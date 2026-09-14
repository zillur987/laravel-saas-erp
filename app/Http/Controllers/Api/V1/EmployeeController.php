<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\HrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly HrService $hrService)
    {
        $this->middleware('permission:'.PermissionEnum::HR_VIEW->value)->only(['index', 'show', 'leaves']);
        $this->middleware('permission:'.PermissionEnum::HR_CREATE->value)->only(['store', 'storeLeave']);
        $this->middleware('permission:'.PermissionEnum::HR_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::HR_DELETE->value)->only('destroy');
        $this->middleware('permission:'.PermissionEnum::HR_LEAVE_APPROVE->value)->only('approveLeave');
        $this->middleware('permission:'.PermissionEnum::HR_PAYROLL_PROCESS->value)->only('processPayroll');
    }

    public function index(Request $request): JsonResponse
    {
        $employees = $this->hrService->paginateEmployees($request->string('search')->value() ?: null);

        return response()->json([
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $employee = $this->hrService->createEmployee($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'hired_at' => ['nullable', 'date'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]));

        return response()->json(['message' => 'Employee created.', 'data' => $employee], 201);
    }

    public function update(Request $request, Employee $employee): JsonResponse
    {
        $employee = $this->hrService->updateEmployee($employee, $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'hired_at' => ['nullable', 'date'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
        ]));

        return response()->json(['data' => $employee]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->hrService->deleteEmployee($employee);

        return response()->json(['message' => 'Employee deleted.']);
    }

    public function leaves(): JsonResponse
    {
        return response()->json([
            'data' => LeaveRequest::query()->with('employee:id,name')->latest()->get(),
        ]);
    }

    public function storeLeave(Request $request): JsonResponse
    {
        $leave = $this->hrService->createLeave($request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'type' => ['sometimes', 'string', 'max:50'],
            'reason' => ['nullable', 'string'],
        ]));

        return response()->json(['data' => $leave], 201);
    }

    public function approveLeave(LeaveRequest $leaveRequest): JsonResponse
    {
        return response()->json([
            'message' => 'Leave approved.',
            'data' => $this->hrService->approveLeave($leaveRequest),
        ]);
    }

    public function processPayroll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'max:20'],
        ]);

        return response()->json([
            'message' => 'Payroll processed.',
            'data' => $this->hrService->processPayroll($validated['period']),
        ], 201);
    }
}
