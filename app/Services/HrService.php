<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Support\DocumentNumber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HrService
{
    public function paginateEmployees(?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return Employee::query()
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function createEmployee(array $data): Employee
    {
        $data['employee_number'] = $data['employee_number']
            ?? DocumentNumber::next('EMP', new Employee, 'employee_number');

        return Employee::create($data);
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->refresh();
    }

    public function deleteEmployee(Employee $employee): void
    {
        $employee->delete();
    }

    public function createLeave(array $data): LeaveRequest
    {
        return LeaveRequest::create($data + ['status' => 'pending']);
    }

    public function approveLeave(LeaveRequest $leave): LeaveRequest
    {
        $leave->update(['status' => 'approved']);

        return $leave->refresh()->load('employee');
    }

    public function processPayroll(string $period): PayrollRun
    {
        $total = (float) Employee::query()->sum('salary');

        return PayrollRun::create([
            'period' => $period,
            'status' => 'processed',
            'total_amount' => $total,
            'processed_at' => now(),
        ]);
    }
}
