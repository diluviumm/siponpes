<?php

namespace App\Http\Controllers;

class StaticPageController extends Controller
{
    public function privacy()
    {
        return view('privacy');
    }

    public function terms()
    {
        return view('terms');
    }
}
