<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Message;

use JsonSerializable;

final class Attachment implements JsonSerializable
{
    public function __construct(
        public readonly string $content,
        public readonly string $filename,
        public readonly ?string $type = null,
        public readonly ?string $contentId = null,
    ) {
    }

    public static function fromBytes(
        string $data,
        string $filename,
        ?string $contentType = null,
        ?string $contentId = null,
    ): self {
        return new self(
            content: base64_encode($data),
            filename: $filename,
            type: $contentType ?? self::guessContentType($filename),
            contentId: $contentId,
        );
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = [
            'content' => $this->content,
            'filename' => $this->filename,
        ];
        if ($this->type !== null) {
            $out['type'] = $this->type;
        }
        if ($this->contentId !== null) {
            $out['content_id'] = $this->contentId;
        }
        return $out;
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function guessContentType(string $filename): string
    {
        $map = [
            'pdf'  => 'application/pdf',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
            'html' => 'text/html',
            'htm'  => 'text/html',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'webp' => 'image/webp',
            'zip'  => 'application/zip',
            'gz'   => 'application/gzip',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ics'  => 'text/calendar',
            'eml'  => 'message/rfc822',
        ];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $map[$ext] ?? 'application/octet-stream';
    }
}
