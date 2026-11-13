<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Resource;

use PostSMTP\Vendor\MailChannels\Enum\SuppressionDeleteSource;
use PostSMTP\Vendor\MailChannels\Enum\SuppressionSource;
use PostSMTP\Vendor\MailChannels\Response;
use PostSMTP\Vendor\MailChannels\Response\SuppressionListResponse;

final class Suppressions extends AbstractResource
{
    public function list(
        ?string $recipient = null,
        SuppressionSource|string|null $source = null,
        ?string $createdBefore = null,
        ?string $createdAfter = null,
        ?int $limit = null,
        ?int $offset = null,
    ): SuppressionListResponse {
        $query = self::compactOrNull([
            'recipient' => $recipient,
            'source' => $source instanceof SuppressionSource ? $source->value : $source,
            'created_before' => $createdBefore,
            'created_after' => $createdAfter,
            'limit' => $limit,
            'offset' => $offset,
        ]);
        return new SuppressionListResponse(
            $this->request('GET', '/suppression-list', null, $query),
        );
    }

    /**
     * @param list<array<string, mixed>> $entries Each entry: {recipient, source, ...}.
     */
    public function create(array $entries, ?bool $addToSubAccounts = null): SuppressionListResponse
    {
        $payload = self::compact([
            'suppression_entries' => $entries,
            'add_to_sub_accounts' => $addToSubAccounts,
        ]);
        $this->client->config()->logger->info('Creating suppression entries', ['count' => count($entries)]);
        return new SuppressionListResponse(
            $this->request('POST', '/suppression-list', $payload),
        );
    }

    public function delete(string $recipient, SuppressionDeleteSource|string|null $source = null): Response
    {
        $query = self::compactOrNull([
            'source' => $source instanceof SuppressionDeleteSource ? $source->value : $source,
        ]);
        $this->client->config()->logger->info('Deleting suppression', ['recipient' => $recipient]);
        $path = self::encodedPath('suppression-list', 'recipients', $recipient);
        return $this->request('DELETE', $path, null, $query);
    }
}
