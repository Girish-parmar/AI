<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        // Whether or not the email is registered, the response is identical
        // so this endpoint can't be used to discover which emails have accounts.
        if (in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            return back()->with('status', 'If that email is registered, a password reset link has been sent.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => 'Please wait a minute before requesting another reset link.']);
        }

        return back()->withErrors(['email' => 'Something went wrong sending the reset link. Please try again.']);
    }
}
