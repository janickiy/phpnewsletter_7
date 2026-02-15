<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Subscribers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadService
{
    /**
     * @param int $id
     * @return Response
     */
    public function log(int $id): Response
    {
        $ext = 'xlsx';
        $filename = 'log' . date("d_m_Y") . '.xlsx';
        $oSpreadsheet_Out = new Spreadsheet();

        $readySent = ReadySent::where('schedule_id', $id)->get();

        if (!$readySent) abort(404);

        $totalFailed = ReadySent::where('schedule_id', $id)->where('success', 0)->count();
        $readMail = ReadySent::where('schedule_id', $id)->where('readMail', 1)->count();
        $totalTime = ReadySent::selectRaw('sec_to_time(UNIX_TIMESTAMP(max(created_at)) - UNIX_TIMESTAMP(min(created_at))) as total')->where('schedule_id', $id)->first();

        $total = $readySent->count();

        if ($total > 0) {
            $success = $total - $totalFailed;
            $count = 100 * $success / $total;
        } else {
            $count = 0;
            $total = 0;
        }

        $oSpreadsheet_Out->getProperties()->setCreator('Alexander Yanitsky')
            ->setLastModifiedBy('PHP Newsletter')
            ->setTitle(__('frontend.str.log'))
            ->setSubject('Office 2007 XLSX Document')
            ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Log file');

        // Add some data
        $oSpreadsheet_Out->setActiveSheetIndex(0)
            ->setCellValue('A1', __('frontend.str.total') . ": $total\n" . __('frontend.str.sent') . ": " . $count . "%\n" . __('frontend.str.spent_time') . ": $totalTime->total\n" . __('frontend.str.read') . ": " . $readMail)
            ->setCellValue('A2', trans('frontend.str.email'))
            ->setCellValue('B2', trans('frontend.str.time'))
            ->setCellValue('C2', trans('frontend.str.status'))
            ->setCellValue('A2', trans('frontend.str.newsletter'))
            ->setCellValue('B2', trans('frontend.str.email'))
            ->setCellValue('C2', trans('frontend.str.time'))
            ->setCellValue('D2', trans('frontend.str.status'))
            ->setCellValue('E2', trans('frontend.str.read'))
            ->setCellValue('F2', trans('frontend.str.error'))
            ->mergeCells('A1:F1');

        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('A1')->getAlignment()->applyFromArray(['wrapText' => TRUE]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('A2')->getFill()->applyFromArray(['setRGB' => 'E3DA62']);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('B2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('A1')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('A2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('B2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('C2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('D2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('E2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('F2')->getFill()->applyFromArray(['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EE7171']]);

        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('A2')->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('B2')->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('C2')->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('D2')->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('E2')->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
        $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('F2')->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);

        $i = 2;

        foreach ($readySent ?? [] as $row) {
            $i++;

            $oSpreadsheet_Out->setActiveSheetIndex(0)
                ->setCellValue('A' . $i, $row->template)
                ->setCellValue('B' . $i, $row->email)
                ->setCellValue('C' . $i, $row->created_at)
                ->setCellValue('D' . $i, $row->success === 1 ? trans('frontend.str.send_status_yes') : trans('frontend.str.send_status_no'))
                ->setCellValue('E' . $i, $row->readMail === 1 ? trans('frontend.str.yes') : trans('frontend.str.no'))
                ->setCellValue('F' . $i, $row->errorMsg);

            $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('D' . $i)->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
            $oSpreadsheet_Out->setActiveSheetIndex(0)->getStyle('E' . $i)->getAlignment()->applyFromArray(['horizontal' => Alignment::HORIZONTAL_CENTER]);
        }

        $oSpreadsheet_Out->setActiveSheetIndex(0)->getRowDimension(1)->setRowHeight(70);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $oSpreadsheet_Out->getActiveSheet()->getColumnDimension('F')->setWidth(35);

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
     * @return Response
     */
    public function redirect(string $url): Response
    {
        $ext = 'xlsx';
        $filename = 'redirect_' . date("d_m_Y") . '.xlsx';
        $oSpreadsheet_Out = new Spreadsheet();
        $url = base64_decode($url);

        $redirectLog = Redirect::where('url', $url)->get();

        if (!$redirectLog) abort(404);

        $oSpreadsheet_Out->getProperties()->setCreator('Alexander Yanitsky')
            ->setLastModifiedBy('PHP Newsletter')
            ->setTitle(__('str.redirect'))
            ->setSubject('Office 2007 XLSX Document')
            ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Redirect Log file');

        $oSpreadsheet_Out->setActiveSheetIndex(0)
            ->setCellValue('A1', 'E-mail')
            ->setCellValue('B1', __('frontend.str.time'));

        $i = 1;

        foreach ($redirectLog ?? [] as $row) {
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
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
        $request->export_type;
        $subscribers = Subscribers::getSubscribersList($request->categoryId);


        if ($request->export_type == 'text') {
            $ext = 'txt';
            $filename = 'exportEmail' . date("d_m_Y") . '.txt';

            $contents = '';
            foreach ($subscribers ?? [] as $subscriber) {
                $contents .= "" . $subscriber->email . " " . $subscriber->name . "\r\n";
            }
        } elseif ($request->export_type == 'excel') {
            $ext = 'xlsx';
            $filename = 'exportEmail' . date("d_m_Y") . '.xlsx';
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

        if ($request->compress == 'zip') {

            $filename = 'exportEmail_' . date("d_m_Y") . '.zip';

            return response()->streamDownload(function () use ($contents, $filename) {
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

                    $crc = hash_final($hctx, true);

                    fwrite($fout, $crc[3] . $crc[2] . $crc[1] . $crc[0], 4);
                    fwrite($fout, pack("V", $fsize), 4);

                    fclose($fout);
                }
            }, $filename, [
                'Content-Type' => 'application/zip',
            ]);
        } else {
            return response($contents, 200, [
                'Content-Disposition' => 'attachment; filename=' . $filename,
                'Cache-Control' => 'max-age=0',
                'Content-Type' => StringHelper::getMimeType($ext),
            ]);
        }
    }
}
