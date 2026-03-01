<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Jwt;
use PDO;

/**
 * AuthApiController — JWT-based authentication for the REST API.
 *
 * Endpoints:
 *   POST /auth/login    — Authenticate with email + password, receive access + refresh tokens.
 *   POST /auth/refresh  — Exchange a refresh token for a new access + refresh token pair.
 *   POST /auth/logout   — Revoke the current refresh token.
 */
class AuthApiController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * POST /auth/login
     */
    public function login(Request $request): void
    {
        $email    = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Email and password are required.',
            ], 422);
        }

        // Find staff by email globally (joins with stores to get store info).
        $stmt = $this->pdo->prepare(
            'SELECT st.id AS staff_id, st.store_id, st.name AS staff_name, st.email,
                    st.password_hash, st.role_id, st.status AS staff_status,
                    s.name AS store_name, s.status AS store_status
             FROM staff st
             JOIN stores s ON s.id = st.store_id
             WHERE st.email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff === false || !password_verify($password, $staff['password_hash'])) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Invalid email or password.',
            ], 401);
        }

        if ($staff['store_status'] !== 'active') {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Store is not active. Please verify your email first.',
            ], 403);
        }

        if ($staff['staff_status'] !== 'active') {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Your account has been deactivated.',
            ], 403);
        }

        // Generate tokens.
        $storeId = (int) $staff['store_id'];
        $staffId = (int) $staff['staff_id'];
        $roleId  = (int) $staff['role_id'];

        $accessToken = Jwt::generateAccessToken($storeId, $staffId, $roleId);
        $refreshToken = Jwt::generateRefreshTokenString();

        // Store refresh token in DB.
        $this->storeRefreshToken($storeId, $staffId, $refreshToken);

        Response::json([
            'success' => true,
            'data'    => [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type'    => 'Bearer',
                'expires_in'    => defined('JWT_ACCESS_TTL') ? (int) JWT_ACCESS_TTL : 900,
                'user'          => [
                    'id'         => $staffId,
                    'name'       => $staff['staff_name'],
                    'email'      => $staff['email'],
                    'store_id'   => $storeId,
                    'store_name' => $staff['store_name'],
                    'role_id'    => $roleId,
                ],
            ],
            'meta'  => null,
            'error' => null,
        ]);
    }

    /**
     * POST /auth/refresh
     */
    public function refresh(Request $request): void
    {
        $refreshToken = trim((string) $request->input('refresh_token', ''));

        if ($refreshToken === '') {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Refresh token is required.',
            ], 422);
        }

        $tokenHash = hash('sha256', $refreshToken);

        // Find the refresh token in DB.
        $stmt = $this->pdo->prepare(
            'SELECT rt.id, rt.store_id, rt.staff_id, rt.expires_at, rt.revoked,
                    st.role_id, st.status AS staff_status,
                    s.status AS store_status
             FROM refresh_tokens rt
             JOIN staff st ON st.id = rt.staff_id
             JOIN stores s ON s.id = rt.store_id
             WHERE rt.token_hash = ?
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record === false) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Invalid refresh token.',
            ], 401);
        }

        if ((int) $record['revoked'] === 1) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Refresh token has been revoked.',
            ], 401);
        }

        if (strtotime($record['expires_at']) < time()) {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Refresh token has expired.',
            ], 401);
        }

        if ($record['store_status'] !== 'active' || $record['staff_status'] !== 'active') {
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Account or store is not active.',
            ], 403);
        }

        $storeId = (int) $record['store_id'];
        $staffId = (int) $record['staff_id'];
        $roleId  = (int) $record['role_id'];

        // Rotate: revoke old token, issue new pair.
        $this->pdo->beginTransaction();
        try {
            // Mark old token as used and revoked.
            $revokeStmt = $this->pdo->prepare(
                'UPDATE refresh_tokens SET revoked = 1, used_at = NOW() WHERE id = ?'
            );
            $revokeStmt->execute([$record['id']]);

            // Generate new tokens.
            $newAccessToken = Jwt::generateAccessToken($storeId, $staffId, $roleId);
            $newRefreshToken = Jwt::generateRefreshTokenString();
            $this->storeRefreshToken($storeId, $staffId, $newRefreshToken);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            error_log('[AuthApiController] refresh failed: ' . $e->getMessage());
            Response::json([
                'success' => false,
                'data'    => null,
                'meta'    => null,
                'error'   => 'Failed to refresh token. Please try again.',
            ], 500);
        }

        Response::json([
            'success' => true,
            'data'    => [
                'access_token'  => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type'    => 'Bearer',
                'expires_in'    => defined('JWT_ACCESS_TTL') ? (int) JWT_ACCESS_TTL : 900,
            ],
            'meta'  => null,
            'error' => null,
        ]);
    }

    /**
     * POST /auth/logout — Revoke refresh token.
     */
    public function logout(Request $request): void
    {
        $refreshToken = trim((string) $request->input('refresh_token', ''));

        if ($refreshToken !== '') {
            $tokenHash = hash('sha256', $refreshToken);
            $stmt = $this->pdo->prepare(
                'UPDATE refresh_tokens SET revoked = 1 WHERE token_hash = ?'
            );
            $stmt->execute([$tokenHash]);
        }

        Response::json([
            'success' => true,
            'data'    => ['message' => 'Logged out successfully.'],
            'meta'    => null,
            'error'   => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function storeRefreshToken(int $storeId, int $staffId, string $rawToken): void
    {
        $tokenHash = hash('sha256', $rawToken);
        $refreshTtl = defined('JWT_REFRESH_TTL') ? (int) JWT_REFRESH_TTL : 2592000; // 30 days
        $expiresAt = date('Y-m-d H:i:s', time() + $refreshTtl);

        $stmt = $this->pdo->prepare(
            'INSERT INTO refresh_tokens (store_id, staff_id, token_hash, expires_at)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$storeId, $staffId, $tokenHash, $expiresAt]);
    }
}
