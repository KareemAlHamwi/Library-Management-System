<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{

    public function verify(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'Invalid or expired verification link',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ]);
        }


        if ($user->pending_email) {
            $user->email = $user->pending_email;
            $user->pending_email = null;
        }

        $user->markEmailAsVerified();
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }















    // public function verify(Request $request, $id, $hash)
    // {
    //     $user = User::findOrFail($id);

    //     if (! $request->hasValidSignature()) {
    //         return response()->json([
    //             'message' => 'Invalid or expired verification link',
    //         ], 403);
    //     }

    //     if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
    //         return response()->json([
    //             'message' => 'Invalid verification hash',
    //         ], 403);
    //     }

    //     if ($user->hasVerifiedEmail()) {
    //         return response()->json([
    //             'message' => 'Email already verified',
    //         ]);
    //     }
    //     if ($user->pending_email) {
    //         $user->email = $user->pending_email;
    //         $user->pending_email = null;
    //     }

    //     $user->markEmailAsVerified();
    //     $user->save();

    //     event(new \Illuminate\Auth\Events\Verified($user));

    //     return response()->json([
    //         'message' => 'Email verified and updated successfully'
    //     ]);
    //     // $user->markEmailAsVerified();
    //     // event(new Verified($user));

    //     // return response()->json([
    //     //     'message' => 'Email verified successfully',
    //     // ]);
    // }






    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(
                config('app.frontend_url') . '/dashboard?verified=1'
            );
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(
            config('app.frontend_url') . '/dashboard?verified=1'
        );
    }
}
