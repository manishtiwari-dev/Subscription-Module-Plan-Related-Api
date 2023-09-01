<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\TimeZone;
use Illuminate\Http\Request;
use ApiHelper;

class TimezoneController extends Controller
{
    public function index_all(Request $request){

        $api_token = $request->api_token;

        $timezone_list = TimeZone::all();

        return ApiHelper::JSON_RESPONSE(true,$timezone_list,'');
    }

}
