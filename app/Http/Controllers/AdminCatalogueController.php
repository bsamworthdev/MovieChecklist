<?php

namespace App\Http\Controllers;

use App\Services\UkCatalogueUpdate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Exceptions\CatalogueUpdateException;

class AdminCatalogueController extends Controller
{
    public function start(Request $request, UkCatalogueUpdate $updater)
    {
        $data = $request->validate(['service' => ['required', Rule::in(array_keys(config('catalogues.services')))]]);
        try {
            $progress = $updater->start($data['service'], $request->user()->getAuthIdentifier());
        } catch (CatalogueUpdateException $exception) {
            throw ValidationException::withMessages(['catalogue' => $exception->getMessage()]);
        }
        $request->session()->put('catalogue_update_token', $progress['token']);
        return response()->json($progress);
    }

    public function step(Request $request, UkCatalogueUpdate $updater)
    {
        $data = $request->validate(['token' => 'required|uuid', 'processed' => 'required|integer|min:0']);
        $token = $request->session()->get('catalogue_update_token');
        if (!is_string($token) || !hash_equals($token, $data['token'])) {
            throw ValidationException::withMessages(['token' => 'This catalogue update has expired or was replaced. Start it again.']);
        }
        try {
            return response()->json($updater->step($token, $request->user()->getAuthIdentifier(), (int) $data['processed']));
        } catch (CatalogueUpdateException $exception) {
            throw ValidationException::withMessages(['catalogue' => $exception->getMessage()]);
        }
    }
}
