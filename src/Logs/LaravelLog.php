<?php

namespace ArchiElite\LogViewer\Logs;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\LogLevels\LaravelLogLevel;
use ArchiElite\LogViewer\Utils\Utils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use ArchiElite\LogViewer\MailParser\Message;

class LaravelLog extends Log
{
    public static string $name = 'Laravel';

    public static string $regex = '/\[(?P<datetime>[^\]]+)\] (?P<environment>\S+)\.(?P<level>\S+): (?P<message>.*)/';

    public int $fullTextLength;

    public static array $columns = [
        ['label' => 'Severity', 'data_path' => 'level'],
        ['label' => 'Datetime', 'data_path' => 'datetime'],
        ['label' => 'Env', 'data_path' => 'extra.environment'],
        ['label' => 'Message', 'data_path' => 'message'],
    ];

    protected function parseText(array &$matches = []): void
    {
        $this->text = mb_convert_encoding(rtrim($this->text, "\t\n\r"), 'UTF-8', 'UTF-8');
        $length = strlen($this->text);

        $this->extractContextsFromFullText();

        $this->extra['log_size'] = $length;
        $this->extra['log_size_formatted'] = Utils::bytesForHumans($length);

        [$firstLine, $theRestOfIt] = explode("\n", Str::finish($this->text, "\n"), 2);

        $firstLineSplit = str_split($firstLine, 1000);

        preg_match(static::regexPattern(), array_shift($firstLineSplit), $matches);

        $this->datetime = Carbon::parse($matches[1])->tz(
            config('log-viewer.timezone', config('app.timezone', 'UTC'))
        );

        $this->extra['environment'] = $matches[5] ?? null;

        $middle = trim(rtrim($matches[4] ?? '', $this->extra['environment'] . '.'));

        $this->level = strtoupper($matches[6] ?? '');

        $firstLineText = $matches[7];

        if (! empty($middle)) {
            $firstLineText = $middle . ' ' . $firstLineText;
        }

        $this->message = trim($firstLineText);
        $text = $firstLineText . ($matches[8] ?? '') . implode('', $firstLineSplit) . "\n" . $theRestOfIt;

        if (session()->get('log-viewer:shorter-stack-traces', false)) {
            $excludes = config('log-viewer.shorter_stack_trace_excludes', []);
            $emptyLineCharacter = '    ...';
            $lines = explode("\n", $text);
            $filteredLines = [];
            foreach ($lines as $line) {
                $shouldExclude = false;
                foreach ($excludes as $excludePattern) {
                    if (str_starts_with($line, '#') && str_contains($line, $excludePattern)) {
                        $shouldExclude = true;

                        break;
                    }
                }

                if ($shouldExclude && end($filteredLines) !== $emptyLineCharacter) {
                    $filteredLines[] = $emptyLineCharacter;
                } elseif (! $shouldExclude) {
                    $filteredLines[] = $line;
                }
            }
            $text = implode("\n", $filteredLines);
        }

        if (strlen($text) > LogViewer::maxLogSize()) {
            $text = Str::limit($text, LogViewer::maxLogSize());
            $this->extra['log_text_incomplete'] = true;
        }

        $this->text = trim($text);
        $this->extractMailPreview();
    }

    protected function fillMatches(array $matches = []): void
    {
        //
    }

    protected static function regexPattern(): string
    {
        return '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}\.?(\d{6}([\+-]\d\d:\d\d)?)?)\](.*?(\w+)\.|.*?)('
            . implode('|', array_filter(LaravelLogLevel::caseValues()))
            . ')?: ?(.*?)( in [\/].*?:[0-9]+)?$/is';
    }

    protected function extractContextsFromFullText(): void
    {
        // The regex pattern to find JSON strings.
        $pattern = '/(\{(?:[^{}]|(?R))*\}|\[(?:[^\[\]]|(?R))*\])/';
        $contexts = [];

        // Find matches.
        preg_match_all($pattern, $this->text, $matches);

        if (! isset($matches[0])) {
            return;
        }

        foreach ($matches[0] as $json_string) {
            $json_data = json_decode(trim($json_string), true);

            if (json_last_error() == JSON_ERROR_CTRL_CHAR) {
                $json_data = json_decode(str_replace("\n", '\\n', $json_string), true);
            }

            if (json_last_error() == JSON_ERROR_NONE) {
                $contexts[] = $json_data;

                if (config('log-viewer.strip_extracted_context', false)) {
                    $this->text = rtrim(str_replace($json_string, '', $this->text));
                }
            }
        }

        if (count($contexts) > 1) {
            $this->context = $contexts;
        } elseif (count($contexts) === 1) {
            $this->context = $contexts[0];
        }
    }

    protected function extractMailPreview(): void
    {
        $possibleParts = preg_split('/[^\r]\n/', $this->text);
        $part = null;

        foreach ($possibleParts as $possiblePart) {
            if (
                Str::contains($this->text, 'To:')
                && Str::contains($this->text, 'From:')
                && Str::contains($this->text, 'MIME-Version: 1.0')
            ) {
                $part = $possiblePart;

                break;
            }
        }

        if (! $part) {
            return;
        }

        $message = Message::fromString($part);

        $this->extra['mail_preview'] = [
            'id' => $message->getId() ?: null,
            'subject' => $message->getSubject(),
            'from' => $message->getFrom(),
            'to' => $message->getTo(),
            'attachments' => array_map(fn ($attachment) => [
                'content' => $attachment->getContent(),
                'content_type' => $attachment->getContentType(),
                'filename' => $attachment->getFilename(),
                'size_formatted' => Utils::bytesForHumans($attachment->getSize()),
            ], $message->getAttachments()),
            'html' => $message->getHtmlPart()?->getContent(),
            'text' => $message->getTextPart()?->getContent(),
            'size_formatted' => Utils::bytesForHumans($message->getSize()),
        ];
    }
}
