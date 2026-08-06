<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    //

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function profile()
    {

        $profile = Auth::user();
        return view('admin.profile', compact('profile'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        // $request->validate([
        //     'name' => 'required|string|max:255',
        //     'address' => 'nullable|string|max:255',
        //     'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        //     'phone' => 'nullable|string|max:255',
        //     'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
        // ]);
        $user->name = $request->name;
        $user->address = $request->address;
        $user->phone = $request->phone;
        // $user->email = $request->email;

        $oldPhoto = $user->photo; // Store the old photo name

        if ($request->hasFile('photo')) {
            $imageName = time() . '.' . $request->photo->extension();
            $request->photo->move(public_path('uploads/user_images'), $imageName);
            $user->photo = $imageName;

            // Delete the old photo if it exists
            if ($oldPhoto && file_exists(public_path('uploads/user_images/' . $oldPhoto))) {
                unlink(public_path('uploads/user_images/' . $oldPhoto));
            }
        }

        $user->save();

        $notification = array(
            'message' => 'Profile updated successfully.',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!\Hash::check($request->old_password, $user->password)) {
            $notification = array(
                'message' => 'The provided password does not match your current password.',
                'alert-type' => 'error'
            );
            return redirect()->back()->with($notification);
        }

        $user->password = \Hash::make($request->new_password);
        $user->save();

        $notification = array(
            'message' => 'Password changed successfully.',
            'alert-type' => 'success'
        );

        return redirect()->back()->with($notification);
    }
}
