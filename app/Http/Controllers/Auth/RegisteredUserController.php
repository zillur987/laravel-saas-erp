<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $subdomain = $this->generateUniqueSubdomain($request->company_name);

            $tenant = Tenant::create([
                'name' => $request->company_name,
                'subdomain' => $subdomain,
                'plan' => 'free',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // Bind the freshly created tenant so role assignment (which may
            // itself be tenant-aware later) works correctly, and so any
            // afterCommit hooks see the right tenant context.
            app()->instance('tenant', $tenant);

            $user->assignRole('owner');

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Turn "Acme Corp" into "acme-corp", and if taken, "acme-corp-2", etc.
     */
    private function generateUniqueSubdomain(string $companyName): string
    {
        $base = Str::slug($companyName);
        $subdomain = $base;
        $counter = 2;

        while (Tenant::where('subdomain', $subdomain)->exists()) {
            $subdomain = "{$base}-{$counter}";
            $counter++;
        }

        return $subdomain;
    }
}