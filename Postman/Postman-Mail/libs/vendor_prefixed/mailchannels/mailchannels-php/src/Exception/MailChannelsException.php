<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Exception;

use RuntimeException;
use Throwable;

class MailChannelsException extends RuntimeException
{
    /** @var array<string, string> */
    protected array $headers;

    /** @var mixed */
    protected $responseBody;

    /** @param array<string, string> $headers */
    public function __construct(
        string $message,
        protected ?int $statusCode = null,
        protected ?string $errorCode = null,
        protected ?string $errorType = null,
        protected ?string $requestId = null,
        protected ?string $retryAfter = null,
        protected ?string $suggestedAction = null,
        array $headers = [],
        mixed $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
        $this->headers = $headers;
        $this->responseBody = $responseBody;
        if ($this->errorType === null) {
            $this->errorType = self::deriveErrorType($errorCode, $statusCode);
        }
        if ($this->suggestedAction === null) {
            $this->suggestedAction = self::deriveSuggestedAction($errorCode, $statusCode);
        }
        if ($this->requestId === null) {
            $this->requestId = self::extractRequestId($headers);
        }
        if ($this->retryAfter === null && isset($headers['Retry-After'])) {
            $this->retryAfter = $headers['Retry-After'];
        }
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getRetryAfter(): ?string
    {
        return $this->retryAfter;
    }

    public function getSuggestedAction(): ?string
    {
        return $this->suggestedAction;
    }

    /** @return array<string, string> */
    public function getResponseHeaders(): array
    {
        return $this->headers;
    }

    public function getResponseBody(): mixed
    {
        return $this->responseBody;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'error_code' => $this->errorCode,
            'error_type' => $this->errorType,
            'request_id' => $this->requestId,
            'retry_after' => $this->retryAfter,
            'suggested_action' => $this->suggestedAction,
            'headers' => $this->headers,
            'response' => $this->responseBody,
        ];
    }

    private static function deriveErrorType(?string $code, ?int $status): ?string
    {
        if ($code !== null && $code !== '') {
            return $code;
        }
        return match (true) {
            $status === 401 => 'authentication_error',
            $status === 403 => 'permission_error',
            $status === 409 => 'conflict_error',
            $status === 413 => 'payload_too_large',
            $status === 429 => 'rate_limit_error',
            $status !== null && $status >= 500 => 'server_error',
            $status !== null && $status >= 400 => 'invalid_request_error',
            default => null,
        };
    }

    private static function deriveSuggestedAction(?string $code, ?int $status): ?string
    {
        if ($code === 'MissingApiKey') {
            return 'Configure MAILCHANNELS_API_KEY or pass apiKey to MailChannels\\Client.';
        }
        return match (true) {
            $status === 401 => 'Check that the MailChannels API key is valid.',
            $status === 403 => 'Confirm the API key has access to this MailChannels resource.',
            $status === 409 => 'Use the existing resource or retry with a unique identifier.',
            $status === 413 => 'Reduce message size or attachment payload before retrying.',
            $status === 429 => 'Back off before retrying; inspect Retry-After if present.',
            $status !== null && $status >= 500 => 'Retry later or contact MailChannels support with the request ID.',
            default => null,
        };
    }

    /** @param array<string, string> $headers */
    private static function extractRequestId(array $headers): ?string
    {
        $candidates = ['X-Request-ID', 'X-Request-Id', 'Request-ID', 'X-Correlation-ID', 'X-Amzn-Trace-Id'];
        $lower = [];
        foreach ($headers as $key => $value) {
            $lower[strtolower($key)] = $value;
        }
        foreach ($candidates as $candidate) {
            $value = $lower[strtolower($candidate)] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }
        return null;
    }
}
