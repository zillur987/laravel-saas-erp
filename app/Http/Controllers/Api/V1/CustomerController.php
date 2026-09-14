<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:'.PermissionEnum::CRM_VIEW->value)->only(['index', 'show']);
        $this->middleware('permission:'.PermissionEnum::CRM_CREATE->value)->only('store');
        $this->middleware('permission:'.PermissionEnum::CRM_UPDATE->value)->only('update');
        $this->middleware('permission:'.PermissionEnum::CRM_DELETE->value)->only('destroy');
    }

    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->when($request->string('search')->value(), fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json(['data' => $customer]);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = Customer::create($request->validate($this->rules()));

        return response()->json(['message' => 'Customer created.', 'data' => $customer], 201);
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validate($this->rules(false)));

        return response()->json(['message' => 'Customer updated.', 'data' => $customer->refresh()]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $creating = true): array
    {
        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:lead,active,inactive'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
