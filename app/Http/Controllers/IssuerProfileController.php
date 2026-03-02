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
            'profile' => $this->profile(),
        ]);
    }

    public function update(UpdateIssuerProfileRequest $request): RedirectResponse
    {
        $this->profile()->update($request->validated());

        return redirect()->route('issuer-profile.edit')->with('success', 'Perfil actualizado.');
    }

    public static function profile(): IssuerProfile
    {
        return IssuerProfile::query()->firstOrCreate([], [
            'name' => 'Ricardo Aragon',
            'email' => null,
            'address' => null,
            'nie' => null,
            'additional_info' => null,
        ]);
    }
}
