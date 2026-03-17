<?php

namespace App\Http\Controllers;


use App\DTO\SubscriberCreateData;
use App\DTO\ReadySentReadData;
use App\DTO\RedirectCreateData;
use App\Helpers\SettingsHelper;
use App\Helpers\StringHelper;
use App\Http\Requests\Frontend\AddSubRequest;
use App\Models\Category;
use App\Models\Subscribers;
use App\Repositories\ReadySentRepository;
use App\Repositories\RedirectRepository;
use App\Repositories\SubscriberRepository;
use App\Services\SendMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(
        private readonly SubscriberRepository $subscriberRepository,
        private readonly ReadySentRepository $readySentRepository,
        private readonly RedirectRepository $redirectRepository,
        private readonly SendMailService $sendMailService,
    ) {
    }

    /**
     * @param int $subscriber
     * @param int $template
     * @return Response
     */
    public function pic(int $subscriber, int $template): Response
    {
        $this->readySentRepository->markAsRead(
            new ReadySentReadData(
                subscriberId: $subscriber,
                templateId: $template,
            )
        );

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

        $this->redirectRepository->add(
            new RedirectCreateData(
                url: $url,
                time: now(),
                email: $subscriberModel->email ?? 'test',
            )
        );

        return redirect($url);
    }

    /**
     * @param int $id
     * @param string $token
     * @return View
     */
    public function unsubscribe(int $id, string $token): View
    {
        $subscriberModel = $this->subscriberRepository->find($id);

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
    public function subscribe(int $id, string $token): View
    {
        $subscriberModel = $this->subscriberRepository->find($id);

        abort_if(!$subscriberModel || $subscriberModel->token !== $token, 404);

        $subscriberModel->active = 1;
        $subscriberModel->save();

        return view('frontend.subscribe');
    }

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
        $settings = SettingsHelper::getInstance();
        $validated = $request->validated();
        $token = StringHelper::token();

        $requireConfirmation = (int) $settings->getValueForKey('REQUIRE_SUB_CONFIRMATION') === 1;

        $subscriber = $this->subscriberRepository->createFrontendSubscriber(
            new SubscriberCreateData(
                email: $validated['email'],
                name: $validated['name'] ?? '',
                active: $requireConfirmation ? 0 : 1,
                token: $token,
                timeSent: now(),
                categoryIds: $validated['categoryId'] ?? [],
            )
        );

        $this->sendMailService->sendFrontendSubscriberEmails($subscriber);

        return response()->json([
            'result' => 'success',
            'msg' => __('frontend.msg.subscription_is_formed'),
        ]);
    }

    public function getCategories(): JsonResponse
    {
        return response()->json([
            'items' => Category::query()
                ->orderBy('name', 'desc')
                ->get(),
        ]);
    }
}
