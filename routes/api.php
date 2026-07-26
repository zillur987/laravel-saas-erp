<?php
declare(strict_types=1);
 
use App\Enums\PermissionEnum;
use App\Http\Controllers\Api\V1\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserAccessController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PostController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('api')->prefix('v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);

        Route::get('/debug-user', function(Request $request) { //debugging purpose
            return [
                'user' => $request->user(),
                'roles' => $request->user()->roles->pluck('name'),
                'permissions' => $request->user()->getPermissionsViaRoles()
            ];
        });
        Route::apiResource('/posts', PostController::class);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // ---- RBAC management ----------------------------------------------
        Route::get('/roles/permission-matrix', [RoleController::class, 'permissionMatrix']);
        Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
        Route::get('/permissions', [PermissionController::class, 'index']);

        Route::post('/users/{user}/roles', [UserAccessController::class, 'syncRoles']);
        Route::post('/users/{user}/permissions', [UserAccessController::class, 'syncDirectPermissions']);

        // ---- Example: real ERP modules protected by permission ------------
        // Route-level middleware is the first line of defense; the frontend
        // v-can directive/composable only hides UI, it never replaces this.
        Route::middleware('permission:' . PermissionEnum::INVENTORY_VIEW->value)->group(function () {
            // Route::get('/inventory/products', [ProductController::class, 'index']);
        });

        Route::middleware('permission:' . PermissionEnum::INVENTORY_CREATE->value)->group(function () {
            // Route::post('/inventory/products', [ProductController::class, 'store']);
        });

        Route::middleware('permission:' . PermissionEnum::SALES_APPROVE->value)->group(function () {
            // Route::post('/sales/orders/{order}/approve', [SalesOrderController::class, 'approve']);
        });
    });


});
