<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class VatLinks extends Component
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
        return view('components.vat-links');
    }
}
