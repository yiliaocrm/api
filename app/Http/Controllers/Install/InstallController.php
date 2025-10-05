<?php

namespace App\Http\Controllers\Install;

use App\Exceptions\HisException;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\Install\InstallRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InstallController extends Controller
{
    /**
     * 安装首页
     * @return SymfonyResponse
     */
    public function index(): SymfonyResponse
    {
        $file = public_path('dist/install/index.html');
        if (file_exists($file)) {
            return Response::make(file_get_contents($file), 200, [
                'Content-Type'  => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma'        => 'no-cache',
                'Expires'       => '0'
            ]);
        }
        abort(404);
    }

    /**
     * 检查系统环境要求
     * @param InstallRequest $request
     * @return JsonResponse
     */
    public function environment(InstallRequest $request): JsonResponse
    {
        return response_success(
            $request->getEnvironmentData()
        );
    }

    /**
     * 开始安装
     * @param InstallRequest $request
     * @return JsonResponse
     * @throws HisException
     */
    public function start(InstallRequest $request): JsonResponse
    {
        $request->validateDatabaseConnection();
        $request->saveInstallConfig();

        return response_success([
            'steps' => $request->getInstallSteps(),
        ]);
    }

    /**
     * 统一安装处理
     * @param InstallRequest $request
     * @return JsonResponse
     * @throws HisException
     */
    public function install(InstallRequest $request): JsonResponse
    {
        $request->executeInstallStep(
            $request->input('action')
        );
        return response_success();
    }

    /**
     * 重定向到安装首页
     * @return RedirectResponse
     */
    public function redirect(): RedirectResponse
    {
        return redirect()->route('install.index');
    }
}
