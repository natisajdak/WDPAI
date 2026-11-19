<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    /** @var array<string, string> */
    protected array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        protected string $content = '',
        protected int $status = 200,
        array $headers = [],
    ) {
        $this->headers = $headers + ['Content-Type' => 'text/html; charset=utf-8'];
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }
}
