<?php
namespace App\Http\Controllers\Web;

use App\Exports\HistoryErrorRecordsExport;
use App\Http\Controllers\Controller;
use App\Models\ImportHistory;
use App\Models\ImportHistoryRecord;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportHistoryController extends Controller
{
    public function __construct(
        protected ImportHistory $importHistoryModel
    )
    {

    }
    public function index(Request $request)
    {
        $histories = $this->importHistoryModel::query()
                ->when($templateId = $request->get('template_id'), function ($query) use ($templateId) {
                    $query->where('template_id', $templateId);
                })
                ->orderByDesc('id')
                ->paginate($request->get('rows', 10));

        return response_success([
            'rows' => $histories->items(),

            'total' => $histories->total()
        ]);
    }


    public function records($historyId, Request $request)
    {
        $records = ImportHistoryRecord::query()
            ->when($status = $request->get('status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->where('history_id', $historyId)
            ->orderByDesc('id')
            ->paginate($request->get('rows', 10));

        return response_success([
            'rows' => $records->items(),

            'total' => $records->total()
        ]);
    }


    /**
     * 导入
     *
     * @param $id
     * @param ImportService $importService
     * @return \Illuminate\Http\JsonResponse
     */
    public function importRecords($id, ImportService $importService)
    {
        $importService->import($id);

        return response_success();
    }


    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportErrorRecord($id)
    {
        return Excel::download(new HistoryErrorRecordsExport(ImportHistory::query()->find($id)), Str::random(10) . '.xlsx');
    }
}
