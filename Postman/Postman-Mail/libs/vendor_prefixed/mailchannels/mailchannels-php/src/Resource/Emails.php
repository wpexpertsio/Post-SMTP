<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Exception\ValidationException;
use PostSMTP\Vendor\MailChannels\Message\Attachment;
use PostSMTP\Vendor\MailChannels\Message\Content;
use PostSMTP\Vendor\MailChannels\Message\EmailAddress;
use PostSMTP\Vendor\MailChannels\Message\EmailHeaders;
use PostSMTP\Vendor\MailChannels\Message\EmailParams;
use PostSMTP\Vendor\MailChannels\Message\Personalization;
use PostSMTP\Vendor\MailChannels\Response\QueuedSendResponse;
use PostSMTP\Vendor\MailChannels\Response\SendResponse;

/**
 * `POST /send` and `POST /send-async`.
 *
 * Accepts either a structured EmailParams object or a flexible associative
 * array. The array form supports both the compact Resend-style shortcuts
 * (to/cc/bcc/text/html) and the full MailChannels payload shape.
 */
final class Emails extends AbstractResource
{
    /** @param EmailParams|array<string, mixed> $params */
    public function send(EmailParams|array $params, bool $dryRun = false): SendResponse
    {
        $payload = self::normalize($params);
        $query = $dryRun ? ['dry-run' => 'true'] : null;
        $this->client->config()->logger->info('Sending email through MailChannels', ['dry_run' => $dryRun]);
        return new SendResponse(
            $this->request('POST', '/send', $payload, $query),
        );
    }

    /**
     * Queue an email through `/send-async`. The server queues the message
     * and acks with a `request_id` and `queued_at` timestamp — there is no
     * message ID at this point because delivery hasn't happened yet. This is
     * server-side async — it does NOT make the HTTP call itself async.
     *
     * @param EmailParams|array<string, mixed> $params
     */
    public function queue(EmailParams|array $params): QueuedSendResponse
    {
        $payload = self::normalize($params);
        $this->client->config()->logger->info('Queueing email through MailChannels /send-async');
        return new QueuedSendResponse(
            $this->request('POST', '/send-async', $payload),
        );
    }

    /**
     * @param EmailParams|array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function normalize(EmailParams|array $params): array
    {
        if ($params instanceof EmailParams) {
            return $params->toPayload();
        }
        return self::normalizeArray($params);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function normalizeArray(array $params): array
    {
        $from = self::extractFrom($params);
        $subject = $params['subject'] ?? null;
        if (!is_string($subject) || $subject === '') {
            throw new ValidationException('Email parameters must include a non-empty `subject`.', errorCode: 'InvalidEmailParams');
        }

        $content = isset($params['content']) ? self::normalizeContent($params['content']) : self::contentFromShortcuts($params);
        $personalizations = isset($params['personalizations'])
            ? self::normalizePersonalizations($params['personalizations'])
            : [self::personalizationFromShortcuts($params)];

        $payload = [
            'from' => $from,
            'subject' => $subject,
            'content' => $content,
            'personalizations' => $personalizations,
        ];

        self::copyAddressOptional($params, $payload, 'reply_to');
        self::copyAddressOptional($params, $payload, 'envelope_from');

        if (isset($params['headers'])) {
            $payload['headers'] = self::normalizeHeaderMap($params['headers']);
        }

        if (isset($params['attachments'])) {
            $payload['attachments'] = self::normalizeAttachments($params['attachments']);
        }

        if (isset($params['tracking_settings'])) {
            $payload['tracking_settings'] = self::asAssoc(
                $params['tracking_settings'],
                '`tracking_settings`',
            );
        }

        if (isset($params['unsubscribe_settings'])) {
            $payload['unsubscribe_settings'] = self::asAssoc(
                $params['unsubscribe_settings'],
                '`unsubscribe_settings`',
            );
        }

        foreach (['transactional', 'campaign_id', 'dkim_domain', 'dkim_private_key', 'dkim_selector'] as $optional) {
            if (array_key_exists($optional, $params) && $params[$optional] !== null) {
                $payload[$optional] = $params[$optional];
            }
        }

        return $payload;
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    private static function normalizeHeaderMap(mixed $value): array
    {
        if (!is_array($value)) {
            throw new ValidationException(
                '`headers` must be an array of header => value strings.',
                errorCode: 'InvalidEmailParams',
            );
        }
        $headers = [];
        foreach ($value as $key => $val) {
            if (!is_string($key) || !is_string($val)) {
                throw new ValidationException(
                    '`headers` must use string keys and string values.',
                    errorCode: 'InvalidEmailParams',
                );
            }
            $headers[$key] = $val;
        }
        return EmailHeaders::validate($headers);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function extractFrom(array $params): array
    {
        foreach (['from', 'from_email', 'from_address', 'from_field'] as $key) {
            if (array_key_exists($key, $params)) {
                return EmailAddress::from($params[$key])->toArray();
            }
        }
        throw new ValidationException('Email parameters must include `from`.', errorCode: 'InvalidEmailParams');
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, string>>
     */
    private static function contentFromShortcuts(array $params): array
    {
        $content = [];
        if (isset($params['text']) && is_string($params['text']) && $params['text'] !== '') {
            $content[] = ['type' => Content::TEXT_PLAIN, 'value' => $params['text']];
        }
        if (isset($params['html']) && is_string($params['html']) && $params['html'] !== '') {
            $content[] = ['type' => Content::TEXT_HTML, 'value' => $params['html']];
        }
        if ($content === []) {
            throw new ValidationException(
                'Email parameters must include `content`, `text`, or `html` (as strings).',
                errorCode: 'InvalidEmailParams',
            );
        }
        return $content;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function personalizationFromShortcuts(array $params): array
    {
        if (!array_key_exists('to', $params)) {
            throw new ValidationException('Email parameters must include `to`.', errorCode: 'InvalidEmailParams');
        }
        $p = ['to' => self::normalizeAddressList($params['to'])];
        if (!empty($params['cc'])) {
            $p['cc'] = self::normalizeAddressList($params['cc']);
        }
        if (!empty($params['bcc'])) {
            $p['bcc'] = self::normalizeAddressList($params['bcc']);
        }
        return $p;
    }

    /**
     * @param mixed $value
     * @return list<array<string, string>>
     */
    private static function normalizeAddressList(mixed $value): array
    {
        if ($value instanceof EmailAddress) {
            return [$value->toArray()];
        }
        if (is_string($value) || (is_array($value) && isset($value['email']) && is_string($value['email']))) {
            return [EmailAddress::from($value)->toArray()];
        }
        if (is_array($value)) {
            return array_values(array_map(
                static fn ($item) => EmailAddress::from($item)->toArray(),
                $value,
            ));
        }
        throw new ValidationException(
            'Address list must be a string, EmailAddress, array, or list of those.',
            errorCode: 'InvalidEmailParams',
        );
    }

    /**
     * @param mixed $value
     * @return list<array<string, string>>
     */
    private static function normalizeContent(mixed $value): array
    {
        if (!is_iterable($value)) {
            throw new ValidationException('`content` must be a list.', errorCode: 'InvalidEmailParams');
        }
        $out = [];
        foreach ($value as $item) {
            if ($item instanceof Content) {
                $out[] = $item->toArray();
                continue;
            }
            $assoc = self::asAssoc($item, 'Each content entry');
            $type = $assoc['type'] ?? null;
            $itemValue = $assoc['value'] ?? null;
            if (!is_string($type) || !is_string($itemValue)) {
                throw new ValidationException(
                    'Each content entry must include string `type` and `value` keys.',
                    errorCode: 'InvalidEmailParams',
                );
            }
            $entry = ['type' => $type, 'value' => $itemValue];
            if (isset($assoc['template_type']) && is_string($assoc['template_type'])) {
                $entry['template_type'] = $assoc['template_type'];
            }
            $out[] = $entry;
        }
        return $out;
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private static function normalizePersonalizations(mixed $value): array
    {
        if (!is_iterable($value)) {
            throw new ValidationException('`personalizations` must be a list.', errorCode: 'InvalidEmailParams');
        }
        $out = [];
        $index = 0;
        foreach ($value as $item) {
            if ($item instanceof Personalization) {
                $out[] = $item->toArray();
                $index++;
                continue;
            }
            $assoc = self::asAssoc($item, "Personalization #{$index}");
            if (isset($assoc['headers'])) {
                $assoc['headers'] = self::normalizeHeaderMap($assoc['headers']);
            }
            $out[] = $assoc;
            $index++;
        }
        if ($out === []) {
            throw new ValidationException('`personalizations` must contain at least one entry.', errorCode: 'InvalidEmailParams');
        }
        return $out;
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private static function normalizeAttachments(mixed $value): array
    {
        if (!is_iterable($value)) {
            throw new ValidationException('`attachments` must be a list.', errorCode: 'InvalidEmailParams');
        }
        $out = [];
        foreach ($value as $item) {
            if ($item instanceof Attachment) {
                $out[] = $item->toArray();
                continue;
            }
            $assoc = self::asAssoc($item, 'Each attachment');
            if (!isset($assoc['content'], $assoc['filename'])) {
                throw new ValidationException(
                    'Each attachment must include `content` and `filename`.',
                    errorCode: 'InvalidEmailParams',
                );
            }
            $out[] = $assoc;
        }
        return $out;
    }

    /**
     * Coerce a user-supplied value into an associative array with verified
     * string keys, raising ValidationException if either condition fails.
     *
     * @return array<string, mixed>
     */
    private static function asAssoc(mixed $value, string $description): array
    {
        if (!is_array($value)) {
            throw new ValidationException(
                "{$description} must be an array.",
                errorCode: 'InvalidEmailParams',
            );
        }
        $out = [];
        foreach ($value as $key => $val) {
            if (!is_string($key)) {
                throw new ValidationException(
                    "{$description} must use string keys.",
                    errorCode: 'InvalidEmailParams',
                );
            }
            $out[$key] = $val;
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $source
     * @param array<string, mixed> $target
     */
    private static function copyAddressOptional(array $source, array &$target, string $key): void
    {
        if (!array_key_exists($key, $source) || $source[$key] === null) {
            return;
        }
        $target[$key] = EmailAddress::from($source[$key])->toArray();
    }
}
