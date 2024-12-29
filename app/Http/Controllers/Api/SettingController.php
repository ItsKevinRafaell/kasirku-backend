<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(){
        $setting = Setting::first();
        if($setting){
            return response()->json([
                'message' => 'Sukses menampilkan setting',
                'data' => $setting,
                'success' => true
            ]);
        }

        return response()->json([
            'message' => 'Setting tidak ditemukan',
            'success' => false,
            'data' => null
        ], 404);
    }
}
