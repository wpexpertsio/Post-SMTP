<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Client;
use PostSMTP\Vendor\MailChannels\Response;

abstract class AbstractResource
{
    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     * @param array<string, mixed>|null $query
     * @param array<string, string>     $extraHeaders
     */
    protected function request(
        string $method,
        string $path,
        ?array $jsonBody = null,
        ?array $query = null,
        array $extraHeaders = [],
        bool $requireApiKey = true,
    ): Response {
        return $this->client->transport()->request(
            $method,
            $path,
            $jsonBody,
            $query,
            $extraHeaders,
            $requireApiKey,
        );
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    protected static function compact(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[$key] = $value;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>|null
     */
    protected static function compactOrNull(array $values): ?array
    {
        $out = self::compact($values);
        return $out === [] ? null : $out;
    }

    protected static function encodedPath(string|int ...$segments): string
    {
        $encoded = [];
        foreach ($segments as $segment) {
            $encoded[] = rawurlencode((string) $segment);
        }
        return '/' . implode('/', $encoded);
    }
}
