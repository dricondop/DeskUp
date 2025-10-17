<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/signin', function () {
    return view('signin');
});

Route::post('/signin', function (Request $request): Response|RedirectResponse {
    $credentials = $request->validate([
        'email' => ['required', 'email:rfc'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate(); // Avoids session fixation

        //This rehashes de password if it is needed only (outdated hash) after checking the password is right
        if (Hash::needsRehash(Auth::user()->password)) {
            Auth::user()->forceFill([
            'password' => Hash::make($request->input('password')),
            ])->save();
        }

        return response('Correct sign-in!', 200);
    }
    //It will always be a failed attempt unless credential match exactly with the DB
    return back()
        ->withErrors(['auth' => 'Incorrect email or password'])
        ->withInput(['email' => $request->input('email')]);

})->middleware('throttle:5,1'); 
//The throttle is a security measure to limit the ammount of sing-ins to 5 per minute.