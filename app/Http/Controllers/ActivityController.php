<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function store($user, $activity_name, $document_number)
    {
        $activity = new Activity();
        $activity->user_id = $user;
        $activity->activity_name = $activity_name;
        $activity->document_number = $document_number;
        $activity->save();
    }
}
