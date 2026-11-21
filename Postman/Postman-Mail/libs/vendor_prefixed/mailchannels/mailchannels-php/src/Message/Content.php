<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Message;

use JsonSerializable;

final class Content implements JsonSerializable
{
    public const TEXT_PLAIN = 'text/plain';
    public const TEXT_HTML = 'text/html';
    public const TEMPLATE_MUSTACHE = 'mustache';

    public function __construct(
        public readonly string $type,
        public readonly string $value,
        public readonly ?string $templateType = null,
    ) {
    }

    public static function plain(string $value): self
    {
        return new self(self::TEXT_PLAIN, $value);
    }

    public static function html(string $value): self
    {
        return new self(self::TEXT_HTML, $value);
    }

    public static function mustacheHtml(string $value): self
    {
        return new self(self::TEXT_HTML, $value, self::TEMPLATE_MUSTACHE);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = ['type' => $this->type, 'value' => $this->value];
        if ($this->templateType !== null) {
            $out['template_type'] = $this->templateType;
        }
        return $out;
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
