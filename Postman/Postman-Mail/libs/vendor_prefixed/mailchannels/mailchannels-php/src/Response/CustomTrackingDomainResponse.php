<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;
use PostSMTP\Vendor\MailChannels\Response;

/**
 * Typed response returned by
 * {@see \MailChannels\Resource\CustomTrackingDomains::create()} and
 * {@see \MailChannels\Resource\CustomTrackingDomains::update()}.
 *
 * Covers two distinct success shapes, mirroring {@see SendResponse}:
 *
 * - **Registered (HTTP 201/200).** The domain is active immediately:
 *   `$name`/`$hostname`/`$scope`/`$status`/`$createdAt` are populated.
 * - **DNS setup pending (HTTP 202).** Before completing registration (or
 *   re-activating a domain whose DNS verification lapsed), MailChannels
 *   requires a CNAME and, the first time a hostname is registered, a TXT
 *   ownership record. `$instructions` (plus `$token`/`$txtRecordName`/
 *   `$txtRecordValue` when a TXT check is outstanding) describe what to
 *   add before retrying.
 *
 * All typed fields are nullable because the API omits whichever fields
 * don't apply to the current shape — branch on `$status !== null` vs
 * `$instructions !== null` rather than the HTTP status code.
 *
 * A `422` response (DNS still not verified on retry) is a real error, not
 * an alternate success shape — the SDK raises {@see \MailChannels\Exception\InvalidRequestException}
 * for it, with the same pending-verification body available via
 * `getResponseBody()`.
 */
final class CustomTrackingDomainResponse extends Response
{
    public readonly ?string $name;
    public readonly ?string $hostname;
    public readonly ?string $scope;
    public readonly ?string $status;
    public readonly ?string $createdAt;
    public readonly ?string $instructions;
    public readonly ?string $token;
    public readonly ?string $txtRecordName;
    public readonly ?string $txtRecordValue;

    /**
     * @throws ResponseValidationException when a present field has the
     *     wrong type. Missing fields are nulled out.
     */
    public function __construct(Response $response)
    {
        $body = $response->data;
        parent::__construct(
            $response->data,
            $response->statusCode,
            $response->headers,
            $response->rawBody,
        );
        $this->name = ResponseValidator::asNullableString($body, 'name');
        $this->hostname = ResponseValidator::asNullableString($body, 'hostname');
        $this->scope = ResponseValidator::asNullableString($body, 'scope');
        $this->status = ResponseValidator::asNullableString($body, 'status');
        $this->createdAt = ResponseValidator::asNullableString($body, 'created_at');
        $this->instructions = ResponseValidator::asNullableString($body, 'instructions');
        $this->token = ResponseValidator::asNullableString($body, 'token');
        $this->txtRecordName = ResponseValidator::asNullableString($body, 'txt_record_name');
        $this->txtRecordValue = ResponseValidator::asNullableString($body, 'txt_record_value');
    }
}
