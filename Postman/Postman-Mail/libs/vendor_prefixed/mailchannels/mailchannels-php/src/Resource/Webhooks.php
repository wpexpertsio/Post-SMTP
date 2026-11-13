<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Enum\WebhookBatchStatus;
use PostSMTP\Vendor\MailChannels\Exception\ValidationException;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\WebhookBatchListResponse;
use PostSMTP\Vendor\MailChannels\Response\WebhookListResponse;
use PostSMTP\Vendor\MailChannels\Response\WebhookPublicKeyResponse;
use PostSMTP\Vendor\MailChannels\Response\WebhookResendResponse;
use PostSMTP\Vendor\MailChannels\Response\WebhookValidationResultsResponse;

/**
 * Webhook subscription management. For inbound signature verification, see
 * {@see \MailChannels\Webhook\Verifier} — those helpers are stateless and do
 * not need a Client instance.
 */
final class Webhooks extends AbstractResource
{
    public function list(): WebhookListResponse
    {
        return new WebhookListResponse($this->request('GET', '/webhook'));
    }

    public function create(string $endpoint): Response
    {
        self::validateEndpoint($endpoint);
        $this->client->config()->logger->info('Creating webhook', ['endpoint' => $endpoint]);
        return $this->request('POST', '/webhook', null, ['endpoint' => $endpoint]);
    }

    /**
     * Validate that an endpoint is a well-formed http(s) URL. MailChannels
     * delivers webhook events over HTTP(S) only; other schemes are rejected
     * before the request is sent.
     */
    public static function validateEndpoint(string $endpoint): void
    {
        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            throw new ValidationException(
                "Webhook endpoint '{$endpoint}' is not a valid URL.",
                errorCode: 'InvalidWebhookEndpoint',
            );
        }
        $scheme = strtolower((string) parse_url($endpoint, PHP_URL_SCHEME));
        if ($scheme !== 'http' && $scheme !== 'https') {
            throw new ValidationException(
                "Webhook endpoint scheme must be http or https; got '{$scheme}'.",
                errorCode: 'InvalidWebhookEndpoint',
            );
        }
    }

    public function deleteAll(): Response
    {
        $this->client->config()->logger->info('Deleting all webhooks');
        return $this->request('DELETE', '/webhook');
    }

    /**
     * @param list<WebhookBatchStatus|string>|null $statuses
     */
    public function batches(
        ?string $createdAfter = null,
        ?string $createdBefore = null,
        ?array $statuses = null,
        ?string $webhook = null,
        ?int $limit = null,
        ?int $offset = null,
    ): WebhookBatchListResponse {
        $resolvedStatuses = $statuses === null ? null : array_map(
            static fn ($s) => $s instanceof WebhookBatchStatus ? $s->value : (string) $s,
            $statuses,
        );
        $query = self::compactOrNull([
            'created_after' => $createdAfter,
            'created_before' => $createdBefore,
            'statuses' => $resolvedStatuses,
            'webhook' => $webhook,
            'limit' => $limit,
            'offset' => $offset,
        ]);
        return new WebhookBatchListResponse(
            $this->request('GET', '/webhook-batch', null, $query),
        );
    }

    /** `POST /webhook-batch/{batch_id}/resend` — synchronously re-deliver a failed batch. */
    public function resendBatch(int $batchId): WebhookResendResponse
    {
        $this->client->config()->logger->info('Resending webhook batch', ['batch_id' => $batchId]);
        $path = self::encodedPath('webhook-batch', $batchId, 'resend');
        return new WebhookResendResponse(
            $this->request('POST', $path),
        );
    }

    /** `GET /webhook/public-key?id=...` — does not require an API key. */
    public function publicKey(string $keyId): WebhookPublicKeyResponse
    {
        return new WebhookPublicKeyResponse(
            $this->request('GET', '/webhook/public-key', null, ['id' => $keyId], requireApiKey: false),
        );
    }

    public function validate(?string $requestId = null): WebhookValidationResultsResponse
    {
        $payload = self::compactOrNull(['request_id' => $requestId]);
        return new WebhookValidationResultsResponse(
            $this->request('POST', '/webhook/validate', $payload),
        );
    }
}
