<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIssuerProfileRequest;
use App\Models\IssuerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IssuerProfileController extends Controller
{
    public function edit(): View
    {
        return view('issuer-profile.edit', [
            'profile' => IssuerProfile::forUser(auth()->id()),
        ]);
    }

    public function update(UpdateIssuerProfileRequest $request): RedirectResponse
    {
        IssuerProfile::forUser(auth()->id())->update($request->validated());

        return redirect()->route('issuer-profile.edit')->with('success', 'Perfil actualizado.');
    }
}
