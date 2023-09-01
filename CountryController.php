<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Country;
use Illuminate\Http\Request;
use ApiHelper;

class CountryController extends Controller
{
    public $page = 'country';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';





    public function index_all(Request $request)
    {

        $api_token = $request->api_token;

        $country_list = Country::all();

        return ApiHelper::JSON_RESPONSE(true, $country_list, '');
    }

    public function index(Request $request)
    {

        // Validate user page access
        $start_time = microtime();
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? (int)$request->perPage : ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $data_query = Country::select('countries_id', 'countries_name', 'countries_iso_code_2', 'countries_iso_code_3', 'currencies_id', 'time_zone_id', 'languages_id', 'utc_time', 'address_format_id');

        // search
        if (!empty($search))
            $data_query = $data_query->where("countries_name", "LIKE", "%{$search}%")
                ->orWhere("countries_iso_code_2", "LIKE", "%{$search}%")
                ->orWhere("countries_iso_code_3", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $data_query = $data_query->orderBy('countries_id', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;     // apply page logic

        $data_count = $data_query->count(); // get total count

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_query->get();

        $data_list = $data_list->map(function ($data) {
            if (!empty($data->currency)) {
                $data->currency_code = $data->currency->currencies_code;
            } else {
                $data->currency_code = '';
            }
            return $data;
        });
        $data_list = $data_list->map(function ($time) {
            $time->timezone_location = $time->timezone->timezone_location;
            return $time;
        });
        $data_list = $data_list->map(function ($lang) {
            if (!empty($lang->language)) {
                $lang->language_name = $lang->language->languages_name;
            } else {
                $lang->language_name = '';
            }
            return $lang;
        });

        $end_time = microtime();
        $actual_time = (float)($end_time) - (float)($start_time);
        $res = [
            'data' => $data_list,
            'current_page' => $current_page,
            'total_records' => $data_count,
            'total_page' => ceil((int)$data_count / (int)$perPage),
            'per_page' => $perPage,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'actual_time' => $actual_time,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }


    public function store(Request $request)
    {
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }


        $countries_id = $request->countries_id;
        $countries_name = $request->countries_name;
        $countries_iso_code_2 = $request->countries_iso_code_2;
        $countries_iso_code_3 = $request->countries_iso_code_3;
        $currencies_id = $request->currencies_id;
        $languages_id = $request->languages_id;
        $time_zone_id = $request->time_zone_id;
        $utc_time = $request->utc_time;
        $address_format_id = $request->address_format_id;

        $validator = Validator::make($request->all(), [
            'countries_name' => 'required',
            'countries_iso_code_2' => 'required|max:2',
            'countries_iso_code_3' => 'required|max:3',
            'currencies_id' => 'required',
            'languages_id' => 'required',
            'time_zone_id' => 'required',
            'utc_time' => 'required',
        ], [
            'countries_name.required' => 'COUNTRY_NAME_REQUIRED',
            'countries_iso_code_2.required' => 'COUNTRY_ISO_CODE_2_REQUIRED',
            'countries_iso_code_2.max' => 'COUNTRY_ISO_CODE_2_MAX',
            'countries_iso_code_3.required' => 'COUNTRY_ISO_CODE_3_REQUIRED',
            'countries_iso_code_3.max' => 'COUNTRY_ISO_CODE_3_MAX',
            'currencies_id.required' => 'CURRENCY_ID_REQUIRED',
            'time_zone_id.required' => 'TIMEZONE_2_REQUIRED',
            'utc_time.required' => 'UTC_TIME_REQUIRED',

        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());


        $data = Country::create([
            'countries_id' => $countries_id,
            'countries_name' => $countries_name,
            'countries_iso_code_2' => $countries_iso_code_2,
            'countries_iso_code_3' => $countries_iso_code_3,
            'currencies_id' => $currencies_id,
            'languages_id' => $languages_id,
            'time_zone_id' => $time_zone_id,
            'utc_time' => $utc_time,
            'address_format_id' => $address_format_id,
        ]);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data->id, 'SUCCESS_COUNTRY_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_COUNTRY_ADD');
    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;

        $data_list = Country::where('countries_id', $request->countries_id)->first();
        return ApiHelper::JSON_RESPONSE(true, $data_list, '');
    }

    public function update(Request $request)
    {
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $countries_id = $request->countries_id;
        $countries_name = $request->countries_name;
        $countries_iso_code_2 = $request->countries_iso_code_2;
        $countries_iso_code_3 = $request->countries_iso_code_3;
        $currencies_id = $request->currencies_id;
        $languages_id = $request->languages_id;
        $time_zone_id = $request->time_zone_id;
        $utc_time = $request->utc_time;

        $validator = Validator::make($request->all(), [
            'countries_name' => 'required',
            'countries_iso_code_2' => 'required|max:2',
            'countries_iso_code_3' => 'required|max:3',
            'currencies_id' => 'required',
            'languages_id' => 'required',
            'time_zone_id' => 'required',
            'utc_time' => 'required',
        ], [
            'countries_name.required' => 'COUNTRY_NAME_REQUIRED',
            'countries_iso_code_2.required' => 'COUNTRY_ISO_CODE_2_REQUIRED',
            'countries_iso_code_2.max' => 'COUNTRY_ISO_CODE_2_MAX',
            'countries_iso_code_3.required' => 'COUNTRY_ISO_CODE_3_REQUIRED',
            'countries_iso_code_3.max' => 'COUNTRY_ISO_CODE_3_MAX',
            'currencies_id.required' => 'CURRENCY_ID_REQUIRED',
            'time_zone_id.required' => 'TIMEZONE_2_REQUIRED',
            'utc_time.required' => 'UTC_TIME_REQUIRED',

        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());

        $data = Country::where('countries_id', $countries_id)->update(['countries_name' => $countries_name, 'countries_iso_code_2' => $countries_iso_code_2, 'countries_iso_code_3' => $countries_iso_code_3, 'currencies_id' => $currencies_id, 'languages_id' => $languages_id, 'time_zone_id' => $time_zone_id, 'utc_time' => $utc_time,]);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_COUNTRY_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_COUNTRY_UPDATE');
    }

    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $countries_id = $request->countries_id;

        $status = Country::where('countries_id', $countries_id)->delete();
        if ($status) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_COUNTRY_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_COUNTRY_DELETE');
        }
    }
}
