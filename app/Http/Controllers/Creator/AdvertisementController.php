<?php

namespace App\Http\Controllers\Creator;

use App\Enums\ApprovalStatus;
use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Advertisement\StoreAdvertisementRequest;
use App\Http\Requests\Advertisement\UpdateAdvertisementRequest;
use App\Models\Advertisement;
use App\Models\User;
use App\Notifications\ContentSubmittedForReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AdvertisementController extends Controller
{
    public function index(Request $request): View
    {
        $advertisements = Advertisement::ownedBy($request->user()->id)->latest()->get();

        return view('creator.advertisements.index', ['advertisements' => $advertisements]);
    }

    public function create(): View
    {
        return view('creator.advertisements.create');
    }

    public function store(StoreAdvertisementRequest $request): RedirectResponse
    {
        $advertisement = new Advertisement($request->validated());
        $advertisement->created_by = $request->user()->id;
        $advertisement->status = ContentStatus::Draft;
        $advertisement->save();

        return redirect()->route('creator.advertisements.index')->with('status', 'Advertisement created as a draft.');
    }

    public function edit(Advertisement $advertisement): View
    {
        $this->authorize('update', $advertisement);

        return view('creator.advertisements.edit', ['advertisement' => $advertisement]);
    }

    public function update(UpdateAdvertisementRequest $request, Advertisement $advertisement): RedirectResponse
    {
        $this->authorize('update', $advertisement);

        $advertisement->update($request->validated());

        return redirect()->route('creator.advertisements.index')->with('status', 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement): RedirectResponse
    {
        $this->authorize('delete', $advertisement);

        $advertisement->delete();

        return redirect()->route('creator.advertisements.index')->with('status', 'Advertisement deleted.');
    }

    public function submit(Request $request, Advertisement $advertisement): RedirectResponse
    {
        $this->authorize('update', $advertisement);

        abort_unless($advertisement->status->canSubmit(), 422, 'This advertisement cannot be submitted from its current status.');

        // An ad marked "pending" must always have a matching pending
        // approval record — the admin queue is built by querying that
        // record, so a partial write here would silently hide the ad from
        // reviewers.
        DB::transaction(function () use ($request, $advertisement) {
            $advertisement->update(['status' => ContentStatus::Pending]);

            $advertisement->approvals()->create([
                'requested_by' => $request->user()->id,
                'status' => ApprovalStatus::Pending,
            ]);
        });

        $reviewers = User::whereIn('role', [Role::Admin, Role::SuperAdmin])->get();
        Notification::send($reviewers, new ContentSubmittedForReview('advertisement', $advertisement->title, $request->user()->name));

        return redirect()->route('creator.advertisements.index')->with('status', 'Advertisement submitted for review.');
    }
}
