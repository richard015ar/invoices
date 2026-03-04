<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIssuerProfileRequest;
use App\Models\IssuerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class IssuerProfileController extends Controller
{
    public function edit(): View
    {
        return view('issuer-profile.edit', [
            'profile' => $this->profile(auth()->id()),
        ]);
    }

    public function update(UpdateIssuerProfileRequest $request): RedirectResponse
    {
        $this->profile(auth()->id())->update($request->validated());

        return redirect()->route('issuer-profile.edit')->with('success', 'Perfil actualizado.');
    }

    public static function profile(int $userId): IssuerProfile
    {
        $user = User::query()->find($userId);

        return IssuerProfile::query()->firstOrCreate(['user_id' => $userId], [
            'user_id' => $userId,
            'name' => $user?->name ?? 'New User',
            'email' => $user?->email,
            'address' => null,
            'nie' => null,
            'additional_info' => null,
        ]);
    }
}
