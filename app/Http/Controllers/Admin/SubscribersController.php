<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\{
    Category,
    Subscribers,
    Subscriptions,
    Charsets,
};
use App\Helpers\StringHelper;
use App\Http\Requests\Admin\Subscribers\{ImportRequest, StoreRequest, EditRequest};
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;

class SubscribersController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = trans('frontend.hint.subscribers_index') ?? null;

        return view('admin.subscribers.index', compact('infoAlert'))->with('title', trans('frontend.title.subscribers_index'));
    }

    /**
     * @return View
     */
    public function create(): View
    {
        $options = Category::getOption();
        $infoAlert = trans('frontend.hint.subscribers_create') ?? null;

        return view('admin.subscribers.create_edit', compact('options', 'infoAlert'))->with('title', trans('frontend.title.subscribers_create'));
    }

    /**
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        $id = Subscribers::create(array_merge($request->all(), ['timeSent' => date('Y-m-d H:i:s'), 'active' => 1, 'token' => StringHelper::token()]))->id;

        if ($id) {
            foreach ($request->categoryId ?? [] as $categoryId) {
                if (is_numeric($categoryId)) {
                    Subscriptions::create(['subscriber_id' => $id, 'category_id' => $categoryId]);
                }
            }
        }

        return redirect()->route('admin.subscribers.index')->with('success', trans('message.information_successfully_added'));
    }

    /**
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = Subscribers::find($id);

        if (!$row) abort(404);

        $options = Category::getOption();
        $subscriberCategoryId = [];

        foreach ($row->subscriptions ?? [] as $subscription) {
            $subscriberCategoryId[] = $subscription->category_id;
        }

        $infoAlert = trans('frontend.hint.subscribers_edit') ?? null;

        return view('admin.subscribers.create_edit', compact('options', 'row', 'subscriberCategoryId', 'infoAlert'))->with('title', trans('frontend.title.subscribers_edit'));
    }

    /**
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        $row = Subscribers::find($request->id);

        if (!$row) abort(404);

        if ($request->categoryId) {
            Subscriptions::where('subscriber_id', $request->id)->delete();

            foreach ($request->categoryId ?? [] as $categoryId) {
                if (is_numeric($categoryId)) Subscriptions::create(['subscriber_id' => $request->id, 'category_id' => $categoryId]);
            }
        }

        $row->name = $request->input('name');
        $row->email = $request->input('email');
        $row->save();

        return redirect()->route('admin.subscribers.index')->with('success', trans('message.data_updated'));
    }

    /**
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        Subscriptions::where('subscriber_id', $id)->delete();
        Subscribers::find($id)->delete();
    }

    /**
     * @return View
     */
    public function import(): View
    {
        $charsets = Charsets::getOption();
        $category_options = Category::getOption();
        $maxUploadFileSize = StringHelper::maxUploadFileSize();
        $infoAlert = trans('frontend.hint.subscribers_import') ?? null;

        return view('admin.subscribers.import', compact('charsets', 'category_options', 'maxUploadFileSize', 'infoAlert'))->with('title', trans('frontend.title.subscribers_import'));
    }

    /**
     * @param ImportRequest $request
     * @return RedirectResponse
     */
    public function importSubscribers(ImportRequest $request): RedirectResponse
    {
        $extension = strtolower($request->file('import')->getClientOriginalExtension());

        switch ($extension) {
            case 'csv':
            case 'xls':
            case 'xlsx':
            case 'ods':
                $result = Subscribers::importFromExcel($request);
                break;

            default:
                $result = Subscribers::importFromText($request);
        }

        if ($result === false)
            return redirect()->route('admin.subscribers.index')->with('error', trans('message.error_import_file'));
        else
            return redirect()->route('admin.subscribers.index')->with('success', trans('message.import_completed') . $result);
    }

    /**
     * @return View
     */
    public function export(): View
    {
        $options = Category::getOption();
        $infoAlert = trans('frontend.hint.subscribers_export') ?? null;

        return view('admin.subscribers.export', compact('options', 'infoAlert'))->with('title', trans('frontend.title.subscribers_export'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response|void
     */
    public function exportSubscribers(Request $request)
    {
        $request->export_type;
        $subscribers = Subscribers::getSubscribersList($request->categoryId);

        if ($request->export_type === 'text') {
            $ext = 'txt';
            $filename = 'emailexport' . date("d_m_Y") . '.txt';

            $contents = '';
            foreach ($subscribers ?? [] as $subscriber) {
                $contents .= "" . $subscriber->email . " " . $subscriber->name . "\r\n";
            }
        } elseif ($request->export_type === 'excel') {
            $ext = 'xlsx';
            $filename = 'emailexport' . date("d_m_Y") . '.xlsx';
            $oSpreadsheet_Out = new Spreadsheet();

            $oSpreadsheet_Out->getProperties()->setCreator('Alexander Yanitsky')
                ->setLastModifiedBy('PHP Newsletter')
                ->setTitle('Office 2007 XLSX Document')
                ->setSubject('Office 2007 XLSX Document')
                ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
                ->setKeywords('office 2007 openxml php')
                ->setCategory('Email export file');

            // Add some data
            $oSpreadsheet_Out->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Email')
                ->setCellValue('B1', trans('frontend.str.name'));

            $i = 1;

            foreach ($subscribers ?? [] as $subscriber) {
                $i++;

                $oSpreadsheet_Out->setActiveSheetIndex(0)
                    ->setCellValue('A' . $i, $subscriber->email)
                    ->setCellValue('B' . $i, $subscriber->name);
            }

            $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('A')->setWidth(30);
            $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('B')->setWidth(30);

            $oWriter = IOFactory::createWriter($oSpreadsheet_Out, 'Xlsx');
            ob_start();
            $oWriter->save('php://output');
            $contents = ob_get_contents();
            ob_end_clean();
        }

        if ($request->compress === 'zip') {
            header('Content-type: application/zip');
            header('Content-Disposition: attachment; filename=emailexport_' . date("d_m_Y") . '.zip');

            $fout = fopen("php://output", "wb");

            if ($fout !== false) {
                fwrite($fout, "\x1F\x8B\x08\x08" . pack("V", '') . "\0\xFF", 10);

                $oname = str_replace("\0", "", $filename);
                fwrite($fout, $oname . "\0", 1 + strlen($oname));

                $fltr = stream_filter_append($fout, "zlib.deflate", STREAM_FILTER_WRITE, -1);
                $hctx = hash_init("crc32b");

                if (!ini_get("safe_mode")) set_time_limit(0);

                hash_update($hctx, $contents);
                $fsize = strlen($contents);

                fwrite($fout, $contents, $fsize);

                stream_filter_remove($fltr);

                $crc = hash_final($hctx, TRUE);

                fwrite($fout, $crc[3] . $crc[2] . $crc[1] . $crc[0], 4);
                fwrite($fout, pack("V", $fsize), 4);

                fclose($fout);

                exit();
            }

        } else {
            return response($contents, 200, [
                'Content-Disposition' => 'attachment; filename=' . $filename,
                'Cache-Control' => 'max-age=0',
                'Content-Type' => StringHelper::getMimeType($ext),
            ]);
        }
    }

    /**
     * @return RedirectResponse
     */
    public function removeAll(): RedirectResponse
    {
        Subscribers::truncate();
        Subscriptions::truncate();

        return redirect()->route('admin.subscribers.index')->with('success', trans('message.data_successfully_deleted'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $temp = [];

        foreach ($request->activate ?? [] as $id) {
            if (is_numeric($id)) {
                $temp[] = $id;
            }
        }

        switch ($request->action) {
            case  0 :
            case  1 :
                Subscribers::whereIN('id', $temp)->update(['active' => $request->action]);
                break;

            case 2 :
                Subscriptions::whereIN('subscriber_id', $temp)->delete();
                Subscribers::whereIN('id', $temp)->delete();
                break;
        }

        return redirect()->route('admin.subscribers.index')->with('success', trans('message.actions_completed'));
    }
}
