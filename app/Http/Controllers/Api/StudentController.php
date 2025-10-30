<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CaseerAcademyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    private CaseerAcademyService $caseerService;

    public function __construct(CaseerAcademyService $caseerService)
    {
        $this->caseerService = $caseerService;
    }

    /**
     * Get the last 10 students
     *
     * GET /api/students
     */
    public function index(): JsonResponse
    {
        $result = $this->caseerService->getLatestStudents();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'students' => $result['students'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }

    /**
     * Search for students
     *
     * GET /api/students/search?query=john
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $result = $this->caseerService->searchStudent($validated['query']);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'students' => $result['students'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }

    /**
     * Create a new student
     *
     * POST /api/students
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $result = $this->caseerService->createStudent($validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'student_id' => $result['student_id'],
                'message' => $result['message'],
            ], 201);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }

    /**
     * Get student details
     *
     * GET /api/students/{id}
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->caseerService->getStudentDetails($id);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'student' => $result['student'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 404);
    }

    /**
     * Update student password
     *
     * POST /api/students/{id}/password
     */
    public function updatePassword(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        $result = $this->caseerService->changePassword($id, $validated['new_password']);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 500);
    }
}









