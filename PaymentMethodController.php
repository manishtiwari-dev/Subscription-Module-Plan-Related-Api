<?php


namespace App\Http\Controllers\Api;

use App\Models\PaymentMethods;
use App\Models\PaymentMethodsDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use ApiHelper;
use Illuminate\Support\Str;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\Super\AppSettingGroupKey;

use App\Models\Language;
use App\Models\Country;


class PaymentMethodController extends Controller
{
    public $page = 'payment_method';
    public $pagedetails = 'payment_setting';
    public $superpagedetails = 'super_payment_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';



    //This Function is used to show the list of subscribers
    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? $request->perPage : 10;
        $search = $request->search;
        $sortBy = $request->sortBy;
        $orderBy = $request->orderBy;
        /*Fetching subscriber data*/
        $payment_methods_query = PaymentMethods::with('payment_methods_details');
        /*Checking if search data is not empty*/
        if (!empty($search))
            $payment_methods_query = $payment_methods_query
                ->where("method_name", "LIKE", "%{$search}%");
        /* order by sorting */
        if (!empty($sortBy) && !empty($orderBy))
            $payment_methods_query = $payment_methods_query->orderBy($sortBy, $orderBy);
        else
            $payment_methods_query = $payment_methods_query->orderBy('payment_method_id', 'DESC');
        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;
        $payment_count = $payment_methods_query->count();
        $payment_list = $payment_methods_query->skip($skip)->take($perPage)->get();

        $payment_list = $payment_list->map(function ($payment) {
            $payment->payment_method = $payment->method_name;
            $payment->method_logo = ApiHelper::getFullImageUrl($payment->method_logo, 'index-list');
            //    $payment_status = ['InActive', 'Active', 'Disabled'];
            //      $payment->status = $payment_status[(int)$payment->status];
            $permissionListBox = [];
            if (!empty($payment->payment_methods_details)) {
                foreach ($payment->payment_methods_details as $key => $per) {
                    $permissionListBox[$key] = $per->method_key;
                }
            }
            $payment->methodKey = implode(",", $permissionListBox);
            return $payment;
        });


        $countryList = [];
        $countryName = Country::where('countries_id', $request->countries_id)->first();
        if (!empty($countryName)) {
            $countryName = $countryName->countries_name;
            $countryList = explode(',', $countries_name);
        }

        if (!empty($payment_list)) {
            $payment_list->map(function ($payment) use ($countryList) {

                $payment->shiping_status = in_array($payment->payment_method_id, $countryList) ? true : false;

                $countryString = [];

                $selectedCountry = Country::select('countries_name')->whereRaw('countries_id IN(' . $payment->enabled_country . ') ')->get();
                if (!empty($selectedCountry)) {
                    foreach ($selectedCountry as $key => $country) {
                        array_push($countryString, $country->countries_name);
                    }
                }

                $payment->selectedCountry = implode(',', $countryString);


                return $payment;
            });
        }

        /*Binding data into a variable*/
        $res = [
            'data' => $payment_list,
            'current_page' => $current_page,
            'total_records' => $payment_count,
            'total_page' => ceil((int)$payment_count / (int)$perPage),
            'per_page' => $perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }


    public function create(Request $request)
    {
        $api_token = $request->api_token;
        $country_list = Country::select('countries_name as label', 'countries_id as value')->get();

        $res = [
            'country_list' => $country_list,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }



    public function list(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        $userType = ApiHelper::userType($api_token);
        $page_access = ($userType == 'subscriber' ? $this->pagedetails : $this->superpagedetails);
        if (!ApiHelper::is_page_access($api_token, $page_access, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? (int)$request->perPage : ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $whereQ  = ApiHelper::convertToSingleQuote(ApiHelper::getKeySetVal('payment_method_enable'));


        $data_list = PaymentMethods::with('payment_methods_details')->where(
            function ($query) {
                return $query
                    ->where('is_key_managable', '1')
                    ->orWhere('status', 1);
            }
        )
            ->whereRaw('method_key IN (' . $whereQ . ')')->get();


        // payment_method_enable setting  info
        $paymentIMethodList = [];
        $payment_method_enable = AppSettingGroupKey::where('setting_key', 'payment_method_enable')->first();
        if (!empty($payment_method_enable)) {
            $setting_value = $payment_method_enable->setting_value;
            $paymentIMethodList = explode(',', $setting_value);
        }

        if (!empty($data_list)) {
            $data_list->map(function ($payment) use ($paymentIMethodList) {

                // payment status
                $payment->setting_status = in_array($payment->payment_method_id, $paymentIMethodList) ? true : false;

                // payment key
                if (!empty($payment->payment_methods_details)) {
                    $payment->payment_methods_details->map(function ($key) {

                        //$res = AppSetting::where('setting_key', $key->method_key)->first();
                        $res = Setting::where('setting_key', $key->method_key)->first();
                        $key->method_value = (!empty($res)) ? $res->setting_value : '';
                        return $key;
                    });
                }

                return $payment;
            });
        }

        $res = ['data_list' => $data_list];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }


    //This Function is used to get the details of subscriber data

    public function details(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $subscriber_id = $request->subscriber_id;
        $data = PaymentMethods::where('subscriber_id', $subscriber_id)->first();
        if (!empty($data)) {
            /* Fetching data of business*/
            $data->business = $data->business;
            $subscriber_history = [];
            /* Fetching data of subscription*/
            $data->subscription = $data->subscription;
            foreach ($data->subscription as $key => $value) {
                $value->subscriber_history = $value->subscriber_history;
                $value->subscription_plan = $value->subscription_plan;
                $subscriber_history[$key] = $value;
            }
            /* Fetching data of subscriber history*/
            $data->subscription = $subscriber_history;
        }
        return ApiHelper::JSON_RESPONSE(true, $data, '');
    }

    //This Function is used to show the particular subscriber data
    public function edit(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $payment_method_id = $request->payment_method_id;
        $data = PaymentMethods::where('payment_method_id', $payment_method_id)->first();
        $permissionListBox = [];

        if (!empty($data->payment_methods_details)) {
            foreach ($data->payment_methods_details as $key => $per) {
                $permissionListBox[$key] = $per->method_key;
            }
        }
        $data->method_key = implode(",", $permissionListBox);

        $data->selectedCountry = Country::select('countries_name as label', 'countries_id as value')->whereRaw('countries_id IN(' . $data->enabled_country . ') ')->get();

        $country_list = Country::select('countries_name as label', 'countries_id as value')->get();

        $res = [
            'country_list' => $country_list,
            'data_list' => $data
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    //This Function is used to update the particular subscriber data
    public function update(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        /*fetching data from api*/
        $method_name = $request->method_name;
        $method_description = $request->method_description;
        $method_logo = $request->method_logo;
        $payment_method_id = $request->payment_method_id;
        $enabled_country = $request->enabled_country;
        $is_key_managable = $request->is_key_managable;


        if (empty($enabled_country)) {
            $enabled_country = 0;
        }

        /*validating data*/
        $validator = Validator::make($request->all(), [
            'method_name' => 'required',
            // 'method_key' => 'required',
        ], [
            'method_name.required' => 'METHOD_NAME_REQUIRED',
        ]);
        /*if validation fails then it will show error message*/
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }
        /*updating subscriber data after validation*/

        if (!empty($method_logo))
            ApiHelper::image_upload_with_crop($api_token, $method_logo, 1, 'payment', '', false);

        //    ApiHelper::image_upload_with_crop($api_token,$method_logo, 4, 'payment');

        $data = PaymentMethods::where('payment_method_id', $payment_method_id)->update([
            'method_name' => $method_name,
            'method_description' => $method_description,
            'method_logo' => $method_logo,
            'enabled_country' => $enabled_country,
            'sort_order' => $request->sort_order,
        ]);
        if ($is_key_managable == 1) {
            $payment_data = PaymentMethodsDetails::where('payment_method_id', $payment_method_id)->delete();
            $payment_data = [];
            if (strpos($request->method_key, ",")) {
                $method_key = explode(',', $request->method_key);
                foreach ($method_key as $key => $value) {
                    $payment_data = PaymentMethodsDetails::create([
                        'payment_method_id' => $payment_method_id,
                        'method_key' => $value,
                    ]);
                }
            } else {
                $payment_data = PaymentMethodsDetails::create([
                    'payment_method_id' => $payment_method_id,
                    'method_key' => $request->method_key,
                ]);
            }
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PAYMENT_UPDATE');
    }

    //This Function is used to add the subscriber data
    public function store(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $method_name = $request->method_name;
        $method_description = $request->method_description;
        $method_logo = $request->method_logo;
        $enabled_country = $request->enabled_country;
        $is_key_managable = $request->is_key_managable;


        if (empty($enabled_country)) {
            $enabled_country = 0;
        }

        $validator = Validator::make($request->all(), [
            'method_name' => 'required',
            // 'method_key' => 'required',
        ], [
            'method_name.required' => 'METHOD_NAME_REQUIRED',
            // 'method_key.required'=>'METHOD_KEY_REQUIRED',
        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        if (!empty($request->method_logo))
            ApiHelper::image_upload_with_crop($api_token, $method_logo, 1, 'payment', '', false);

        //  ApiHelper::image_upload_with_crop($api_token,$method_logo, 4, 'payment');

        $data = PaymentMethods::insertGetId([
            'method_name' => $method_name,
            'method_key' => Str::slug($method_name),
            'method_description' => $method_description,
            'method_logo' => $method_logo,
            'enabled_country' => $enabled_country,
            'sort_order' => $request->sort_order,
        ]);
        if ($is_key_managable == 1) {
            $payment_data = [];
            if (strpos($request->method_key, ",")) {
                $method_key = explode(',', $request->method_key);
                foreach ($method_key as $key => $value) {
                    $payment_data = PaymentMethodsDetails::create([
                        'payment_method_id' => $data,
                        'method_key' => $value,
                    ]);
                }
            } else {
                $payment_data = PaymentMethodsDetails::create([
                    'payment_method_id' => $data,
                    'method_key' => $request->method_key,
                ]);
            }
        }

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PAYMENT_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PAYMENT_ADD');
        }
    }



    public function payment_update(Request $request)
    {
        $keyList = $request->all();

        unset($keyList['api_token']);       // un sert api_token from array.

        // $all_image_key = [''];

        // store each payment key
        foreach ($keyList as $key => $list) {
            if (!empty($list)) {
                Setting::updateOrCreate(
                    ['setting_key' => $key],
                    [
                        'setting_value' => $list,
                    ]
                );
            }
        }


        return ApiHelper::JSON_RESPONSE(true, $keyList, 'SUCCESS_PAYMENT_UPDATE');
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $payment_method_id = $request->payment_method_id;
        $status = $request->status;
        $sub_data = PaymentMethods::where('payment_method_id', $payment_method_id)->first();

        if ($sub_data->status == '0') {
            $data = PaymentMethods::where('payment_method_id', $payment_method_id)->update(['status' => '1']);
            $status = 'Active';
        } elseif ($sub_data->status == '1') {
            $data = PaymentMethods::where('payment_method_id', $payment_method_id)->update(['status' => '0']);
            $status = 'InActive';
        }
        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }
}
