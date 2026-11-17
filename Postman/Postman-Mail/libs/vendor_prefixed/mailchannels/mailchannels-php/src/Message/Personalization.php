<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Message;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ValidationException;

final class Personalization implements JsonSerializable
{
    /** @var list<EmailAddress> */
    public readonly array $to;

    /** @var list<EmailAddress>|null */
    public readonly ?array $cc;

    /** @var list<EmailAddress>|null */
    public readonly ?array $bcc;

    /** @var array<string, string>|null */
    public readonly ?array $headers;

    /** @var array<string, string>|null */
    public readonly ?array $substitutions;

    /** @var array<string, mixed>|null */
    public readonly ?array $dynamicTemplateData;

    /**
     * @param iterable<mixed> $to
     * @param iterable<mixed>|null $cc
     * @param iterable<mixed>|null $bcc
     * @param array<string, string>|null $headers
     * @param array<string, string>|null $substitutions
     * @param array<string, mixed>|null $dynamicTemplateData
     */
    public function __construct(
        iterable $to,
        ?iterable $cc = null,
        ?iterable $bcc = null,
        public readonly ?string $subject = null,
        public readonly EmailAddress|null $from = null,
        public readonly EmailAddress|null $replyTo = null,
        ?array $headers = null,
        ?array $substitutions = null,
        ?array $dynamicTemplateData = null,
        public readonly ?string $dkimDomain = null,
        public readonly ?string $dkimPrivateKey = null,
        public readonly ?string $dkimSelector = null,
    ) {
        $toList = EmailAddress::listFrom($to);
        if ($toList === []) {
            throw new ValidationException(
                'Personalization requires at least one `to` recipient.',
                errorCode: 'InvalidPersonalization',
            );
        }
        $this->to = array_values($toList);
        $this->cc = $cc === null ? null : array_values(EmailAddress::listFrom($cc));
        $this->bcc = $bcc === null ? null : array_values(EmailAddress::listFrom($bcc));
        $this->headers = $headers !== null ? EmailHeaders::validate($headers) : null;
        $this->substitutions = $substitutions;
        $this->dynamicTemplateData = $dynamicTemplateData;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['to' => array_map(static fn (EmailAddress $a) => $a->toArray(), $this->to)];
        if ($this->cc !== null) {
            $out['cc'] = array_map(static fn (EmailAddress $a) => $a->toArray(), $this->cc);
        }
        if ($this->bcc !== null) {
            $out['bcc'] = array_map(static fn (EmailAddress $a) => $a->toArray(), $this->bcc);
        }
        if ($this->subject !== null) {
            $out['subject'] = $this->subject;
        }
        if ($this->from !== null) {
            $out['from'] = $this->from->toArray();
        }
        if ($this->replyTo !== null) {
            $out['reply_to'] = $this->replyTo->toArray();
        }
        if ($this->headers !== null) {
            $out['headers'] = $this->headers;
        }
        if ($this->substitutions !== null) {
            $out['substitutions'] = $this->substitutions;
        }
        if ($this->dynamicTemplateData !== null) {
            $out['dynamic_template_data'] = $this->dynamicTemplateData;
        }
        if ($this->dkimDomain !== null) {
            $out['dkim_domain'] = $this->dkimDomain;
        }
        if ($this->dkimPrivateKey !== null) {
            $out['dkim_private_key'] = $this->dkimPrivateKey;
        }
        if ($this->dkimSelector !== null) {
            $out['dkim_selector'] = $this->dkimSelector;
        }
        return $out;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
