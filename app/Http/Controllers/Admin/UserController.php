<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'canManageSuperAdmins' => $request->user()->role === Role::SuperAdmin,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.create', ['assignableRoles' => $this->assignableRoles($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_map(fn (Role $role) => $role->value, $this->assignableRoles($request)))],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->authorizeTarget($request, $user);

        return view('admin.users.edit', [
            'user' => $user,
            'assignableRoles' => $this->assignableRoles($request),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(array_map(fn (Role $role) => $role->value, $this->assignableRoles($request)))],
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeTarget($request, $user);

        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    /**
     * Only SuperAdmin can create/edit/delete SuperAdmin accounts. Since this
     * always runs before assignableRoles() in edit/update, a target that
     * reaches assignableRoles() is either non-SuperAdmin or being viewed by
     * a SuperAdmin — so assignableRoles() only needs to check the viewer.
     */
    private function authorizeTarget(Request $request, User $target): void
    {
        abort_if(
            $target->role === Role::SuperAdmin && $request->user()->role !== Role::SuperAdmin,
            403
        );
    }

    /**
     * @return array<int, Role>
     */
    private function assignableRoles(Request $request): array
    {
        if ($request->user()->role === Role::SuperAdmin) {
            return Role::cases();
        }

        return array_values(array_filter(Role::cases(), fn (Role $role) => $role !== Role::SuperAdmin));
    }
}
