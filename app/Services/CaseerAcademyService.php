<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CaseerAcademyService
{
    private string $apiUrl;

    private string $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('services.caseer_academy.api_url', 'https://caseer.academy/wp-json/my-app/v1');
        $this->secretKey = config('services.caseer_academy.api_secret', 'C@533r3c');
    }

    /**
     * Get the latest 10 students
     */
    public function getLatestStudents(): array
    {
        try {
            $response = $this->makeRequest('GET', '/admin/latest-students');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'students' => $response->json(),
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to fetch students');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to get latest students', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Search for a student by name, email, or phone
     */
    public function searchStudent(string $query): array
    {
        try {
            $response = $this->makeRequest('GET', '/admin/search-student', [
                'query' => $query,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'students' => $response->json(),
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to search students');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to search student', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Create a new student
     */
    public function createStudent(array $data): array
    {
        try {
            $response = $this->makeRequest('POST', '/user/create', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'student_id' => $response->json('user_id'),
                    'message' => $response->json('message'),
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to create student');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to create student', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Get student details including enrolled courses and order history
     */
    public function getStudentDetails(int $studentId): array
    {
        try {
            $response = $this->makeRequest('GET', "/admin/student/{$studentId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'student' => $response->json(),
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to get student details');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to get student details', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Change student password
     */
    public function changePassword(int $studentId, string $newPassword): array
    {
        try {
            $response = $this->makeRequest('POST', '/admin/change-password', [
                'user_id' => $studentId,
                'new_password' => $newPassword,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message') ?? 'Password changed successfully',
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to change password');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to change password', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Reset password by email
     */
    public function resetPasswordByEmail(string $email, string $newPassword): array
    {
        try {
            Log::info('Caseer API: Attempting to reset password', [
                'email' => $email,
                'endpoint' => '/admin/reset-password',
            ]);

            $response = $this->makeRequest('POST', '/admin/reset-password', [
                'email' => $email,
                'new_password' => $newPassword,
            ]);

            Log::info('Caseer API: Reset password response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => $response->json('message') ?? "Password for {$email} has been updated successfully.",
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to reset password');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to reset password', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Search users by username, email, or display name
     */
    public function searchUsers(string $searchTerm): array
    {
        try {
            $response = $this->makeRequest('GET', '/admin/search-users', [
                'term' => $searchTerm,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'users' => $response->json(),
                ];
            }

            return $this->handleErrorResponse($response, 'Failed to search users');
        } catch (\Exception $e) {
            Log::error('Caseer API: Failed to search users', [
                'search_term' => $searchTerm,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('GET', '/admin/latest-students');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to Caseer Academy API',
                ];
            }

            return $this->handleErrorResponse($response, 'Connection failed');
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'is_auth_error' => $this->isAuthenticationError($e),
            ];
        }
    }

    /**
     * Make HTTP request to Caseer Academy API
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): Response
    {
        $url = $this->apiUrl.$endpoint;

        $request = Http::withHeaders([
            'X-Secret-Key' => $this->secretKey,
            'Accept' => 'application/json',
        ])->timeout(30);

        if ($method === 'GET') {
            return $request->get($url, $data);
        }

        return $request->post($url, $data);
    }

    /**
     * Handle error responses from API
     */
    private function handleErrorResponse(Response $response, string $defaultMessage): array
    {
        $error = $response->json('message') ?? $defaultMessage;
        $status = $response->status();

        return [
            'success' => false,
            'error' => $error,
            'status' => $status,
            'is_auth_error' => in_array($status, [401, 403]),
        ];
    }

    /**
     * Check if exception is an authentication error
     */
    private function isAuthenticationError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'unauthorized') ||
               str_contains($message, 'forbidden') ||
               str_contains($message, 'not allowed') ||
               str_contains($message, '401') ||
               str_contains($message, '403');
    }
}
