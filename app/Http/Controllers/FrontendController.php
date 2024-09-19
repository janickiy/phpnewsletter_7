<?php

namespace App\Http\Controllers;

use App\Models\{
    ReadySent,
    Redirect,
    Subscribers,
    Category,
    Subscriptions
};

use App\Helpers\{
    StringHelper,
    SettingsHelper,
    SendEmailHelper,
};
use App\Http\Requests\Frontend\AddSubRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FrontendController extends Controller
{
    /**
     * @param int $subscriber
     * @param int $template
     * @return void
     */
    public function pic(int $subscriber, int $template)
    {
        ReadySent::where('template_id', $template)->where('subscriber_id', $subscriber)->update(['readmail' => 1]);

        $im = imagecreatetruecolor(1, 1);

        imagefilledrectangle($im, 0, 0, 99, 99, 0xFFFFFF);
        header('Content-Type: image/gif');
        imagegif($im);
        imagedestroy($im);
        exit;
    }

    /**
     * @param string $ref
     * @param int $subscriber
     * @return RedirectResponse
     */
    public function redirectLog(string $ref, int $subscriber): RedirectResponse
    {
        if (!$ref) abort(404);

        $url = isset($ref) ? base64_decode($ref) : '';
        $subscribers = Subscribers::find($subscriber);

        $data = [
            'url' => $url,
            'time' => date("Y-m-d H:i:s"),
            'email' => $subscribers->email ?? 'test'
        ];

        Redirect::create($data);

        return redirect($url);
    }

    /**
     * @param int $subscriber
     * @param string $token
     * @return View
     */
    public function unsubscribe(int $subscriber, string $token): View
    {
        $subscriber = Subscribers::find($subscriber);

        if (!$subscriber || $subscriber->token != $token) abort(404);

        $email = $subscriber->email;
        $subscriber->active = 0;
        $subscriber->save();

        $msg = str_replace('%EMAIL%', $email, trans('frontend.str.address_has_been_deleted'));

        return view('frontend.unsubscribe', compact('msg'));
    }

    /**
     * @param int $subscriber
     * @param string $token
     * @return View
     */
    public function subscribe(int $subscriber, string $token): View
    {
        $subscriber = Subscribers::find($subscriber);

        if (!$subscriber || $subscriber->token != $token) abort(404);

        $subscriber->active = 1;
        $subscriber->save();

        return view('frontend.subscribe');
    }

    /**
     * @return View
     */
    public function form(): View
    {
        $category = Category::get();

        return view('frontend.subform', compact('category'))->with('title', 'Subform');
    }

    /**
     * @param AddSubRequest $request
     * @return JsonResponse
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function addSub(AddSubRequest $request): JsonResponse
    {

        $token = StringHelper::token();

        $id = Subscribers::create(array_merge($request->all(), ['active' => SettingsHelper::getInstance()->getValueForKey('REQUIRE_SUB_CONFIRMATION') == 1 ? 0 : 1, 'token' => $token]))->id;

        if ($id) {
            if (SettingsHelper::getInstance()->getValueForKey('REQUIRE_SUB_CONFIRMATION') == 1) {
                SendEmailHelper::setSubject(SettingsHelper::getInstance()->getValueForKey('SUBJECT_TEXT_CONFIRM'));

                $CONFIRM = SettingsHelper::getInstance()->getValueForKey('URL') . "subscribe/" . $id . "/" . $token;
                $msg = str_replace(array("\r\n", "\r", "\n"), '<br>', SettingsHelper::getInstance()->getValueForKey('TEXT_CONFIRMATION'));
                $msg = str_replace('%CONFIRM%', $CONFIRM, $msg);

                SendEmailHelper::setBody($msg);
                SendEmailHelper::setEmail($request->email);
                SendEmailHelper::setToken($token);
                SendEmailHelper::setSubscriberId($id);
                SendEmailHelper::setName($request->name);
                SendEmailHelper::setUnsub(false);
                SendEmailHelper::setTracking(false);
                SendEmailHelper::sendEmail();
            }

            if (SettingsHelper::getInstance()->getValueForKey('NEW_SUBSCRIBER_NOTIFY') == 1) {
                $subject = trans('frontend.str.notification_newuser');
                $subject = str_replace('%SITE%', $_SERVER['SERVER_NAME'], $subject);
                $msg = trans('frontend.str.notification_newuser') . "\nName: " . $request->name . " \nE-mail: " . $request->email . "\n";
                $msg = str_replace('%SITE%', $_SERVER['SERVER_NAME'], $msg);

                SendEmailHelper::setSubject($subject);
                SendEmailHelper::setBody($msg);
                SendEmailHelper::setEmail(SettingsHelper::getInstance()->getValueForKey('EMAIL'));
                SendEmailHelper::setName(SettingsHelper::getInstance()->getValueForKey('FROM'));
                SendEmailHelper::setTracking(false);
                SendEmailHelper::setUnsub(false);
                SendEmailHelper::sendEmail();
            }

            if ($request->categoryId) {
                foreach ($request->categoryId as $categoryId) {
                    if (is_numeric($categoryId)) {
                        Subscriptions::create(['subscriberId' => $id, 'categoryId' => $categoryId]);
                    }
                }
            }
        }

        return response()->json([
            'result' => 'success',
            'msg' => trans('frontend.msg.subscription_is_formed')
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        $category = Category::orderBy('name', 'desc')->get();

        return response()->json(['items' => $category]);
    }
}
