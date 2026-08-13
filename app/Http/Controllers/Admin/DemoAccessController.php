<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\DemoAccess;
use App\Models\Script;
use App\Models\User;
use App\Notifications\DemoAccessGranted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoAccessController extends Controller
{
    public function index(): View
    {
        $grants = DemoAccess::with(['user', 'grantedBy', 'resource'])->latest()->get();

        return view('admin.demo-access.index', ['grants' => $grants]);
    }

    public function create(): View
    {
        return view('admin.demo-access.create', [
            'users' => User::where('role', Role::User)->orderBy('name')->get(),
            'courses' => Course::approved()->orderBy('title')->get(),
            'scripts' => Script::approved()->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'resource' => ['required', 'string', 'regex:/^(course|script):\d+$/'],
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $user = User::where('role', Role::User)->findOrFail($validated['user_id']);

        [$type, $id] = explode(':', $validated['resource'], 2);
        $resourceClass = match ($type) {
            'course' => Course::class,
            'script' => Script::class,
        };
        $resource = $resourceClass::where('status', ContentStatus::Approved)->findOrFail($id);

        $alreadyActive = DemoAccess::where('user_id', $user->id)
            ->where('resource_type', $resourceClass)
            ->where('resource_id', $resource->id)
            ->active()
            ->exists();

        abort_if($alreadyActive, 422, 'This user already has active demo access to this content.');

        $expiresAt = now()->addDays((int) $validated['days']);

        DemoAccess::create([
            'user_id' => $user->id,
            'resource_type' => $resourceClass,
            'resource_id' => $resource->id,
            'granted_by' => $request->user()->id,
            'expires_at' => $expiresAt,
        ]);

        $url = $type === 'course'
            ? route('user.courses.show', $resource)
            : route('user.scripts.show', $resource);

        $user->notify(new DemoAccessGranted($resource->title, $url, $expiresAt));

        return redirect()->route('admin.demo-access.index')
            ->with('status', "Demo access to \"{$resource->title}\" granted to {$user->name}.");
    }

    public function revoke(DemoAccess $demoAccess): RedirectResponse
    {
        abort_unless($demoAccess->expires_at->isFuture(), 422, 'This demo access has already expired.');

        $demoAccess->update(['expires_at' => now()]);

        return back()->with('status', 'Demo access revoked.');
    }
}
