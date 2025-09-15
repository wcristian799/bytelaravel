<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Models\Admin;
use App\Traits\UploadAble;

class ProfileController extends Controller
{
    use UploadAble;

    /**
     * Profile View.
     *
     * @return void
     */
    public function profile()
    {
        $user = auth()->user();

        return view('backend.profile.index', compact('user'));
    }

    /**
     * Profile Setting.
     *
     * @return void
     */
    public function setting()
    {
        $user = Admin::find(auth()->id());

        return view('backend.profile.setting', compact('user'));
    }

    /**
     * Profile Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile_update(ProfileRequest $request)
    {
        $data = $request->only(['name', 'email']);
        $user = Admin::find(auth()->id());

        if ($request->hasFile('image')) {
            $data['image'] = uploadImage($request->image, 'user');

            deleteFile($user->image);
        }
        if ($request->isPasswordChange == 1) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return back()->with('success', __('profile_update_successfully'));
    }
}
