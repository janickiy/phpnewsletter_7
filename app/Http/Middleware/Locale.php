<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;

class Locale
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        $raw_locale = $request->cookie('lang');

        if (!$raw_locale && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match_all('/([a-z-]+)(?:;q=([0-9.]+))?/', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $accept_langs);

            foreach ($accept_langs[1] ?? [] as $lang) {
                $normalized = strtolower($lang);
                $locales = config('app.locales', []);

                if (in_array($normalized, $locales, true)) {
                    $raw_locale = $normalized;
                    break;
                }

                foreach ($locales as $availableLocale) {
                    if (str_starts_with($normalized, strtolower($availableLocale)) || str_starts_with(strtolower($availableLocale), substr($normalized, 0, 2))) {
                        $raw_locale = $availableLocale;
                        break 2;
                    }
                }
            }
        }

        if (in_array($raw_locale, config('app.locales'), true)) {
            $locale = $raw_locale;
        } else {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
