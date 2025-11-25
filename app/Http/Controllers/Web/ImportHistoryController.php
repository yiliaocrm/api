<?php

namespace App\Http\Controllers\Web;

use App\Models\ImportHistory;
use App\Services\ImportService;
use App\Models\ImportHistoryRecord;
use App\Http\Controllers\Controller;
use App\Exports\HistoryErrorRecordsExport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class ImportHistoryController extends Controller
{
    public function __construct(
        protected ImportHistory $importHistoryModel
    )
    {

    }

    /**
     * 历史导入记录
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $rows  = $request->input('rows', 10);
        $sort  = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');
        $query = ImportHistory::query()
            ->with([
                'template:id,title',
            ])
            ->orderBy($sort, $order)
            ->paginate($rows);
        return response_success([
            'rows'  => $query->items(),
            'total' => $query->total()
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
     * @return JsonResponse
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
