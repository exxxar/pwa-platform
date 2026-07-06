<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicLandingController extends Controller
{
    public function index(Request $request)
    {
        return view('public.landing', [
            'requestedSlug' => session('requested_slug'),
            'year' => date('Y'),
            'appName' => config('app.name', 'PWA Platform'),
        ]);
    }
}
