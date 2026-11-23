<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\BadRequestException;
use JsonException;
use LogicException;

/**
 * Niezmienny obraz żądania HTTP. Middleware i router tworzą jego kopie z dodatkowymi atrybutami.
 */
final class Request
{
    /** @var array<string, string> */
    private array $routeParams = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /** @var array<string, mixed>|null */
    private ?array $json = null;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $headers klucze małymi literami
     * @param array<string, mixed> $files
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $headers = [],
        private readonly array $files = [],
        private readonly array $server = [],
        private readonly string $rawBody = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }

        foreach (['CONTENT_TYPE' => 'content-type', 'CONTENT_LENGTH' => 'content-length'] as $key => $name) {
            if (isset($_SERVER[$key])) {
                $headers[$name] = (string) $_SERVER[$key];
            }
        }

        $uriPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            self::normalizePath(is_string($uriPath) ? $uriPath : '/'),
            $_GET,
            $_POST,
            $headers,
            $_FILES,
            $_SERVER,
            (string) file_get_contents('php://input'),
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Parametr z query stringa. Tablice (np. ?status[]=x) są ignorowane, bo żaden filtr ich nie oczekuje.
     */
    public function query(string $key, ?string $default = null): ?string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) ? trim($value) : $default;
    }

    /**
     * Query string razem ze znakiem "?" albo pusty tekst, np. do zapamiętania adresu przed logowaniem.
     */
    public function queryString(): string
    {
        return $this->query === [] ? '' : '?' . http_build_query($this->query);
    }

    /**
     * Pole formularza albo pole z ciała JSON.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        return $this->json()[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->body + $this->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        if (!str_contains($this->header('content-type') ?? '', 'application/json') || $this->rawBody === '') {
            return $this->json = [];
        }

        try {
            $decoded = json_decode($this->rawBody, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestException('error.invalid_json');
        }

        if (!is_array($decoded)) {
            throw new BadRequestException('error.invalid_json');
        }

        return $this->json = $decoded;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '');
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on' || $this->header('x-forwarded-proto') === 'https';
    }

    public function isMethodSafe(): bool
    {
        return in_array($this->method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * Ścieżki /api/* zawsze odpowiadają JSON-em, nawet przy błędach.
     */
    public function wantsJson(): bool
    {
        return str_starts_with($this->path, '/api/') || str_contains($this->header('accept') ?? '', 'application/json');
    }

    public function param(string $name): string
    {
        if (!array_key_exists($name, $this->routeParams)) {
            throw new LogicException(sprintf('Trasa nie definiuje parametru {%s}.', $name));
        }

        return $this->routeParams[$name];
    }

    /**
     * Parametr trasy będący identyfikatorem rekordu. Niepoprawna wartość kończy się odpowiedzią 400.
     */
    public function idParam(string $name = 'id'): int
    {
        return IdParam::parse($this->param($name));
    }

    /**
     * @param array<string, string> $params
     */
    public function withRouteParams(array $params): self
    {
        $clone = clone $this;
        $clone->routeParams = $params;

        return $clone;
    }

    public function attribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    public static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : $path;
    }
}
