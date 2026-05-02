<?php

namespace App\Features\Settings\Actions;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class IndexSettingAction
{
    public function __invoke(): JsonResponse
    {
        $settings = Setting::all()->mapWithKeys(function (Setting $setting) {
            return [$setting->key => $setting->value];
        });

        return response()->json($settings);
    }
}
