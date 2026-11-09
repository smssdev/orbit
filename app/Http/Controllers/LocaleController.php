<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Switch the interface language: remembered on the account when signed in, and in a cookie for everyone (so
     * the sign-in pages keep it too).
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', Rule::in(array_keys(Locales::SUPPORTED))],
        ]);

        $locale = $request->string('locale')->toString();
        $user = $request->user();

        if ($user instanceof User) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back()->withCookie(cookie()->forever(Locales::COOKIE, $locale));
    }
}
