<?php

namespace App\Http\Controllers;

use App\Helpers\SendEmailHelper;
use App\Helpers\SettingsHelper;
use App\Helpers\StringHelper;
use App\Http\Requests\Frontend\AddSubRequest;
use App\Models\Category;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class FrontendController extends Controller
{
    /**
     * @param int $subscriber
     * @param int $template
     * @return Response
     */
    public function pic(int $subscriber, int $template): Response
    {
        ReadySent::query()
            ->where('template_id', $template)
            ->where('subscriber_id', $subscriber)
            ->update(['readmail' => 1]);

        $image = imagecreatetruecolor(1, 1);
        imagefilledrectangle($image, 0, 0, 1, 1, 0xFFFFFF);

        ob_start();
        imagegif($image);
        $content = ob_get_clean();

        imagedestroy($image);

        return response($content, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * @param string $ref
     * @param int $subscriber
     * @return RedirectResponse
     */
    public function redirectLog(string $ref, int $subscriber): RedirectResponse
    {
        abort_if($ref === '', 404);

        $url = base64_decode($ref, true) ?: '';
        $subscriberModel = Subscribers::query()->find($subscriber);

        Redirect::query()->create([
            'url' => $url,
            'time' => now(),
            'email' => $subscriberModel->email ?? 'test',
        ]);

        return redirect($url);
    }

    /**
     * @param int $subscriber
     * @param string $token
     * @return View
     */
    public function unsubscribe(int $subscriber, string $token): View
    {
        $subscriberModel = Subscribers::query()->find($subscriber);

        abort_if(!$subscriberModel || $subscriberModel->token !== $token, 404);

        $email = $subscriberModel->email;
        $subscriberModel->active = 0;
        $subscriberModel->save();

        return view('frontend.unsubscribe', [
            'msg' => str_replace('%EMAIL%', $email, __('frontend.str.address_has_been_deleted')),
        ]);
    }

    /**
     * @param int $subscriber
     * @param string $token
     * @return View
     */
    public function subscribe(int $subscriber, string $token): View
    {
        $subscriberModel = Subscribers::query()->find($subscriber);

        abort_if(!$subscriberModel || $subscriberModel->token !== $token, 404);

        $subscriberModel->active = 1;
        $subscriberModel->save();

        return view('frontend.subscribe');
    }

    /**
     * @return View
     */
    public function form(): View
    {
        return view('frontend.subform', [
            'category' => Category::query()->get(),
            'title' => 'Subform',
        ]);
    }

    /**
     * @param AddSubRequest $request
     * @return JsonResponse
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \Throwable
     */
    public function addSub(AddSubRequest $request): JsonResponse
    {
        $sendMail = new SendEmailHelper();
        $settings = SettingsHelper::getInstance();
        $token = StringHelper::token();

        $requireConfirmation = (int) $settings->getValueForKey('REQUIRE_SUB_CONFIRMATION') === 1;
        $notifyNewSubscriber = (int) $settings->getValueForKey('NEW_SUBSCRIBER_NOTIFY') === 1;

        $subscriberId = DB::transaction(function () use ($request, $token, $requireConfirmation) {
            $subscriber = Subscribers::query()->create([
                $request->validated(),
                'active' => $requireConfirmation ? 0 : 1,
                'token' => $token,
            ]);

            foreach ($request->categoryId ?? [] as $categoryId) {
                if (is_numeric($categoryId)) {
                    Subscriptions::query()->create([
                        'subscriber_id' => $subscriber->id,
                        'category_id' => (int) $categoryId,
                    ]);
                }
            }

            return $subscriber->id;
        });

        if ($requireConfirmation) {
            $sendMail->setSubject($settings->getValueForKey('SUBJECT_TEXT_CONFIRM'));

            $confirmUrl = URL::route('frontend.subscribe', [
                'subscriber' => $subscriberId,
                'token' => $token,
            ]);

            $message = str_replace(
                ["\r\n", "\r", "\n"],
                '<br>',
                $settings->getValueForKey('TEXT_CONFIRMATION')
            );

            $message = str_replace('%CONFIRM%', $confirmUrl, $message);

            $sendMail->body = $message;
            $sendMail->email = $request->email;
            $sendMail->token = $token;
            $sendMail->subscriberId = $subscriberId;
            $sendMail->name = $request->name;
            $sendMail->unsub = false;
            $sendMail->tracking = false;
            $sendMail->sendEmail();
        }

        if ($notifyNewSubscriber) {
            $subject = str_replace(
                '%SITE%',
                request()->getHost(),
                __('frontend.str.notification_newuser')
            );

            $message = __('frontend.str.notification_newuser') .
                "\nName: {$request->name} \nE-mail: {$request->email}\n";

            $message = str_replace('%SITE%', request()->getHost(), $message);

            $sendMail->subject = $subject;
            $sendMail->body = $message;
            $sendMail->email = $settings->getValueForKey('EMAIL');
            $sendMail->name = $settings->getValueForKey('FROM');
            $sendMail->tracking = false;
            $sendMail->unsub = false;
            $sendMail->sendEmail();
        }

        return response()->json([
            'result' => 'success',
            'msg' => __('frontend.msg.subscription_is_formed'),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function getCategories(): JsonResponse
    {
        return response()->json([
            'items' => Category::query()
                ->orderBy('name', 'desc')
                ->get(),
        ]);
    }
    }
