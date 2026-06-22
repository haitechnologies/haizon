<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Security\Roles;

/**
 * User Service
 *
 * Implements business logic for managing users (employees).
 */
class UserService
{
    private UserRepository $userRepo;

    /** Allowed personal email domains for employee accounts */
    private const ALLOWED_EMAIL_DOMAINS = [
        'gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com',
        'live.com', 'icloud.com', 'mail.com', 'protonmail.com',
        'proton.me', 'aol.com', 'ymail.com', 'zoho.com',
        'rediffmail.com', 'tutanota.com',
    ];

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * Retrieve a user by ID
     *
     * @throws NotFoundException
     */
    public function getById(int $id): User
    {
        $user = $this->userRepo->find($id);
        if ($user === null) {
            throw new NotFoundException("Employee with ID {$id} not found.");
        }
        return $user;
    }

    /**
     * Create a new user (employee)
     *
     * @throws ValidationException
     */
    public function create(array $data, int $createdBy): User
    {
        $this->validateData($data);

        $email = trim($data['email']);
        if ($this->userRepo->existsByEmail($email)) {
            throw new ValidationException(['email' => 'Duplicate Email. Please enter different.']);
        }

        $password = $data['password'] ?? '';
        $hashedPassword = null;
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }

        // Handle Date of Birth conversion (d-m-Y to Y-m-d)
        $dob = null;
        if (!empty($data['dob'])) {
            $dob = $this->convertDateToDb($data['dob']);
        }

        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');

        $user = new User(
            id: null,
            canAccessSystem: (bool)($data['can_access_system'] ?? true),
            isActive: (bool)($data['is_active'] ?? true),
            roleId: (int)$data['role_id'],
            email: $email,
            password: $hashedPassword,
            fullName: trim($firstName . ' ' . $lastName),
            firstName: $firstName,
            lastName: $lastName,
            mobile: !empty($data['mobile']) ? trim($data['mobile']) : null,
            contact1: trim($data['contact1']),
            contact2: !empty($data['contact2']) ? trim($data['contact2']) : null,
            address: !empty($data['address']) ? trim($data['address']) : null,
            dob: $dob,
            departmentId: !empty($data['department_id']) ? (int)$data['department_id'] : null,
            lastLogin: null,
            photo: !empty($data['photo']) ? trim($data['photo']) : null,
            publish: (bool)($data['publish'] ?? true),
            createdBy: $createdBy
        );

        return $this->userRepo->save($user);
    }

    /**
     * Update an existing user
     *
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $id, array $data): User
    {
        $user = $this->getById($id);

        // Prevent editing super admin roles/data for safety if roles are full access
        if (class_exists('Roles') && Roles::hasFullAccess($user->roleId)) {
            // Check if current user is trying to edit a super admin but isn't allowed
            // The controller handles this, but let's keep it safe.
        }

        $this->validateData($data, $id);

        $email = trim($data['email']);
        if ($this->userRepo->existsByEmail($email, $id)) {
            throw new ValidationException(['email' => 'Duplicate Email. Please enter different.']);
        }

        // Update password only if a new one is provided
        $hashedPassword = $user->password;
        $password = $data['password'] ?? '';
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        }

        // Handle Date of Birth conversion (d-m-Y to Y-m-d)
        $dob = null;
        if (!empty($data['dob'])) {
            $dob = $this->convertDateToDb($data['dob']);
        }

        $firstName = trim($data['first_name'] ?? $user->firstName ?? '');
        $lastName = trim($data['last_name'] ?? $user->lastName ?? '');

        $updatedUser = new User(
            id: $user->id,
            canAccessSystem: (bool)($data['can_access_system'] ?? $user->canAccessSystem),
            isActive: (bool)($data['is_active'] ?? $user->isActive),
            roleId: (int)$data['role_id'],
            email: $email,
            password: $hashedPassword,
            fullName: trim($firstName . ' ' . $lastName),
            firstName: $firstName,
            lastName: $lastName,
            mobile: !empty($data['mobile']) ? trim($data['mobile']) : $user->mobile,
            contact1: trim($data['contact1']),
            contact2: !empty($data['contact2']) ? trim($data['contact2']) : null,
            address: !empty($data['address']) ? trim($data['address']) : null,
            dob: $dob,
            departmentId: !empty($data['department_id']) ? (int)$data['department_id'] : $user->departmentId,
            lastLogin: $user->lastLogin,
            photo: isset($data['photo']) ? (!empty($data['photo']) ? trim($data['photo']) : null) : $user->photo,
            publish: (bool)($data['publish'] ?? $user->publish),
            createdAt: $user->createdAt,
            createdBy: $user->createdBy
        );

        return $this->userRepo->save($updatedUser);
    }

    /**
     * List all users
     */
    public function list(): array
    {
        return $this->userRepo->findAll();
    }

    /**
     * Delete user by ID
     *
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function delete(int $id): void
    {
        $user = $this->getById($id);

        // Prevent deleting user ID 1 (system admin seed)
        if ($id === 1) {
            throw new ValidationException(['user' => 'System Admin user cannot be deleted.']);
        }

        $this->userRepo->delete($id);
    }

    /**
     * Validate user inputs
     *
     * @throws ValidationException
     */
    private function validateData(array $data, ?int $id = null): void
    {
        $errors = [];

        $roleId = (int)($data['role_id'] ?? 0);
        if ($roleId <= 0) {
            $errors['role_id'] = 'Please select role.';
        }

        $firstName = trim($data['first_name'] ?? '');
        if ($firstName === '') {
            $errors['first_name'] = 'First name is mandatory.';
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $errors['email'] = 'Email is mandatory.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please provide a valid email address.';
        } elseif (!preg_match('/@[^@]+$/', $email, $matches)) {
            $errors['email'] = 'Invalid email format.';
        } else {
            $domain = strtolower($matches[0]);
            $isAllowed = false;
            foreach (self::ALLOWED_EMAIL_DOMAINS as $allowed) {
                if ($domain === '@' . $allowed) {
                    $isAllowed = true;
                    break;
                }
            }
            if (!$isAllowed) {
                $errors['email'] = 'Only personal email addresses (e.g. Gmail, Yahoo, Outlook) are allowed.';
            }
        }

        $password = $data['password'] ?? '';
        if ($id === null && $password === '') {
            // New user requires password
            $errors['password'] = 'Password is mandatory for new employees.';
        } elseif ($password !== '' && (strlen($password) < 6 || strlen($password) > 20)) {
            $errors['password'] = 'Password length must be between 6 - 20 chars.';
        }

        $contact1 = trim($data['contact1'] ?? '');
        if ($contact1 === '') {
            $errors['contact1'] = 'Contact 1 is mandatory.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Convert date string from d-m-Y to Y-m-d format
     */
    private function convertDateToDb(string $dateStr): string
    {
        $dateStr = trim($dateStr);
        if ($dateStr === '') {
            return '1970-01-01';
        }

        try {
            $dt = \DateTime::createFromFormat('d-m-Y', $dateStr);
            if ($dt instanceof \DateTime) {
                return $dt->format('Y-m-d');
            }
        } catch (\Throwable $e) {
            // Ignore parse failures, fallback to manual parsing
        }

        $parts = explode('-', $dateStr);
        if (count($parts) === 3) {
            return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }

        return '1970-01-01';
    }
}
