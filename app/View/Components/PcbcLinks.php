<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PcbcLinks extends Component
{
    public $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    public function render()
    {
        return view('components.pcbc-links');
    }
}
