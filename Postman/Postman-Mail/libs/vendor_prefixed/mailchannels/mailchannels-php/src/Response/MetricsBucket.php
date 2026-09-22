<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One time-series bucket entry: an event count for a single time
 * interval. The `metrics/engagement`, `/performance`, `/volume`, and
 * `/recipient-behaviour` endpoints all return lists of these grouped
 * under named bucket categories (`open`, `click`, `delivered`, ...).
 *
 * Plain value object — not a `Response` subclass.
 */
final class MetricsBucket implements JsonSerializable
{
    public function __construct(
        public readonly ?int $count = null,
        public readonly ?string $periodStart = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            count: ResponseValidator::asNullableInt($data, 'count'),
            periodStart: ResponseValidator::asNullableString($data, 'period_start'),
        );
    }

    /**
     * Extract a list of MetricsBucket from `$parent[$key]`. Returns null
     * when the parent itself is null or the key is absent.
     *
     * @param array<string, mixed>|null $parent
     * @return list<self>|null
     */
    public static function listFromParent(?array $parent, string $key): ?array
    {
        if ($parent === null) {
            return null;
        }
        $raw = ResponseValidator::asNullableListOfArrays($parent, $key);
        if ($raw === null) {
            return null;
        }
        return array_map(static fn (array $b): self => self::fromArray($b), $raw);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->periodStart !== null) {
            $out['period_start'] = $this->periodStart;
        }
        if ($this->count !== null) {
            $out['count'] = $this->count;
        }
        return $out;
    }
}
