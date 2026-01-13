<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SignUpController extends Controller
{
    public function create(Request $request)
    {
        if ($request->has('phone_number')) {
            $phone = $request->phone_number;
            // Store phone number in a cookie (valid 1 day)
            return response()
                ->view('sign-up')
                ->cookie('signup_phone', $phone, 60 * 24);
        }

        return view('sign-up');
        // return view('sign-up');
    }
}
