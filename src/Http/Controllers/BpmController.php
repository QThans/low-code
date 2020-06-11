<?php

namespace Thans\Bpm\Http\Controllers;

use Dcat\Admin\Layout\Content;
use Illuminate\Routing\Controller;

class BpmController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('Title')
            ->description('Description')
            ->body(view('bpm::index'));
    }
}
