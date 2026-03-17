<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Subscribers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadService
{
    /**
     * @param int $id
     * @return StreamedResponse
     */
    public function log(int $id): StreamedResponse
    {
        $readySent = ReadySent::where('schedule_id', $id)->get();

        abort_if($readySent->isEmpty(), 404);

        $filename = 'log_' . date("d_m_Y") . '.xlsx';

        return $this->streamExcel($filename, function (Spreadsheet $sheet) use ($readySent, $id) {

            $total = $readySent->count();
            $failed = ReadySent::where('schedule_id', $id)->where('success', 0)->count();
            $read = ReadySent::where('schedule_id', $id)->where('readMail', 1)->count();

            $sheet->setActiveSheetIndex(0)
                ->setCellValue('A1', "Total: $total | Read: $read | Failed: $failed");

            $sheet->setActiveSheetIndex(0)
                ->setCellValue('A2', 'Template')
                ->setCellValue('B2', 'Email')
                ->setCellValue('C2', 'Time')
                ->setCellValue('D2', 'Status');

            $i = 2;

            foreach ($readySent as $row) {
                $i++;

                $sheet->setActiveSheetIndex(0)
                    ->setCellValue('A' . $i, $row->template)
                    ->setCellValue('B' . $i, $row->email)
                    ->setCellValue('C' . $i, $row->created_at)
                    ->setCellValue('D' . $i, $row->success ? 'OK' : 'FAIL');
            }
        });
    }

    /**
     * @param string $url
     * @return StreamedResponse
     */
    public function redirect(string $url): StreamedResponse
    {
        $url = base64_decode($url);

        $rows = Redirect::where('url', $url)->get();

        abort_if($rows->isEmpty(), 404);

        $filename = 'redirect_' . date("d_m_Y") . '.xlsx';

        return $this->streamExcel($filename, function (Spreadsheet $sheet) use ($rows) {

            $sheet->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Email')
                ->setCellValue('B1', 'Time');

            $i = 1;

            foreach ($rows as $row) {
                $i++;

                $sheet->setActiveSheetIndex(0)
                    ->setCellValue('A' . $i, $row->email)
                    ->setCellValue('B' . $i, $row->created_at);
            }
        });
    }

    /**
     * @param Request $request
     * @return Response|StreamedResponse
     */
    public function exportSubscribers(Request $request): Response|StreamedResponse
    {
        $subscribers = $this->getSubscribersList($request->categoryId);

        [$contents, $ext] = $this->buildSubscribersFile($subscribers, $request->export_type);

        $filename = 'exportEmail_' . date("d_m_Y") . '.' . $ext;

        if ($request->compress === 'zip') {
            return $this->zipResponse($contents, $filename);
        }

        return response($contents, 200, [
            'Content-Disposition' => "attachment; filename={$filename}",
            'Content-Type' => StringHelper::getMimeType($ext),
        ]);
    }

    /**
     * @param Collection $subscribers
     * @param string $type
     * @return array|string[]
     */
    private function buildSubscribersFile(Collection $subscribers, string $type): array
    {
        if ($type === 'text') {
            $contents = '';

            foreach ($subscribers as $s) {
                $contents .= "{$s->email} {$s->name}\n";
            }

            return [$contents, 'txt'];
        }

        if ($type === 'excel') {
            $spreadsheet = new Spreadsheet();

            $spreadsheet->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Email')
                ->setCellValue('B1', 'Name');

            $i = 1;

            foreach ($subscribers as $s) {
                $i++;

                $spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue('A' . $i, $s->email)
                    ->setCellValue('B' . $i, $s->name);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

            ob_start();
            $writer->save('php://output');
            $contents = ob_get_clean();

            return [$contents, 'xlsx'];
        }

        throw new \InvalidArgumentException('Invalid export type');
    }

    /**
     * @param string $contents
     * @param string $innerFilename
     * @return StreamedResponse
     */
    private function zipResponse(string $contents, string $innerFilename): StreamedResponse
    {
        $zipFilename = pathinfo($innerFilename, PATHINFO_FILENAME) . '.zip';

        return response()->streamDownload(function () use ($contents, $innerFilename) {

            $zip = new \ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');

            if ($zip->open($tmpFile, \ZipArchive::CREATE) === true) {
                $zip->addFromString($innerFilename, $contents);
                $zip->close();

                readfile($tmpFile);
                unlink($tmpFile);
            }

        }, $zipFilename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @param string $filename
     * @param callable $callback
     * @return StreamedResponse
     */
    private function streamExcel(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback) {

            $spreadsheet = new Spreadsheet();
            $callback($spreadsheet);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');

        }, $filename, [
            'Content-Type' => StringHelper::getMimeType('xlsx'),
        ]);
    }

    /**
     * @param array|null $ids
     * @return Collection
     */
    private function getSubscribersList(?array $ids): Collection
    {
        if ($ids) {
            return Subscribers::query()
                ->select('subscribers.name', 'subscribers.email')
                ->distinct()
                ->leftJoin('subscriptions', function ($join) use ($ids) {
                    $join->on('subscribers.id', '=', 'subscriptions.subscriber_id')
                        ->whereIn('subscriptions.category_id', $ids);
                })
                ->where('subscribers.active', 1)
                ->whereNotNull('subscriptions.subscriber_id')
                ->get();
        }

        return Subscribers::select('name', 'email')
            ->where('active', 1)
            ->get();
    }
}
