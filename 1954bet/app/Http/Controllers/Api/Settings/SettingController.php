<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        $setting = \Helper::getSetting();
        $custom = \Helper::getCustom();
        
        // Garante que o retorno seja um array para evitar erro de 'null' no VueJS
        $settingData = $setting ? (is_array($setting) ? $setting : $setting->toArray()) : [];

        // Blindagem contra tela preta: bt_5_link, modal_active, etc.
        if (!isset($settingData['custom_layout']) || empty($settingData['custom_layout'])) {
            $settingData['custom_layout'] = [
                'bt_5_link' => '/support',
                'icon_bottom_left' => 'fa fa-user',
                'icon_bottom_right' => 'fa fa-bars',
                'modal_active' => false,
                'bet_required' => 0
            ];
        }

        return response()->json([
            'setting' => $settingData,
            'custom' => $custom,
            'favorites' => [] 
        ], 200);
    }
}