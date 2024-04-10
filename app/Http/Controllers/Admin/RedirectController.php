<?php

namespace App\Http\Controllers\Admin;

use App\Models\Redirect;
use App\Helpers\StringHelper;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use URL;

class RedirectController  extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $infoAlert = trans('frontend.hint.redirect_index') ? trans('frontend.hint.redirect_index') : null;

        return view('admin.redirect.index', compact('infoAlert'))->with('title',trans('frontend.title.redirect_index'));
    }

    /**
     * @return RedirectResponse
     */
    public function clear(): RedirectResponse
    {
        Redirect::truncate();

        return redirect(URL::route('admin.redirect.index'))->with('success', trans('message.statistics_cleared'));
    }

    /**
     * @param string $url
     * @return Response
     */
    public function download(string $url): Response
    {
        $ext = 'xlsx';
        $filename = 'redirect_' . date("d_m_Y") . '.xlsx';
        $oSpreadsheet_Out = new Spreadsheet();

        $url = base64_decode($url);

        $redirectLog = Redirect::where('url', $url)->get();

        if (!$redirectLog) abort(404);

        $oSpreadsheet_Out->getProperties()->setCreator('Alexander Yanitsky')
            ->setLastModifiedBy('PHP Newsletter')
            ->setTitle(trans('str.redirect'))
            ->setSubject('Office 2007 XLSX Document')
            ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Redirect Log file');

        $oSpreadsheet_Out->setActiveSheetIndex(0)
            ->setCellValue('A1', 'E-mail')
            ->setCellValue('B1', trans('frontend.str.time'));

        $i = 1;

        foreach ($redirectLog as $row) {
            $i++;

            $oSpreadsheet_Out->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $row->email)
                ->setCellValue('B' . $i, $row->created_at);
        }

        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('B')->setWidth(25);

        $oWriter = IOFactory::createWriter($oSpreadsheet_Out, 'Xlsx');
        ob_start();
        $oWriter->save('php://output');
        $contents = ob_get_contents();
        ob_end_clean();

        return response($contents, 200, [
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Cache-Control' => 'max-age=0',
            'Content-Type' => StringHelper::getMimeType($ext),
        ]);
    }

    /**
     * @param string $url
     * @return View
     */
    public function info(string $url): View
    {
        $infoAlert = trans('frontend.hint.redirectlog_info') ? trans('frontend.hint.redirectlog_info') : null;

        return view('admin.redirect.info', compact('url','infoAlert'))->with('title', trans('frontend.title.redirect_info'));
    }
}
