<?php

namespace App\Features\Settings;

use App\Features\Settings\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->mapWithKeys(function (Setting $setting) {
            return [$setting->key => $setting->value];
        });

        return response()->json($settings);
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        foreach ($data['settings'] as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value);
            }
        }

        $settings = Setting::all()->mapWithKeys(function (Setting $setting) {
            return [$setting->key => $setting->value];
        });

        return response()->json($settings);
    }
}
