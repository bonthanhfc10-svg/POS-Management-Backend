<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    private const ALLOWED_KEYS = [
        'store_name',
        'store_address',
        'store_phone',
        'currency',
        'tax_rate',
        'receipt_footer',
    ];

    public function index(): JsonResponse
    {
        $settings = SystemSetting::orderBy('key')->get();

        $data = [];
        foreach ($settings as $setting) {
            $data[$setting->key] = $setting->value;
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        $settings = $request->validated('settings');

        foreach ($settings as $key => $value) {
            if (!in_array($key, self::ALLOWED_KEYS, true)) {
                continue;
            }

            $type = $key === 'tax_rate' ? 'number' : 'string';
            SystemSetting::setValue($key, $value, $type);
        }

        $stored = [];
        foreach (SystemSetting::orderBy('key')->get() as $setting) {
            $stored[$setting->key] = $setting->value;
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully.',
            'data' => $stored,
        ]);
    }
}
