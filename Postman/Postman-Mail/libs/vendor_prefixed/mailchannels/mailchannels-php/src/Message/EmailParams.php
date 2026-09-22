<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Message;

use PostSMTP\Vendor\MailChannels\Exception\ValidationException;

/**
 * Strongly-typed email payload. Use this when you want compile-time-ish
 * validation; for quick scripts you can pass a plain array to Emails::send().
 */
final class EmailParams
{
    /** @var list<Personalization> */
    public readonly array $personalizations;

    /** @var list<Content> */
    public readonly array $content;

    /** @var list<Attachment>|null */
    public readonly ?array $attachments;

    /** @var array<string, string>|null */
    public readonly ?array $headers;

    /** @var array<string, mixed>|null */
    public readonly ?array $trackingSettings;

    /** @var array<string, mixed>|null */
    public readonly ?array $unsubscribeSettings;

    /**
     * @param iterable<Personalization>      $personalizations
     * @param iterable<Content>              $content
     * @param iterable<Attachment>|null      $attachments
     * @param array<string, string>|null     $headers
     * @param array<string, mixed>|null      $trackingSettings
     * @param array<string, mixed>|null      $unsubscribeSettings
     */
    public function __construct(
        public readonly EmailAddress $from,
        public readonly string $subject,
        iterable $personalizations,
        iterable $content,
        public readonly ?EmailAddress $replyTo = null,
        public readonly ?EmailAddress $envelopeFrom = null,
        ?array $headers = null,
        ?iterable $attachments = null,
        public readonly ?bool $transactional = null,
        public readonly ?string $campaignId = null,
        ?array $trackingSettings = null,
        public readonly ?string $dkimDomain = null,
        public readonly ?string $dkimPrivateKey = null,
        public readonly ?string $dkimSelector = null,
        ?array $unsubscribeSettings = null,
    ) {
        $pList = is_array($personalizations) ? $personalizations : iterator_to_array($personalizations);
        $cList = is_array($content) ? $content : iterator_to_array($content);
        if ($pList === []) {
            throw new ValidationException(
                'EmailParams requires at least one personalization.',
                errorCode: 'InvalidEmailParams',
            );
        }
        if ($cList === []) {
            throw new ValidationException(
                'EmailParams requires at least one content part.',
                errorCode: 'InvalidEmailParams',
            );
        }
        if ($subject === '') {
            throw new ValidationException(
                'EmailParams requires a non-empty subject.',
                errorCode: 'InvalidEmailParams',
            );
        }
        $this->personalizations = array_values($pList);
        $this->content = array_values($cList);
        $this->attachments = $attachments === null ? null : array_values(
            is_array($attachments) ? $attachments : iterator_to_array($attachments)
        );
        $this->headers = $headers !== null ? EmailHeaders::validate($headers) : null;
        $this->trackingSettings = $trackingSettings;
        $this->unsubscribeSettings = $unsubscribeSettings;
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        $payload = [
            'from' => $this->from->toArray(),
            'subject' => $this->subject,
            'personalizations' => array_map(static fn (Personalization $p) => $p->toArray(), $this->personalizations),
            'content' => array_map(static fn (Content $c) => $c->toArray(), $this->content),
        ];
        if ($this->replyTo !== null) {
            $payload['reply_to'] = $this->replyTo->toArray();
        }
        if ($this->envelopeFrom !== null) {
            $payload['envelope_from'] = $this->envelopeFrom->toArray();
        }
        if ($this->headers !== null) {
            $payload['headers'] = $this->headers;
        }
        if ($this->attachments !== null) {
            $payload['attachments'] = array_map(
                static fn (Attachment $a) => $a->toArray(),
                $this->attachments,
            );
        }
        if ($this->transactional !== null) {
            $payload['transactional'] = $this->transactional;
        }
        if ($this->campaignId !== null) {
            $payload['campaign_id'] = $this->campaignId;
        }
        if ($this->trackingSettings !== null) {
            $payload['tracking_settings'] = $this->trackingSettings;
        }
        if ($this->unsubscribeSettings !== null) {
            $payload['unsubscribe_settings'] = $this->unsubscribeSettings;
        }
        if ($this->dkimDomain !== null) {
            $payload['dkim_domain'] = $this->dkimDomain;
        }
        if ($this->dkimPrivateKey !== null) {
            $payload['dkim_private_key'] = $this->dkimPrivateKey;
        }
        if ($this->dkimSelector !== null) {
            $payload['dkim_selector'] = $this->dkimSelector;
        }
        return $payload;
    }
}
