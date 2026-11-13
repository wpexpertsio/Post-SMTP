<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Message;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ValidationException;
use Stringable;

final class EmailAddress implements JsonSerializable, Stringable
{
    public function __construct(
        public readonly string $email,
        public readonly ?string $name = null,
    ) {
        if (!self::looksLikeEmail($email)) {
            throw new ValidationException(
                "Email address '{$email}' must contain a local part and a domain.",
                errorCode: 'InvalidEmailAddress',
            );
        }
    }

    /**
     * Accept any of: a string ('alice@example.com'), an EmailAddress, or an
     * associative array (['email' => '...', 'name' => '...']).
     */
    public static function from(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }
        if (is_string($value)) {
            return new self($value);
        }
        if (is_array($value)) {
            $email = $value['email'] ?? null;
            if (!is_string($email)) {
                throw new ValidationException(
                    'Email address mapping must include an `email` string.',
                    errorCode: 'InvalidEmailAddress',
                );
            }
            $name = $value['name'] ?? null;
            return new self($email, is_string($name) ? $name : null);
        }
        throw new ValidationException(
            'Email address must be a string, array, or MailChannels\\Message\\EmailAddress.',
            errorCode: 'InvalidEmailAddress',
        );
    }

    /**
     * @param iterable<mixed> $values
     * @return list<self>
     */
    public static function listFrom(iterable $values): array
    {
        $out = [];
        foreach ($values as $value) {
            $out[] = self::from($value);
        }
        return $out;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = ['email' => $this->email];
        if ($this->name !== null && $this->name !== '') {
            $out['name'] = $this->name;
        }
        return $out;
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->name !== null && $this->name !== ''
            ? sprintf('%s <%s>', $this->name, $this->email)
            : $this->email;
    }

    private static function looksLikeEmail(string $value): bool
    {
        if ($value === '' || str_starts_with($value, '@') || str_ends_with($value, '@')) {
            return false;
        }
        return str_contains($value, '@');
    }
}
