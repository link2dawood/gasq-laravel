<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorProfileController extends Controller
{
    public function show(Request $request, User $user): View
    {
        if ($user->user_type !== 'vendor') {
            abort(404);
        }
        $user->load(['vendorProfile', 'vendorCapability']);
        return view('vendor-profile.show', ['vendor' => $user]);
    }
}
