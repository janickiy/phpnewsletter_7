<?php

namespace App\Http\Controllers\Admin;


use App\Services\SubscriberService;
use App\Repositories\CategoryRepository;
use App\Repositories\SubscriberRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\DownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\{
    Subscribers,
    Charsets,
};
use App\Helpers\StringHelper;
use App\Http\Requests\Admin\Subscribers\{ImportRequest, StoreRequest, EditRequest};

;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;


class SubscribersController extends Controller
{
    public function __construct(
        private SubscriberRepository   $subscribersRepository,
        private CategoryRepository     $categoryRepository,
        private SubscriptionRepository $subscriptionRepository,
        private DownloadService        $downloadService,
        private SubscriberService      $subscriberService,
    )
    {
        parent::__construct();
    }


    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = __('frontend.hint.subscribers_index') ?? null;

        return view('admin.subscribers.index', compact('infoAlert'))->with('title', __('frontend.title.subscribers_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = $this->categoryRepository->getOption();
        $infoAlert = __('frontend.hint.subscribers_create') ?? null;

        return view('admin.subscribers.create_edit', compact('options', 'infoAlert'))->with('title', __('frontend.title.subscribers_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->subscribersRepository->create(array_merge($request->all(), [
                'timeSent' => date('Y-m-d H:i:s'),
                'active' => 1,
                'token' => StringHelper::token()
            ]));
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.subscribers.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->subscribersRepository->find($id);

        if (!$row) abort(404);

        $options = $this->categoryRepository->getOption();
        $subscriberCategoryId = $this->subscribersRepository->getSubscriberCategoryIdList($id);

        $infoAlert = __('frontend.hint.subscribers_edit') ?? null;

        return view('admin.subscribers.create_edit', compact('options', 'row', 'subscriberCategoryId', 'infoAlert'))->with('title', __('frontend.title.subscribers_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $this->subscribersRepository->update($request->id, $request->all());

                if ($request->categoryId) {
                    $this->subscriptionRepository->updateSubscriptions($request->categoryId, $request->id);
                }
            });
        } catch (Exception $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.subscribers.index')->with('success', __('message.data_updated'));
    }

    /**
     * @param int $id
     * @return void
     * @throws \Throwable
     */
    public function destroy(int $id): void
    {
        DB::transaction(function () use ($id) {
            $this->subscriptionRepository->removeBySubscriberId($id);
            $this->subscribersRepository->delete($id);
        });
    }

    /**
     * @return View
     */
    public function import(): View
    {
        $charsets = Charsets::getOption();
        $category_options = $this->categoryRepository->getOption();
        $maxUploadFileSize = StringHelper::maxUploadFileSize();
        $infoAlert = __('frontend.hint.subscribers_import') ?? null;

        return view('admin.subscribers.import', compact('charsets', 'category_options', 'maxUploadFileSize', 'infoAlert'))->with('title', __('frontend.title.subscribers_import'));
    }

    /**
     * @param ImportRequest $request
     * @return RedirectResponse
     */
    public function importSubscribers(ImportRequest $request): RedirectResponse
    {
        set_time_limit(0);

        $extension = strtolower($request->file('import')->getClientOriginalExtension());

        switch ($extension) {
            case 'csv':
            case 'xls':
            case 'xlsx':
            case 'ods':
                $result = $this->subscriberService->importFromExcel($request);
                break;

            default:
                $result = $this->subscriberService->importFromText($request);
        }

        if ($result === false)
            return redirect()->route('admin.subscribers.index')->with('error', __('message.error_import_file'));
        else
            return redirect()->route('admin.subscribers.index')->with('success', __('message.import_completed') . $result);
    }

    /**
     * @return View
     */
    public function export(): View
    {
        $options = $this->categoryRepository->getOption();
        $infoAlert = __('frontend.hint.subscribers_export') ?? null;

        return view('admin.subscribers.export', compact('options', 'infoAlert'))->with('title', __('frontend.title.subscribers_export'));
    }


    /**
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
        set_time_limit(0);

        return $this->downloadService->exportSubscribers($request);
    }

    /**
     * @return RedirectResponse
     */
    public function removeAll(): RedirectResponse
    {
        $this->subscribersRepository->truncate();
        $this->subscriptionRepository->truncate();

        return redirect()->route('admin.subscribers.index')->with('success', __('message.data_successfully_deleted'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $this->subscribersRepository->updateStatus($request->action, $request->activate);

        return redirect()->route('admin.subscribers.index')->with('success', __('message.actions_completed'));
    }
}
