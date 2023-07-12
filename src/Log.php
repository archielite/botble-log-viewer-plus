<?php

namespace ArchiElite\LogViewer;

use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\Utils\Utils;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Log
{
    public int $index;

    public CarbonInterface $time;

    public Level $level;

    public string $environment;

    public string $text;

    public string $fullText;

    public array $contexts = [];

    public bool $fullTextIncomplete = false;

    public int $fullTextLength;

    public string $fileIdentifier;

    public int $filePosition;

    public function __construct(
        int $index,
        string $text,
        string $fileIdentifier,
        int $filePosition,
    ) {
        $this->index = $index;
        $this->fileIdentifier = $fileIdentifier;
        $this->filePosition = $filePosition;
        $text = mb_convert_encoding(rtrim($text, "\t\n\r"), 'UTF-8', 'UTF-8');
        $this->fullTextLength = strlen($text);

        $matches = [];
        [$firstLine, $theRestOfIt] = explode("\n", Str::finish($text, "\n"), 2);

        $firstLineSplit = str_split($firstLine, 1000);
        preg_match(LogViewer::laravelRegexPattern(), array_shift($firstLineSplit), $matches);

        $this->time = Carbon::parse($matches[1])->tz(
            config('plugins.log-viewer-plus.log-viewer.timezone', config('app.timezone', 'UTC'))
        );

        $this->environment = $matches[5] ?? '';

        $middle = trim(rtrim($matches[4] ?? '', $this->environment . '.'));

        $this->level = Level::from(strtolower($matches[6] ?? ''));

        $firstLineText = $matches[7];

        if (! empty($middle)) {
            $firstLineText = $middle . ' ' . $firstLineText;
        }

        $this->text = trim($firstLineText);
        $text = $firstLineText . ($matches[8] ?? '') . implode('', $firstLineSplit) . "\n" . $theRestOfIt;

        if (session()->get('log-viewer:shorter-stack-traces', false)) {
            $excludes = config('plugins.log-viewer-plus.log-viewer.shorter_stack_trace_excludes', []);
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
            $this->fullTextIncomplete = true;
        }

        $this->fullText = trim($text);

        $this->extractContextsFromFullText();
    }

    public function fullTextMatches(string $query = null): bool
    {
        if (empty($query)) {
            return true;
        }

        if (! Str::endsWith($query, '/i')) {
            $query = '/' . $query . '/i';
        }

        return (bool) preg_match($query, $this->fullText);
    }

    public function fullTextLengthFormatted(): string
    {
        return Utils::bytesForHumans($this->fullTextLength);
    }

    public function url(): string
    {
        return route('log-viewer.index', ['file' => $this->fileIdentifier, 'query' => 'log-index:' . $this->index]);
    }

    public function extractContextsFromFullText(): void
    {
        $pattern = '/(\{(?:[^{}]|(?R))*\}|\[(?:[^\[\]]|(?R))*\])/';

        preg_match_all($pattern, $this->fullText, $matches);

        if (! isset($matches[0])) {
            return;
        }

        foreach ($matches[0] as $jsonString) {
            $json_data = json_decode($jsonString, true);

            if (json_last_error() == JSON_ERROR_CTRL_CHAR) {
                $json_data = json_decode(str_replace("\n", '\\n', $jsonString), true);
            }

            if (json_last_error() == JSON_ERROR_NONE) {
                $this->contexts[] = $json_data;
                $this->fullText = str_replace($jsonString, '', $this->fullText);
            }
        }
    }
}
