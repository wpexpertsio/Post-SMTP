<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Response;

use JsonSerializable;
use PostSMTP\Vendor\MailChannels\Exception\ResponseValidationException;
use PostSMTP\Vendor\MailChannels\Internal\ResponseValidator;

/**
 * One per-webhook entry in
 * {@see WebhookValidationResultsResponse::$results}. Spec schema:
 * `WebhookValidationResult`.
 *
 * `$result` is `"passed"` when the endpoint returned a 2xx during the
 * validation test, `"failed"` otherwise. `$response` carries the actual
 * HTTP response MailChannels received.
 *
 * Plain value object — not a `Response` subclass.
 */
final class WebhookValidationResult implements JsonSerializable
{
    public function __construct(
        public readonly ?string $webhook = null,
        public readonly ?string $result = null,
        public readonly ?WebhookEndpointResponse $response = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws ResponseValidationException when a present field has the
     *     wrong type.
     */
    public static function fromArray(array $data): self
    {
        $responseData = ResponseValidator::asNullableAssocArray($data, 'response');

        return new self(
            webhook: ResponseValidator::asNullableString($data, 'webhook'),
            result: ResponseValidator::asNullableString($data, 'result'),
            response: $responseData === null ? null : WebhookEndpointResponse::fromArray($responseData),
        );
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $out = [];
        if ($this->webhook !== null) {
            $out['webhook'] = $this->webhook;
        }
        if ($this->result !== null) {
            $out['result'] = $this->result;
        }
        if ($this->response !== null) {
            $out['response'] = $this->response;
        }
        return $out;
    }
}
