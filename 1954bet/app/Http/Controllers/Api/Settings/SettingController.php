<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        // Busca a configuração ou gera uma padrão para não quebrar o Vue
        $setting = Setting::first() ?: (object) [
            'id' => 1,
            'software_name' => '101Win',
            'status' => 1,
            'disable_last_winners' => 0,
            'pwa_is_enable' => 0
        ];

        return response()->json([
            'setting' => $setting,
            'custom'  => [
                'pwa_is_enable' => $setting->pwa_is_enable ?? 0,
                'custom_css'    => $setting->custom_css ?? '',
                'custom_js'     => $setting->custom_js ?? '',
            ],
            'favorites' => []
        ], 200);
    }
}