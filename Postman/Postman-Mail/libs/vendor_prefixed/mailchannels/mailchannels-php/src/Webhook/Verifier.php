<?php

declare(strict_types=1);

namespace PostSMTP\Vendor\MailChannels\Webhook;

use InvalidArgumentException;

/**
 * RFC 9421 helpers for MailChannels webhook payloads. The Verifier parses
 * the Signature-Input header, verifies the Content-Digest, checks signature
 * freshness, and reconstructs the canonical signature base that the signer
 * computed.
 *
 * Ed25519 signature verification itself is left to the caller's preferred
 * crypto library (`sodium_crypto_sign_verify_detached`, `openssl`, the
 * paragonie/sodium_compat polyfill, etc.) so this package does not pull in
 * a crypto dependency. See README for a worked example.
 */
final class Verifier
{
    private const SIGNATURE_INPUT_RE = '/^(?P<name>[^=]+)=\((?P<covered>[^)]*)\)(?P<params>.*)$/';
    private const PARAM_RE = '/;(?P<key>[a-zA-Z0-9_-]+)=(?P<value>"[^"]*"|[^;]+)/';
    private const DIGEST_RE = '/sha-256=:(?P<digest>[^:]+):/';

    public static function parseSignatureInput(string $value): SignatureParameters
    {
        if (preg_match(self::SIGNATURE_INPUT_RE, trim($value), $m) !== 1) {
            throw new InvalidArgumentException('Invalid Signature-Input header.');
        }

        $covered = [];
        foreach (preg_split('/\s+/', trim($m['covered'])) ?: [] as $component) {
            $component = trim($component, " \t\"");
            if ($component !== '') {
                $covered[] = $component;
            }
        }

        $params = [];
        if (preg_match_all(self::PARAM_RE, $m['params'], $paramMatches, PREG_SET_ORDER)) {
            foreach ($paramMatches as $pm) {
                $params[$pm['key']] = self::unquote($pm['value']);
            }
        }

        $created = isset($params['created']) && ctype_digit($params['created']) ? (int) $params['created'] : null;

        return new SignatureParameters(
            signatureName: trim($m['name']),
            coveredComponents: $covered,
            created: $created,
            algorithm: $params['alg'] ?? null,
            keyId: $params['keyid'] ?? null,
            raw: $value,
        );
    }

    /** @param array<string, string> $headers */
    public static function signatureKeyId(array $headers): ?string
    {
        $value = self::header($headers, 'Signature-Input');
        if ($value === null) {
            return null;
        }
        return self::parseSignatureInput($value)->keyId;
    }

    public static function signatureIsFresh(
        SignatureParameters $parameters,
        int $toleranceSeconds = 300,
        ?int $now = null,
    ): bool {
        if ($parameters->created === null) {
            return false;
        }
        $reference = $now ?? time();
        return abs($reference - $parameters->created) <= $toleranceSeconds;
    }

    /** @param array<string, string> $headers */
    public static function verifyContentDigest(array $headers, string $body): bool
    {
        $digest = self::header($headers, 'Content-Digest');
        if ($digest === null) {
            return false;
        }
        if (preg_match(self::DIGEST_RE, $digest, $m) !== 1) {
            return false;
        }
        $expected = base64_decode($m['digest'], true);
        if ($expected === false) {
            return false;
        }
        return hash_equals($expected, hash('sha256', $body, true));
    }

    /**
     * Reconstruct the canonical RFC 9421 signature base that the signer
     * computed before producing the Signature header. Pass this string to
     * your crypto library together with the raw signature bytes and the
     * public key to verify authenticity:
     *
     *   $base = Verifier::buildSignatureBase('POST', $url, $headers, $body, $params);
     *   sodium_crypto_sign_verify_detached($sigBytes, $base, $publicKeyBytes);
     *
     * Supported covered components:
     *   - Derived: @method, @target-uri, @authority, @scheme, @path,
     *     @query, @request-target
     *   - Any HTTP header by lowercase name (value trimmed)
     *
     * @param array<string, string> $headers
     */
    public static function buildSignatureBase(
        string $method,
        string $targetUri,
        array $headers,
        string $body,
        SignatureParameters $parameters,
    ): string {
        $lines = [];
        foreach ($parameters->coveredComponents as $component) {
            $value = self::componentValue($component, $method, $targetUri, $headers, $body);
            $lines[] = sprintf('"%s": %s', strtolower($component), $value);
        }
        $lines[] = '"@signature-params": ' . self::signatureParamsValue($parameters);
        return implode("\n", $lines);
    }

    /** @param array<string, string> $headers */
    private static function componentValue(
        string $component,
        string $method,
        string $targetUri,
        array $headers,
        string $body,
    ): string {
        if (!str_starts_with($component, '@')) {
            $value = self::header($headers, $component);
            if ($value === null) {
                throw new InvalidArgumentException(
                    "Signature base references header '{$component}' which is not present in the request."
                );
            }
            return trim($value);
        }

        return match ($component) {
            '@method' => strtoupper($method),
            '@target-uri' => $targetUri,
            '@authority' => self::authorityFromUri($targetUri),
            '@scheme' => strtolower(self::uriScheme($targetUri) ?? ''),
            '@path' => self::uriPath($targetUri),
            '@query' => self::queryComponent($targetUri),
            '@request-target' => self::requestTarget($targetUri),
            default => throw new InvalidArgumentException(
                "Unsupported derived component '{$component}' in signature base."
            ),
        };
    }

    private static function signatureParamsValue(SignatureParameters $parameters): string
    {
        // RFC 9421 §2.5 says the @signature-params value must match the
        // Signature-Input header value with the label prefix removed. We
        // pass the original bytes through to preserve parameter order and
        // formatting exactly as the signer produced them.
        $eq = strpos($parameters->raw, '=');
        if ($eq === false) {
            throw new InvalidArgumentException(
                'SignatureParameters::$raw is missing the label prefix; cannot build @signature-params.'
            );
        }
        return trim(substr($parameters->raw, $eq + 1));
    }

    private static function authorityFromUri(string $uri): string
    {
        $host = self::uriHost($uri);
        if ($host === null) {
            throw new InvalidArgumentException("Signature base needs @authority but target URI has no host: {$uri}");
        }
        $authority = strtolower($host);
        $port = self::uriPort($uri);
        $scheme = strtolower(self::uriScheme($uri) ?? '');
        if ($port !== null && !self::isDefaultPort($scheme, $port)) {
            $authority .= ':' . $port;
        }
        return $authority;
    }

    private static function isDefaultPort(string $scheme, int $port): bool
    {
        return ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
    }

    private static function queryComponent(string $uri): string
    {
        $query = self::uriQuery($uri);
        return $query === null || $query === '' ? '?' : '?' . $query;
    }

    private static function requestTarget(string $uri): string
    {
        $path = self::uriPath($uri);
        $query = self::uriQuery($uri);
        return $query === null || $query === '' ? $path : $path . '?' . $query;
    }

    private static function uriScheme(string $uri): ?string
    {
        $value = parse_url($uri, PHP_URL_SCHEME);
        return is_string($value) ? $value : null;
    }

    private static function uriHost(string $uri): ?string
    {
        $value = parse_url($uri, PHP_URL_HOST);
        return is_string($value) ? $value : null;
    }

    private static function uriPort(string $uri): ?int
    {
        $value = parse_url($uri, PHP_URL_PORT);
        return is_int($value) ? $value : null;
    }

    /** Per RFC 9421 §2.2, an empty or missing path is canonicalised to "/". */
    private static function uriPath(string $uri): string
    {
        $value = parse_url($uri, PHP_URL_PATH);
        return is_string($value) && $value !== '' ? $value : '/';
    }

    private static function uriQuery(string $uri): ?string
    {
        $value = parse_url($uri, PHP_URL_QUERY);
        return is_string($value) ? $value : null;
    }

    /** @param array<string, string> $headers */
    private static function header(array $headers, string $name): ?string
    {
        $lower = strtolower($name);
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === $lower) {
                return $value;
            }
        }
        return null;
    }

    private static function unquote(string $value): string
    {
        $trim = trim($value);
        if (strlen($trim) >= 2 && $trim[0] === '"' && $trim[-1] === '"') {
            return substr($trim, 1, -1);
        }
        return $trim;
    }
}
