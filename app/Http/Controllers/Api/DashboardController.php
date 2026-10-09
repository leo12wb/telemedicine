<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    /** GET /api/v1/dashboard */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = match ($user->role->value) {
            'admin' => $this->dashboardService->adminDashboard(),
            'medico' => $this->dashboardService->doctorDashboard($user),
            'paciente' => $this->dashboardService->patientDashboard($user),
        };

        return response()->json(['data' => $data]);
    }
}
