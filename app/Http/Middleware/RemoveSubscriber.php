<?php

namespace App\Http\Middleware;

use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use App\Models\{Subscribers, Subscriptions};
use Closure;

class RemoveSubscriber
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (file_exists(base_path('.env')) && SettingsHelper::getInstance()->getValueForKey('REMOVE_SUBSCRIBER')) {
            $interval = "created_at < NOW() - INTERVAL '" . SettingsHelper::getInstance()->getValueForKey('DAYS_FOR_REMOVE_SUBSCRIBER') . "' DAY";
            $subscribers = Subscribers::query()->active()->whereRaw($interval);

            if ($subscribers->count() > 0) {
                foreach ($subscribers->get() as $subscriber) {
                    Subscriptions::where('subscriber_id',$subscriber->id)->delete();
                }

                $subscribers->delete();
            }
        }

        return $next($request);
    }
}
