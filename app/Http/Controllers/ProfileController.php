<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{

    public function store(StoreProfileRequest $request)
    {
        $user = Auth::user();

        if ($user->profile) {
            return response()->json([
                'message' => 'User already has a profile.'
            ], 409);
        }
        $validated = $request->validated();
        $validated['user_id'] = $user->id;

        // if ($request->hasFile('image')) {

        //     $path = $request->file('image')->store('my photo', 'public');
        //     $validated['image'] = $path;
        // }
        $profile = Profile::create($validated);
        return response()->json([
            'message:' => 'profile created successfully.',
            'profile:' => $profile,
        ], 201);
    }


    public function show()
    {
        $user = Auth::user();
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found.'
            ], 404);
        }

        return response()->json([



            'user' => [
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'role' => $profile->user->role,
                'profile' => [

                    'phone' => $profile->phone,
                    'address' => $profile->address,
                    'birthDate' => $profile->date_of_birth,
                    'bio' => $profile->bio,

                ]
            ]
        ], 200);
    }
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found.'
            ], 404);
        }

        $validated = $request->validated();

        //$userData = $request->only('name');//, 'email'

        //$user->update($userData);
        //     if (!empty($validated['email']) && $validated['email'] !== $user->email) {

        //     $user->pending_email = $validated['email'];
        //     $user->email_verified_at = null;
        //     $user->save();

        //     // IMPORTANT: must be after save
        //     $user->sendEmailVerificationNotification();
        // }

        if (!empty($validated['email']) && $validated['email'] !== $user->email) {


            $user->pending_email = $validated['email'];
            $user->email_verified_at = null;


            $user->save();


            //$user->setAttribute('email', $user->pending_email);

            $user->sendEmailVerificationNotification();
        }


        if (!empty($validated['name'])) {
            $user->name = $validated['name'];
            $user->save();
        }

        // if ($request->hasFile('image')) {
        //     $path = $request->file('image')->store('my photo', 'public');
        //     $validated['image'] = $path;
        // }


        //$profile->update($validated);


        $profile->update(collect($validated)->only([
            'bio',
            'phone',
            'address',
            'date_of_birth'
        ])->toArray());

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'name' => $profile->user->name,
                'email' => $profile->user->email,
                'role' => $profile->user->role,
                'profile' => [

                    'phone' => $profile->phone,
                    'address' => $profile->address,
                    'birthDate' => $profile->date_of_birth,
                    'bio' => $profile->bio,

                ]
            ]
        ], 200);
    }




























































    ///////////////////////////////////////////////////////

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);



    /**
     * Update the user's profile information.
     */
    // public function update(ProfileUpdateRequest $request): RedirectResponse
    // {
    //     $request->user()->fill($request->validated());

    //     if ($request->user()->isDirty('email')) {
    //         $request->user()->email_verified_at = null;
    //     }

    //     $request->user()->save();

    //     return Redirect::route('profile.edit')->with('status', 'profile-updated');
    // }








    ///////////////////////////////////////////////////
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
