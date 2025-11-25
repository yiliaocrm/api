<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Import\CreateTemplateRequest;
use App\Models\ImportTemplate;
use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class ImportTemplateController extends Controller
{
    public function __construct(
        protected ImportTemplate $importTemplateModel
    )
    {

    }

    /**
     * 列表
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $templates = $this->importTemplateModel->paginate($request->get('rows', 10));

        return response_success([
            'rows'  => $templates->items(),
            'total' => $templates->total()
        ]);
    }

    /**
     * @param CreateTemplateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateTemplateRequest $request)
    {
        $this->importTemplateModel->title = $request->get('title');
        $this->importTemplateModel->template = Storage::disk('import')->putFile(date('Y-m-d'), $request->file('template'));
        $this->importTemplateModel->chunk_size = $request->get('chunk_size');
        $this->importTemplateModel->use_import = $request->get('use_import');
        $this->importTemplateModel->async_limit = $request->get('async_limit');
        $this->importTemplateModel->create_user_id = $request->user()?->id ? : 0;
        $this->importTemplateModel->created_at = date('Y-m-d H:i:s');
        $this->importTemplateModel->updated_at = date('Y-m-d H:i:s');
        $this->importTemplateModel->save();

        return response_success();
    }

    public function show($id)
    {
        return response_success($this->importTemplateModel->find($id));
    }


    public function update($id, CreateTemplateRequest $request)
    {
        $template = $this->importTemplateModel->find($id);
        $template->title = $request->get('title');
        $template->template = Storage::disk('import')->putFile(date('Y-m-d'), $request->file('template'));
        $template->chunk_size = $request->integer('chunk_size');
        $template->use_import = $request->string('use_import');
        $template->async_limit = $request->integer('async_limit');
        $template->create_user_id = $request->user()?->id ? : 0;
        $template->updated_at = date('Y-m-d H:i:s');
        $template->save();

        return response_success();
    }


    public function destroy($id)
    {
        $this->importTemplateModel->where('id', $id)->delete();

        return response_success();
    }


    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($id)
    {
        $template = $this->importTemplateModel->where('id', $id)->value('template');

        return Response::download(Storage::disk('import')->path($template));
    }


    public function import($id, Request $request, ImportService $importService)
    {
        /* @var ImportTemplate $template */
        $template = $this->importTemplateModel->where('id', $id)->first();

        if (! $template) {
            return response_error(msg: '模板不存在');
        }

        try {
            $importService->prepare($template, $request->file('file'));
            return response_success();
        } catch (\Throwable $e) {
            return response_error(msg: $e->getMessage());
        }
    }
}
