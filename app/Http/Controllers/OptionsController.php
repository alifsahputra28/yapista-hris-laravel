<?php

namespace App\Http\Controllers;

use App\Support\OrganizationOptions;
use Illuminate\View\View;

class OptionsController extends Controller
{
    public function __invoke(): View
    {
        return view('options.index', [
            'unitLevels' => OrganizationOptions::UNIT_LEVELS,
            'positionTypes' => OrganizationOptions::POSITION_TYPES,
        ]);
    }
}
