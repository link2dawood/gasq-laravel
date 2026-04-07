<?php

namespace App\Http\Controllers;

use App\Services\MasterInputsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MasterInputsController extends Controller
{
    public function __construct(
        private MasterInputsService $masterInputs,
    ) {}

    public function index(Request $request): View
    {
        $profile = $this->masterInputs->getOrCreate($request->user());

        return view('pages.master-inputs', [
            'inputs' => $this->masterInputs->forUser($request->user()),
            'isComplete' => (bool) $profile->is_complete,
        ]);
    }
}

