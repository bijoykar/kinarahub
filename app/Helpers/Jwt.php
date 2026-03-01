<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Jwt — Pure-PHP HS256 JSON Web Token implementation.
 *
 * This class intentionally has no external dependencies.  It implements
 * only the HS256 algorithm (HMAC-SHA-256) which is sufficient for the
 * Kinara Store Hub API authentication use-case.
 *
 * Token structure:  base64url(header) . '.' . base64url(payload) . '.' . base64url(signature)
 *
 * Constants consumed (defined in config/app.php or .env):
 *   JWT_SECRET      — HS256 signing key (min 32 chars recommended)
 *   JWT_ACCESS_TTL  — Access token lifetime in seconds (e.g. 900 for 15 min)
 *
 * Refresh tokens are NOT JWTs — they are random opaque strings stored in
 * the refresh_tokens DB table and returned by generateRefreshTokenString().
 *
 * Security notes:
 * - Signatures are verified with hash_equals() to prevent timing attacks.
 * - The 'exp' claim is checked on every decode() call.
 * - The 'alg' header field is validated; the 'none' algorithm is rejected.
 */
class Jwt
{
    // -----------------------------------------------------------------------
    // Core encode / decode
    // -----------------------------------------------------------------------

    /**
     * Encode a payload array into a signed HS256 JWT string.
     *
     * The fixed header `{"alg":"HS256","typ":"JWT"}` is prepended
     * automatically — callers only supply the payload claims.
     *
     * @param array  $payload Key-value claims to embed in the token.
     * @param string $secret  HMAC-SHA-256 signing key.
     *
     * @return string Compact serialised JWT (three base64url segments joined by '.').
     */
    public static function encode(array $payload, string $secret): string
    {
        $headerJson  = json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $headerEncoded  = self::base64UrlEncode($headerJson);
        $payloadEncoded = self::base64UrlEncode($payloadJson);

        $signingInput = $headerEncoded . '.' . $payloadEncoded;
        $signature    = hash_hmac('sha256', $signingInput, $secret, true);

        return $signingInput . '.' . self::base64UrlEncode($signature);
    }

    /**
     * Decode and verify a JWT string.
     *
     * Performs the following checks in order:
     *  1. Token has exactly three segments.
     *  2. Header declares alg = HS256 (rejects 'none' and other algorithms).
     *  3. HMAC signature matches (timing-safe comparison via hash_equals).
     *  4. 'exp' claim is present and has not passed.
     *
     * @param string $token  Compact JWT string to verify.
     * @param string $secret HMAC-SHA-256 signing key.
     *
     * @return array|null Decoded payload array on success, null on any failure.
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // --- 1. Decode and validate header ---
        $headerJson = self::base64UrlDecode($headerEncoded);
        $header     = json_decode($headerJson, true);

        if (!is_array($header)) {
            return null;
        }

        // Reject anything other than HS256 — including the dangerous 'none'.
        if (($header['alg'] ?? '') !== 'HS256' || ($header['typ'] ?? '') !== 'JWT') {
            return null;
        }

        // --- 2. Verify signature (timing-safe) ---
        $signingInput        = $headerEncoded . '.' . $payloadEncoded;
        $expectedRaw         = hash_hmac('sha256', $signingInput, $secret, true);
        $expectedEncoded     = self::base64UrlEncode($expectedRaw);

        if (!hash_equals($expectedEncoded, $signatureEncoded)) {
            return null;
        }

        // --- 3. Decode payload ---
        $payloadJson = self::base64UrlDecode($payloadEncoded);
        $payload     = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        // --- 4. Validate expiry ---
        $exp = $payload['exp'] ?? null;

        if ($exp === null || time() > (int) $exp) {
            return null; // Token expired or has no expiry — reject.
        }

        return $payload;
    }

    // -----------------------------------------------------------------------
    // Token factory helpers
    // -----------------------------------------------------------------------

    /**
     * Generate a signed HS256 access token for an authenticated staff member.
     *
     * The token is short-lived (JWT_ACCESS_TTL seconds) and carries the
     * minimum claims needed to authorise API requests without a DB round-trip:
     *  - sub       : staff/user ID
     *  - store_id  : tenant identifier (never accepted from request body)
     *  - role_id   : RBAC role for PermissionMiddleware
     *  - iat       : issued-at Unix timestamp
     *  - exp       : expiry Unix timestamp
     *  - type      : literal 'access' (distinguishes from hypothetical future token types)
     *
     * @param int $storeId The authenticated store's ID.
     * @param int $staffId The authenticated staff member's user ID.
     * @param int $roleId  The staff member's RBAC role ID.
     *
     * @return string Signed compact JWT access token.
     */
    public static function generateAccessToken(int $storeId, int $staffId, int $roleId): string
    {
        $now = time();
        $ttl = defined('JWT_ACCESS_TTL') ? (int) JWT_ACCESS_TTL : 900; // Default: 15 minutes.

        $payload = [
            'sub'      => $staffId,
            'store_id' => $storeId,
            'role_id'  => $roleId,
            'iat'      => $now,
            'exp'      => $now + $ttl,
            'type'     => 'access',
        ];

        $secret = defined('JWT_SECRET') ? JWT_SECRET : ($_ENV['JWT_SECRET'] ?? '');

        return self::encode($payload, $secret);
    }

    /**
     * Generate a cryptographically random opaque refresh token string.
     *
     * Refresh tokens for Kinara Store Hub are NOT JWTs — they are random
     * 80-character hex strings stored in the `refresh_tokens` database table
     * and looked up on the `POST /api/v1/auth/refresh` endpoint.  This design
     * allows instant revocation by deleting the DB row.
     *
     * @return string 80-character hex string (40 random bytes encoded as hex).
     */
    public static function generateRefreshTokenString(): string
    {
        return bin2hex(random_bytes(40));
    }

    // -----------------------------------------------------------------------
    // Base64URL helpers (RFC 4648 §5)
    // -----------------------------------------------------------------------

    /**
     * Encode binary data using base64url (URL-safe base64 without padding).
     *
     * Substitutions applied to standard base64 output:
     *   '+' → '-'
     *   '/' → '_'
     *   '=' (padding) → removed
     *
     * @param string $data Raw binary string to encode.
     *
     * @return string Base64url-encoded string, safe for use in URLs and JWT segments.
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode a base64url-encoded string back to raw binary.
     *
     * Reverses the substitutions made by base64UrlEncode() and re-adds
     * the '=' padding characters required by PHP's base64_decode().
     *
     * @param string $data Base64url-encoded string (from a JWT segment).
     *
     * @return string Decoded raw binary string.
     */
    public static function base64UrlDecode(string $data): string
    {
        // Restore standard base64 characters.
        $base64 = strtr($data, '-_', '+/');

        // Re-add padding to make the length a multiple of 4.
        $padded = str_pad($base64, (int) (ceil(strlen($base64) / 4) * 4), '=');

        return (string) base64_decode($padded, true);
    }
}
