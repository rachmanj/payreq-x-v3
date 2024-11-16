<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DashboardLinks extends Component
{
    public $page;
    public $status;

    public function __construct($page, $status)
    {
        $this->page = $page;
        $this->status = $status;
    }

    public function render()
    {
        return view('components.dashboard-links');
    }
}
