<?php

namespace ArchiElite\LogViewer;

use ArchiElite\LogViewer\Utils\Utils;

class Host
{
    public bool $isRemote;

    public function __construct(
        public string|null $identifier,
        public string $name,
        public string|null $host = null,
        public array|null $headers = null,
        public array|null $auth = null,
    ) {
        $this->isRemote = $this->isRemote();
    }

    public static function fromConfig(string|int $identifier, array $config = []): self
    {
        return new self(
            is_string($identifier) ? $identifier : Utils::shortMd5($config['host']),
            $config['name'] ?? (is_string($identifier) ? $identifier : $config['host']),
            $config['host'] ?? null,
            $config['headers'] ?? [],
            $config['auth'] ?? [],
        );
    }

    public function isRemote(): bool
    {
        return ! is_null($this->host);
    }
}
