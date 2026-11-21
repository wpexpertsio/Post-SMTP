<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Internal;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;

/**
 * Internal helpers used by typed response classes (`SendResponse`,
 * `QueuedSendResponse`, etc.) to extract and type-validate fields out of
 * the decoded response body. Missing fields become `null`; present fields
 * with the wrong type throw {@see ResponseValidationException}.
 *
 * @internal Implementation detail of MailChannels\Response\*; not part of
 *     the public SDK surface.
 */
final class ResponseValidator
{
    /** @param array<string, mixed> $body */
    public static function asNullableString(array $body, string $key): ?string
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be a string; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        return $value;
    }

    /**
     * Try multiple keys in order, returning the first one that's present.
     * Useful when the API has aliases or evolving field names — e.g. the
     * DKIM endpoints emit `gracePeriodExpiresAt` today but a v2 fix may
     * normalize to `grace_period_expires_at`. Accepting both lets the SDK
     * keep working across the transition.
     *
     * @param array<string, mixed> $body
     * @param list<string> $keys
     */
    public static function asNullableStringFromKeys(array $body, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $body) && $body[$key] !== null) {
                return self::asNullableString($body, $key);
            }
        }
        return null;
    }

    /** @param array<string, mixed> $body */
    public static function asNullableBool(array $body, string $key): ?bool
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_bool($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be a bool; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        return $value;
    }

    /** @param array<string, mixed> $body */
    public static function asNullableInt(array $body, string $key): ?int
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_int($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be an int; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        return $value;
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|null
     */
    public static function asNullableAssocArray(array $body, string $key): ?array
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_array($value) || array_is_list($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be an associative array; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $v;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $body
     * @return list<string>|null
     */
    public static function asNullableListOfStrings(array $body, string $key): ?array
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_array($value) || !array_is_list($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be a list; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        $out = [];
        foreach ($value as $i => $item) {
            if (!is_string($item)) {
                throw new ResponseValidationException(
                    "Response field '{$key}'[{$i}] must be a string; got " . get_debug_type($item) . '.',
                    errorCode: 'InvalidResponseShape',
                );
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $body
     * @return list<array<string, mixed>>|null
     */
    public static function asNullableListOfArrays(array $body, string $key): ?array
    {
        if (!array_key_exists($key, $body)) {
            return null;
        }
        $value = $body[$key];
        if ($value === null) {
            return null;
        }
        if (!is_array($value) || !array_is_list($value)) {
            throw new ResponseValidationException(
                "Response field '{$key}' must be a list; got " . get_debug_type($value) . '.',
                errorCode: 'InvalidResponseShape',
            );
        }
        $out = [];
        foreach ($value as $i => $item) {
            if (!is_array($item)) {
                throw new ResponseValidationException(
                    "Response field '{$key}'[{$i}] must be an associative array; got " . get_debug_type($item) . '.',
                    errorCode: 'InvalidResponseShape',
                );
            }
            $assoc = [];
            foreach ($item as $k => $v) {
                $assoc[(string) $k] = $v;
            }
            $out[] = $assoc;
        }
        return $out;
    }
}
