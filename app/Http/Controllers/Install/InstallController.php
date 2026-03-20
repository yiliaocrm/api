<?php

namespace App\Http\Controllers\Install;

use App\Exceptions\HisException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Install\InstallRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InstallController extends Controller
{
    /**
     * 安装首页
     */
    public function index(): SymfonyResponse
    {
        $file = public_path('dist/install/index.html');
        if (file_exists($file)) {
            return Response::make(file_get_contents($file), 200, [
                'Content-Type' => 'text/html',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
        abort(404);
    }

    /**
     * 检查系统环境要求
     */
    public function environment(InstallRequest $request): JsonResponse
    {
        return response_success(
            $request->getEnvironmentData()
        );
    }

    /**
     * 开始安装
     *
     * @throws HisException
     */
    public function start(InstallRequest $request): JsonResponse
    {
        $request->validateDatabaseConnection();
        $request->validateRedisConnection();
        $request->saveInstallConfig();

        return response_success([
            'steps' => $request->getInstallSteps(),
        ]);
    }

    /**
     * 统一安装处理
     *
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
     * 获取安装默认配置
     */
    public function getConfig(InstallRequest $request): JsonResponse
    {
        return response_success($request->getConfigData());
    }

    /**
     * 重定向到安装首页
     */
    public function redirect(): RedirectResponse
    {
        return redirect()->route('install.index');
    }
}
