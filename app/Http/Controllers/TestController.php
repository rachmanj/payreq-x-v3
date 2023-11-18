<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $test = app(UserRealizationController::class)->ongoing_realizations();

        return $test;
    }
}
