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
                $code = substr($lang, 0, 2);

                if ($code === 'en') {
                    $raw_locale = 'en';
                    break;
                }

                if ($code === 'ru') {
                    $raw_locale = 'ru';
                    break;
                }
            }
        }

        if (in_array($raw_locale, config('app.locales'))) {
            $locale = $raw_locale;
        } else
            $locale = config('app.locale');

        App::setLocale($locale);

        return $next($request);
    }
}
