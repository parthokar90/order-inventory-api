<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Register customer profile and assign roles
     */
    public function registerCustomer(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('customer');

            $this->userRepository->createCustomerProfile($user, [
                'phone'            => $data['phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'billing_address'  => $data['billing_address'] ?? null,
                'city'             => $data['city'] ?? null,
            ]);

            $user->load('customerProfile');
            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user'  => $user,
                'token' => $token,
            ];
        });
    }

    /**
     * Authenticate user credentials and return auth token
     */
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            throw new Exception('Your account is currently inactive. Please contact support.', 403);
        }

        $user->load('customerProfile');
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout and revoke current token
     */
    public function logout(User $user): bool
    {
        return $user->currentAccessToken()->delete();
    }
}
