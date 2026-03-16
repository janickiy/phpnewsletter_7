<?php

namespace App\Http\Controllers\Admin;


use App\Helpers\StringHelper;
use App\Http\Requests\Admin\Subscribers\EditRequest;
use App\Http\Requests\Admin\Subscribers\ImportRequest;
use App\Http\Requests\Admin\Subscribers\StoreRequest;
use App\Models\Charsets;
use App\Repositories\CategoryRepository;
use App\Repositories\SubscriberRepository;
use App\Repositories\SubscriptionRepository;
use App\Services\DownloadService;
use App\Services\SubscriberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;


class SubscribersController extends Controller
{
    public function __construct(
        private readonly SubscriberRepository $subscribersRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly DownloadService $downloadService,
        private readonly SubscriberService $subscriberService,
    ) {
        parent::__construct();
    }

    /**
     * @return View
     */
    public function index(): View
    {
        return view('admin.subscribers.index', [
            'infoAlert' => __('frontend.hint.subscribers_index'),
            'title' => __('frontend.title.subscribers_index'),
        ]);
    }

    /**
     * @return View
     */
    public function create(): View
    {
        return view('admin.subscribers.create_edit', [
            'options' => $this->categoryRepository->getOption(),
            'infoAlert' => __('frontend.hint.subscribers_create'),
            'title' => __('frontend.title.subscribers_create'),
        ]);
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $this->subscribersRepository->create([
                $request->validated(),
                'timeSent' => now(),
                'active' => 1,
                'token' => StringHelper::token(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.subscribers.index')
            ->with('success', __('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->subscribersRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.subscribers.create_edit', [
            'options' => $this->categoryRepository->getOption(),
            'row' => $row,
            'subscriberCategoryIds' => $this->subscribersRepository->getSubscriberCategoryIdList($id),
            'infoAlert' => __('frontend.hint.subscribers_edit'),
            'title' => __('frontend.title.subscribers_edit'),
        ]);
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $this->subscribersRepository->update(
                    (int) $request->id,
                    $request->safe()->except(['id', 'categoryId'])
                );

                if ($request->filled('categoryId')) {
                    $this->subscriptionRepository->updateSubscriptions(
                        $request->categoryId,
                        (int) $request->id
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.subscribers.index')
            ->with('success', __('message.data_updated'));
    }

    /**
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy(int $id): RedirectResponse
    {
        try {
            DB::transaction(function () use ($id) {
                $this->subscriptionRepository->removeBySubscriberId($id);
                $this->subscribersRepository->delete($id);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.subscribers.index')
            ->with('success', __('message.data_deleted'));
    }

    /**
     * @return View
     */
    public function import(): View
    {
        return view('admin.subscribers.import', [
            'charsets' => Charsets::getOption(),
            'category_options' => $this->categoryRepository->getOption(),
            'maxUploadFileSize' => StringHelper::maxUploadFileSize(),
            'infoAlert' => __('frontend.hint.subscribers_import'),
            'title' => __('frontend.title.subscribers_import'),
        ]);
    }

    /**
     * @param ImportRequest $request
     * @return RedirectResponse
     */
    public function importSubscribers(ImportRequest $request): RedirectResponse
    {
        set_time_limit(0);

        $extension = strtolower($request->file('import')->getClientOriginalExtension());

        $result = match ($extension) {
            'csv', 'xls', 'xlsx', 'ods' => $this->subscriberService->importFromExcel($request),
            default => $this->subscriberService->importFromText($request),
        };

        if ($result === false) {
            return to_route('admin.subscribers.index')
                ->with('error', __('message.error_import_file'));
        }

        return to_route('admin.subscribers.index')
            ->with('success', __('message.import_completed') . $result);
    }

    /**
     * @return View
     */
    public function export(): View
    {
        return view('admin.subscribers.export', [
            'options' => $this->categoryRepository->getOption(),
            'infoAlert' => __('frontend.hint.subscribers_export'),
            'title' => __('frontend.title.subscribers_export'),
        ]);
    }

    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
        return $this->downloadService->exportSubscribers($request);
    }

    /**
     * @return RedirectResponse
     */
    public function removeAll(): RedirectResponse
    {
        try {
            DB::transaction(function () {
                $this->subscriptionRepository->deleteAll();
                $this->subscribersRepository->deleteAll();
            });
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.subscribers.index')
            ->with('success', __('message.data_successfully_deleted'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        try {
            $this->subscribersRepository->updateStatus(
                (int) $request->action,
                (array) $request->activate
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage());
        }

        return to_route('admin.subscribers.index')
            ->with('success', __('message.actions_completed'));
    }
}
