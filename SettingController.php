<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\Setting;
use App\Models\PaymentSetting;
use App\Models\WebsiteSetting;
use App\Models\NotificationSetting;
use App\Models\Currency;
use App\Models\Language;
use App\Models\TimeZone;
use App\Events\GlobalEventBetweenSuperAdminAndAdmin;


class SettingController extends Controller
{
    /**
         Display a listing of the resource.
     */
    public function general_first(Request $request){
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.general')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $res = [];

        $res['settingInfo'] = Setting::where('created_by', ApiHelper::get_adminid_from_token($api_token))->first();
        $res['currency'] = Currency::all();
        $res['language'] = Language::all();
        $res['timezone'] = TimeZone::all();
        return ApiHelper::JSON_RESPONSE(true,$res,'DETAILS_UPDATED');
    }

    public function general(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.general')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $saveData = $request->only('currency','default_language');
        $saveData['created_by'] = ApiHelper::get_adminid_from_token($api_token);
        
        $status = GeneralSetting::updateOrCreate(
            ['created_by' => ApiHelper::get_adminid_from_token($api_token)],
            $saveData
        );

        if ($status)
            return ApiHelper::JSON_RESPONSE(true,$status,'SUCCESS_DETAILS_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],"ERROR_DETAILS_UPDATE");
           
    }

    /*
        Payment setting
    */

    public function payment_first(Request $request){
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.payment')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $result = PaymentSetting::where('created_by', ApiHelper::get_adminid_from_token($api_token))->first();
        return ApiHelper::JSON_RESPONSE(true,$result,'SUCCESS_PAYMENT_DETAILS_UPDATE');
    }

    public function payment(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.general')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $saveData = $request->except('api_token');
        $saveData['created_by'] = ApiHelper::get_adminid_from_token($api_token);
        
        $status = PaymentSetting::updateOrCreate(
            ['created_by' => ApiHelper::get_adminid_from_token($api_token)],
            $saveData
        );

        if ($status)
            return ApiHelper::JSON_RESPONSE(true,$status,'SUCCESS_PAYMENT_DETAILS_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],"ERROR_PAYMENT_DETAILS_UPDATE");
           
    }

    /*
        website setting
    */
    public function website_first(Request $request){
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.website')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $result = WebsiteSetting::where('created_by', ApiHelper::get_adminid_from_token($api_token))->first();
        return ApiHelper::JSON_RESPONSE(true,$result,'SUCCESS_WEBSITE_DETAILS_UPDATE');
    }

    public function website(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.website')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $saveData = $request->only('website_name');
        $saveData['created_by'] = ApiHelper::get_adminid_from_token($api_token);
        
        // image upload
        if($request->has("website_logo")){
            $saveData['website_logo'] = $request->file("website_logo")->store("website");
        }

        $status = WebsiteSetting::updateOrCreate(
            ['created_by' => ApiHelper::get_adminid_from_token($api_token)],
            $saveData
        );

        if ($status)
            return ApiHelper::JSON_RESPONSE(true,$status,'SUCCESS_WEBSITE_DETAILS_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],"ERROR_WEBSITE_DETAILS_UPDATE");
           
    }
    /*
        Notification setting
    */
    public function notification_first(Request $request){
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.notification')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $result = NotificationSetting::where('created_by', ApiHelper::get_adminid_from_token($api_token))->first();
        return ApiHelper::JSON_RESPONSE(true,$result,'SUCCESS_NOTIFICATION_DETAILS_UPDATE');
    }
    public function notification(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'setting.notification')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $saveData = $request->except('api_token');
        $saveData['created_by'] = ApiHelper::get_adminid_from_token($api_token);
        
        $status = NotificationSetting::updateOrCreate(
            ['created_by' => ApiHelper::get_adminid_from_token($api_token)],
            $saveData
        );

        if ($status)
            return ApiHelper::JSON_RESPONSE(true,$status,'SUCCESS_NOTIFICATION_DETAILS_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],"ERROR_NOTIFICATION_DETAILS_UPDATE");
           
    }
    public function index(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        $listData = [];

        $list = Setting::select('setting_key','setting_value')
                ->where('user_id',ApiHelper::get_user_id_from_token($api_token))
                ->get();
        
        if(!empty($list)){
            foreach ($list as $key => $value) {
                $listData[$value['setting_key']] = $value['setting_value'];            
            }
        }
        

        $res = [];
        $res['settingInfo'] = $listData;
        $res['currency'] = Currency::select('currencies_code as label','currencies_code as value')->where('status', 1)->get();
        $res['language'] =Language::select('languages_code as label','languages_code as value')->where('status', 1)->get();
        $res['timezone'] = TimeZone::all();

        return ApiHelper::JSON_RESPONSE(true,$res,'');

    }

    public function store(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        
        // if(!ApiHelper::is_page_access($api_token,'setting.notification')){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }

        $saveData = $request->except('api_token');
        foreach ($saveData as $key => $value) {
            $insertData = [
                'user_id'=>ApiHelper::get_user_id_from_token($api_token),
                'setting_key'=>strtoupper($key),
                'setting_value'=>$request->$key
            ];
            Setting::updateOrCreate([
                'user_id'=>ApiHelper::get_user_id_from_token($api_token),
                'setting_key'=>strtoupper($key),
            ],$insertData);
        }

        broadcast( new GlobalEventBetweenSuperAdminAndAdmin('SUCCESS_SETTING_DETAILS_UPDATE'))->toOthers();
        
        return ApiHelper::JSON_RESPONSE(true,$saveData,'SUCCESS_SETTING_DETAILS_UPDATE');
           
    }

}
