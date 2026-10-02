<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\Api\V1\Auth\LoginRequest;

use App\Http\Requests\Api\V1\Auth\RegisterRequest;

use App\Http\Resources\Api\V1\UserResource;

use App\Services\AuthService;

use App\Traits\ApiResponseTrait;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;

use Exception;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Register Customer
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->registerCustomer($request->validated());

            return $this->successResponse([
                'access_token' => $result['token'],
                'token_type'   => 'Bearer',
                'user'         => new UserResource($result['user']),
            ], 'Registration successful', 201);

        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Login User
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->successResponse([
                'access_token' => $result['token'],
                'token_type'   => 'Bearer',
                'user'         => new UserResource($result['user']),
            ], 'Login successful', 200);

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }

    /**
     * Authenticated User Info
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('customerProfile');

            return $this->successResponse(
                new UserResource($user),
                'User profile retrieved successfully',
                200
            );
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch user profile', 500);
        }
    }

    /**
     * Logout User
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return $this->successResponse(null, 'Successfully logged out', 200);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to logout', 500);
        }
    }
}