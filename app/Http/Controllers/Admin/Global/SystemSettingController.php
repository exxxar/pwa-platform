<?php

namespace App\Http\Controllers\Admin\Global;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Global\UpdateSystemSettingsRequest;
use App\Services\Admin\Global\SystemSettingService;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    protected SystemSettingService $settingService;

    public function __construct(SystemSettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Получение всех системных настроек
     */
    public function index(Request $request)
    {
        $this->authorize('view', 'system-settings');

        $settings = $this->settingService->getAllSettings();

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Обновление системных настроек
     */
    public function update(UpdateSystemSettingsRequest $request)
    {
        $this->authorize('update', 'system-settings');

        $this->settingService->updateSettings($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Настройки успешно обновлены',
        ]);
    }

    /**
     * Очистка кэша настроек
     */
    public function clearCache(Request $request)
    {
        $this->authorize('clearCache', 'system-settings');

        $this->settingService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Кэш настроек очищен',
        ]);
    }
}
