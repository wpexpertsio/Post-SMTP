<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Response object returned by SDK calls. Behaves as the decoded JSON body
 * (array-accessible and iterable) while also exposing HTTP metadata.
 *
 * Subclassed by per-endpoint typed responses under `MailChannels\Response\`
 * (e.g. {@see \MailChannels\Response\SendResponse}) — those add typed
 * properties on top of the array-accessible base.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class Response implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @param array<string, mixed> $data Decoded response body (or wrapped list under `data`).
     * @param array<string, string> $headers Response headers, original-cased.
     */
    public function __construct(
        public readonly array $data,
        public readonly int $statusCode,
        public readonly array $headers = [],
        public readonly ?string $rawBody = null,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $lower = strtolower($name);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $lower) {
                return $v;
            }
        }
        return null;
    }

    public function requestId(): ?string
    {
        foreach (['X-Request-ID', 'X-Request-Id', 'Request-ID', 'X-Correlation-ID'] as $name) {
            $value = $this->header($name);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->data[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('MailChannels\\Response is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('MailChannels\\Response is read-only.');
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }
}
