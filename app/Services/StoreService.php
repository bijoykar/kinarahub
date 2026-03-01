<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Mailer;
use App\Models\StoreModel;

/**
 * StoreService — Business logic for store registration, verification, login, and setup.
 *
 * All validation and orchestration lives here; the controller is thin.
 */
class StoreService
{
    private StoreModel $model;

    public function __construct()
    {
        $this->model = new StoreModel();
    }

    // -----------------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------------

    /**
     * Register a new store.
     *
     * @param array{store_name: string, owner_name: string, email: string, mobile: string, password: string} $data
     * @return array{success: bool, errors: string[], store_id: int|null}
     */
    public function register(array $data): array
    {
        $errors = $this->validateRegistration($data);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors, 'store_id' => null];
        }

        // Check email uniqueness in stores table.
        $existing = $this->model->findByEmail($data['email']);
        if ($existing !== null) {
            return ['success' => false, 'errors' => ['A store with this email already exists.'], 'store_id' => null];
        }

        // Hash password with bcrypt.
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        // Generate a secure verification token (64 hex chars = 32 bytes).
        $verificationToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $storeId = $this->model->createStore([
            'name'                          => $data['store_name'],
            'owner_name'                    => $data['owner_name'],
            'email'                         => $data['email'],
            'mobile'                        => $data['mobile'],
            'password_hash'                 => $passwordHash,
            'verification_token'            => $verificationToken,
            'verification_token_expires_at' => $expiresAt,
        ]);

        // Build verification URL.
        $appUrl = defined('APP_URL') ? APP_URL : ($_ENV['APP_URL'] ?? 'http://localhost/kinarahub');
        $verifyUrl = $appUrl . '/verify/' . $verificationToken;

        // Send verification email (non-blocking — failure is logged, not thrown).
        Mailer::sendVerificationEmail($data['email'], $data['owner_name'], $verifyUrl);

        return ['success' => true, 'errors' => [], 'store_id' => $storeId];
    }

    // -----------------------------------------------------------------------
    // Email verification
    // -----------------------------------------------------------------------

    /**
     * Verify a store's email using the token from the verification link.
     *
     * @return array{success: bool, error: string|null, store_id: int|null, staff_id: int|null, role_id: int|null}
     */
    public function verifyEmail(string $token): array
    {
        $store = $this->model->findByVerificationToken($token);

        if ($store === null) {
            return ['success' => false, 'error' => 'Invalid or expired verification link.', 'store_id' => null, 'staff_id' => null, 'role_id' => null];
        }

        // Check if already active.
        if ($store['status'] === 'active') {
            return ['success' => false, 'error' => 'This account has already been verified.', 'store_id' => null, 'staff_id' => null, 'role_id' => null];
        }

        // Check token expiry.
        if (!empty($store['verification_token_expires_at']) && strtotime($store['verification_token_expires_at']) < time()) {
            return ['success' => false, 'error' => 'Verification link has expired. Please register again.', 'store_id' => null, 'staff_id' => null, 'role_id' => null];
        }

        $pdo = $this->model->getPdo();
        $pdo->beginTransaction();

        try {
            $storeId = (int) $store['id'];

            // 1. Activate the store.
            $this->model->activateStore($storeId);

            // 2. Create Owner role with ALL permissions.
            $roleId = $this->createOwnerRole($storeId);

            // 3. Create the owner as a staff member.
            $staffId = $this->model->createStaff(
                $storeId,
                $store['owner_name'],
                $store['email'],
                $store['mobile'],
                $store['password_hash'],
                $roleId
            );

            // 4. Seed the Walk-in Customer.
            $this->model->seedWalkInCustomer($storeId);

            $pdo->commit();

            return [
                'success'  => true,
                'error'    => null,
                'store_id' => $storeId,
                'staff_id' => $staffId,
                'role_id'  => $roleId,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[StoreService] verifyEmail transaction failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Verification failed. Please try again.', 'store_id' => null, 'staff_id' => null, 'role_id' => null];
        }
    }

    // -----------------------------------------------------------------------
    // Login
    // -----------------------------------------------------------------------

    /**
     * Authenticate a staff member by email and password.
     *
     * @return array{success: bool, error: string|null, staff: array<string, mixed>|null}
     */
    public function login(string $email, string $password): array
    {
        if ($email === '' || $password === '') {
            return ['success' => false, 'error' => 'Email and password are required.', 'staff' => null];
        }

        $staff = $this->model->findStaffByEmailGlobal($email);

        if ($staff === null) {
            return ['success' => false, 'error' => 'Invalid email or password.', 'staff' => null];
        }

        if (!password_verify($password, $staff['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password.', 'staff' => null];
        }

        if ($staff['store_status'] !== 'active') {
            return ['success' => false, 'error' => 'Your store account is not active. Please contact support.', 'staff' => null];
        }

        return ['success' => true, 'error' => null, 'staff' => $staff];
    }

    // -----------------------------------------------------------------------
    // Store setup
    // -----------------------------------------------------------------------

    /**
     * Save store setup data (logo, address).
     *
     * @param int $storeId
     * @param array{address_street?: string, address_city?: string, address_state?: string, address_pincode?: string} $data
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int}|null $logoFile
     * @return array{success: bool, errors: string[]}
     */
    public function saveSetup(int $storeId, array $data, ?array $logoFile): array
    {
        $errors = [];
        $updateData = [];

        // Process address fields.
        foreach (['address_street', 'address_city', 'address_state', 'address_pincode'] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $updateData[$field] = $data[$field];
            }
        }

        // Process logo upload.
        if ($logoFile !== null && $logoFile['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if (!in_array($logoFile['type'], $allowedTypes, true)) {
                $errors[] = 'Logo must be a JPEG, PNG, GIF, or WebP image.';
            } elseif ($logoFile['size'] > $maxSize) {
                $errors[] = 'Logo must be under 2MB.';
            } else {
                $ext = pathinfo($logoFile['name'], PATHINFO_EXTENSION);
                $filename = 'store_' . $storeId . '_logo_' . time() . '.' . $ext;
                $uploadDir = dirname(__DIR__, 2) . '/uploads/logos';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $destination = $uploadDir . '/' . $filename;

                if (move_uploaded_file($logoFile['tmp_name'], $destination)) {
                    $updateData['logo_path'] = '/uploads/logos/' . $filename;
                } else {
                    $errors[] = 'Failed to save logo file.';
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if (!empty($updateData)) {
            $this->model->updateSetup($storeId, $updateData);
        }

        return ['success' => true, 'errors' => []];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Validate registration form data.
     *
     * @return string[] List of validation error messages.
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['store_name']) || strlen(trim($data['store_name'])) < 2) {
            $errors[] = 'Store name is required (minimum 2 characters).';
        }

        if (empty($data['owner_name']) || strlen(trim($data['owner_name'])) < 2) {
            $errors[] = 'Owner name is required (minimum 2 characters).';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (empty($data['mobile']) || !preg_match('/^[0-9]{10,15}$/', $data['mobile'])) {
            $errors[] = 'A valid mobile number is required (10-15 digits).';
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        return $errors;
    }

    /**
     * Create the system Owner role with full permissions for a store.
     *
     * DB ENUM for actions: create, read, update, delete
     * DB ENUM for modules: inventory, sales, customers, reports, settings
     *
     * @return int The Owner role ID.
     */
    private function createOwnerRole(int $storeId): int
    {
        $roleId = $this->model->createRole($storeId, 'Owner', 'Full access to all modules and fields', true);

        $modules = ['inventory', 'sales', 'customers', 'reports', 'settings'];
        $actions = ['create', 'read', 'update', 'delete'];

        $permissions = [];
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $permissions[] = [
                    'module'  => $module,
                    'action'  => $action,
                    'allowed' => 1,
                ];
            }
        }

        $this->model->insertRolePermissions($roleId, $permissions);

        return $roleId;
    }
}
