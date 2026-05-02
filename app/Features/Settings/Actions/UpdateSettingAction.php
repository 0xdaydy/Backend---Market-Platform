<?php

namespace App\Features\Settings\Actions;

use App\Features\Settings\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class UpdateSettingAction
{
    public function __invoke(UpdateSettingRequest $request): JsonResponse
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
