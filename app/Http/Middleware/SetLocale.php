<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the signed-in user's choice, then the visitor's cookie, then the browser's
 * Accept-Language, then the app default.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale);
        View::share('locale', $locale);
        View::share('direction', Locales::direction($locale));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $user = $request->user();

        $candidates = [
            $user instanceof User ? $user->locale : null,
            $request->cookie(Locales::COOKIE),
            $request->getPreferredLanguage(array_keys(Locales::SUPPORTED)),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && Locales::isSupported($candidate)) {
                return $candidate;
            }
        }

        return config()->string('app.locale');
    }
}
