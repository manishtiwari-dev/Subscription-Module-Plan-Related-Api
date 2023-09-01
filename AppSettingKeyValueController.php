<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\Setting;

use App\Models\Super\AppSettingsGroup;
use App\Models\Super\DateFormat;
use App\Models\Super\TimeFormat;

use App\Models\PaymentSetting;
use App\Models\WebsiteSetting;
use App\Models\NotificationSetting;
use App\Models\Currency;
use App\Models\Language;
use App\Models\TimeZone;
use App\Models\Country;
use App\Models\PaymentMethods;
use App\Models\Super\ShippingMethods;
use Modules\CRM\Models\CRMSettingTaxGroup;

use App\Events\GlobalEventBetweenSuperAdminAndAdmin;


class AppSettingKeyValueController extends Controller
{
    /**
         Display a listing of the resource.

     */

    public $page = 'general-setting';
    public $superpage = 'super-general-setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';



    public function index(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        $userType = $request->userType;
        $page_access =  $userType == 'subscriber' ? $this->page : $this->superpage;
        if (!ApiHelper::is_page_access($api_token, $page_access, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');



        $key_list = AppSettingsGroup::with(['settings' => function ($query) {
            $query->orderBy('sort_order', 'ASC')->where('status', 1);
        }])->where(function ($qu) use ($userType) {
            $qu->where('access_privilege', 0)->orWhere('access_privilege', ($userType == 'administrator') ? 2 : 1);
        })->where('status', 1)->orderBy('sort_order', 'ASC')->get();

        if (!empty($key_list)) {
            $key_list->map(function ($groupKey) {

                if (!empty($groupKey->settings)) {
                    $groupKey->settings->map(function ($keys) {

                        $res = Setting::where('setting_key', $keys->setting_key)->first();

                        if ($keys->option_type == 'image') {
                            $key_image = $keys->setting_key . '_image';
                            $keys->$key_image = !empty($res) ? ApiHelper::getFullImageUrl($res->setting_value, '') : '';

                            $keys->setting_value = !empty($res) ? $res->setting_value : '';
                        } else if (isset($res->setting_key) && $res->setting_key == 'tax_group') {
                            $tax = CRMSettingTaxGroup::where('tax_group_id', $res->setting_value)->first();
                            $keys->setting_value = !empty($tax) ? $tax->tax_group_name : '';
                        } else
                            $keys->setting_value = !empty($res) ? $res->setting_value : '';

                        return $keys;
                    });
                }

                return $groupKey;
            });
        }



        //$data_list = WebsiteSetting::all();

        // attaching image url of logo etc..
        // if(!empty($data_list)){
        //     foreach ($data_list as $key => $data) {
        //         $data->settings->map(function($set){
        //             $all_image_key = ["website_logo","website_favicon","preloader_image"];
        //             $key_image = $set->setting_key.'_image'; 
        //             $set->$key_image = in_array($set->setting_key, $all_image_key) 
        //                                 ? ApiHelper::getFullImageUrl($set->setting_value, '') 
        //                                 : '';
        //             return $set;
        //         });
        //     }
        // }

        $currency = Currency::selectRaw('CONCAT(currencies_code," - ", currencies_name)  as label, currencies_code as value')->where('status', 1)->get();

        $language = Language::selectRaw('CONCAT(languages_code, " - ", languages_name )  as label, languages_code as value')->where('status', 1)->get();

        $country = Country::select('countries_name as label', 'countries_iso_code_2 as value')->get();

        $timezone = TimeZone::select('timezone_location as label', 'timezone_location as value')->get();

        $timeformat = TimeFormat::selectRaw(' CONCAT(time_format, " (" ,time_example, ")")  as label , time_format as value')->get();

        $dateformat = DateFormat::selectRaw(' CONCAT(date_format, " (" ,date_example, ")")  as label , date_format as value')->get();

        $PaymentMethods = PaymentMethods::selectRaw('method_name as label, method_key as value')->where('status', 1)->get();

        $shippingMethods = ShippingMethods::selectRaw('method_name as label, method_key as value')->where('status', 1)->get();



        // $taxGroup=CRMSettingTaxGroup::selectRaw('tax_group_name as label, tax_group_id as value')->where('status', 1)->get();

        $taxGroup = CRMSettingTaxGroup::selectRaw(' tax_group_name  as label, tax_group_id as value')->where('status', 1)->get();


        //$taxGroup=CRMSettingTaxGroup::all();


        $res = [
            'data_list' => $key_list,
            'helperData' => [
                'currency' => $currency,
                'language' => $language,
                'country' => $country,
                'timezone' => $timezone,
                'timeformat' => $timeformat,
                'dateformat' => $dateformat,
                'payment_methods' => $PaymentMethods,
                'shipping_methods' => $shippingMethods,
                'taxGroup' => $taxGroup,
            ],
        ];


        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }


    public function store(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;

        $userType = ApiHelper::userType($api_token);
        $page_access =  $userType == 'subscriber' ? $this->page : $this->superpage;
        if (!ApiHelper::is_page_access($api_token, $page_access, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');


        $all_image_key = ["invoice_logo", "application_favicon", "application_logo", "landing_logo", "landing_favicon ", "landing_footer_logo"];

        $saveData = $request->formData;
        if ($saveData) {
            foreach ($saveData as $key => $value) {

                if (!empty($value)) {
                    if (in_array($key, $all_image_key)) {

                        ApiHelper::image_upload_with_crop($api_token,  $value, 1, 'assets', '', false);

                        //    ApiHelper::image_upload_with_crop($api_token, $value, 1,  $key, '', false);

                        //   ApiHelper::image_upload_with_crop($api_token,$value, 6, $key);
                        $setting_val = (isset($value)) ? $value : '';
                        Setting::updateOrCreate(
                            ['setting_key' => $key],
                            ['setting_value' => $setting_val]
                        );
                    }
                }

                if (!in_array($key, $all_image_key)) {
                    $setting_key =  $key;
                    $setting_val = (isset($value)) ? $value : '';

                    Setting::updateOrCreate(
                        ['setting_key' => $key],
                        ['setting_value' => $setting_val]
                    );
                }
            }
        }


        // broadcast( new GlobalEventBetweenSuperAdminAndAdmin('SUCCESS_SETTING_DETAILS_UPDATE'))->toOthers();

        return ApiHelper::JSON_RESPONSE(true, $saveData, 'SUCCESS_SETTING_DETAILS_UPDATE');
    }
}
