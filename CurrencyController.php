<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Currency;
use Illuminate\Http\Request;
use ApiHelper;

class CurrencyController extends Controller
{
    public $page = 'currency';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    public function index_all(Request $request)
    {

        $api_token = $request->api_token;

        $currency_list = Currency::where('status', '1')->get();

        return ApiHelper::JSON_RESPONSE(true, $currency_list, '');
    }

    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? (int)$request->perPage : ApiHelper::perPageItem();
        $search = $request->search;
        $sortBy = $request->sortBy;
        $ASCTYPE = $request->orderBy;

        $currency_query = Currency::select('currencies_id', 'currencies_name', 'currencies_code', 'symbol_left', 'symbol_right', 'decimal_point', 'thousands_point', 'decimal_places', 'value',  'status', 'sort_order');

        if (!empty($search))
            $currency_query = $currency_query->where("currencies_name", "LIKE", "%{$search}%")->orWhere("currencies_code", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBy) && !empty($ASCTYPE))
            $currency_query = $currency_query->orderBy($sortBy, $ASCTYPE);
        else
            $currency_query = $currency_query->orderBy('currencies_name', 'ASC');

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $currency_count = $currency_query->count();

        $currency_list = $currency_query->skip($skip)->take($perPage)->get();

        $currency_list = $currency_query->get();

        // $currency_list = $currency_list->map(function($currency_status){
        //     if($currency_status->status=='0'){
        //         $currency_status->status = 'Deactive';
        //     }else{
        //         $currency_status->status = 'Active';
        //     }
        //     return $currency_status;
        // });


        $res = [
            'data' => $currency_list,
            'current_page' => $current_page,
            'total_records' => $currency_count,
            'total_page' => ceil((int)$currency_count / (int)$perPage),
            'per_page' => $perPage,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function store(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $api_token = $request->api_token;
        $currencies_id = $request->currencies_id;
        $currencies_name = $request->currencies_name;
        $currencies_code = $request->currencies_code;
        $symbol_left = $request->symbol_left;
        $symbol_right = $request->symbol_right;
        $decimal_point = $request->decimal_point;
        $thousands_point = $request->thousands_point;
        $decimal_places = $request->decimal_places;
        $value = $request->value;
        $sort_order = $request->sort_order;

        $validator = Validator::make($request->all(), [
            'currencies_name' => 'required',
            'currencies_code' => 'required',
            'decimal_point' => 'required',
            'thousands_point' => 'required',
            'decimal_places' => 'required',
            'value' => 'required',
        ], [
            'currencies_name.required' => 'CURRENCIES_NAME_REQUIRED',
            'currencies_code.required' => 'CURRENCIES_CODE_REQUIRED',
            'decimal_point.required' => 'DECIMAL_POINT_REQUIRED',
            'thousands_point.required' => 'THOUSANDS_POINT_REQUIRED',
            'decimal_places.required' => 'DECIMAL_PLACES_REQUIRED',
            'value.required' => 'VALUE_REQUIRED',
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());


        $data = Currency::create([
            'currencies_name' => $currencies_name,
            'currencies_code' => $currencies_code,
            'symbol_left' => $symbol_left,
            'symbol_right' => $symbol_right,
            'decimal_point' => $decimal_point,
            'thousands_point' => $thousands_point,
            'decimal_places' => $decimal_places,
            'value' => $value,
            'sort_order' =>  $sort_order
        ]);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data->id, 'SUCCESS_CURRENCY_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_CURRENCY_ADD');
    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $data_list = Currency::where('currencies_id', $request->currencies_id)->first();
        return ApiHelper::JSON_RESPONSE(true, $data_list, '');
    }

    public function update(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $currencies_id = $request->currencies_id;
        $currencies_name = $request->currencies_name;
        $currencies_code = $request->currencies_code;
        $symbol_left = $request->symbol_left;
        $symbol_right = $request->symbol_right;
        $decimal_point = $request->decimal_point;
        $thousands_point = $request->thousands_point;
        $decimal_places = $request->decimal_places;
        $value = $request->value;

        $validator = Validator::make($request->all(), [
            'currencies_name' => 'required',
            'currencies_code' => 'required',
            'decimal_point' => 'required',
            'thousands_point' => 'required',
            'decimal_places' => 'required',
            'value' => 'required',
        ], [
            'currencies_name.required' => 'CURRENCIES_NAME_REQUIRED',
            'currencies_code.required' => 'CURRENCIES_CODE_REQUIRED',
            'decimal_point.required' => 'DECIMAL_POINT_REQUIRED',
            'thousands_point.required' => 'THOUSANDS_POINT_REQUIRED',
            'decimal_places.required' => 'DECIMAL_PLACES_REQUIRED',
            'value.required' => 'VALUE_REQUIRED',
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());

        $data = Currency::where('currencies_id', $currencies_id)->update([
            'currencies_name' => $currencies_name,
            'currencies_code' => $currencies_code,
            'symbol_left' => $symbol_left,
            'symbol_right' => $symbol_right,
            'decimal_point' => $decimal_point,
            'thousands_point' => $thousands_point,
            'decimal_places' => $decimal_places,
            'value' => $value,
        ]);
        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_CURRENCY_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_CURRENCY_UPDATE');
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }


        $currencies_id = $request->currencies_id;
        $sub_data = Currency::where('currencies_id', $currencies_id)->first();

        if ($sub_data->status == '1') {
            $data = Currency::where('currencies_id', $currencies_id)->update(['status' => '0']);
        } else {
            $data = Currency::where('currencies_id', $currencies_id)->update(['status' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }



    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $currencies_id = $request->currencies_id;
        $sort_order = $request->sort_order;
        $infoData =  Currency::find($currencies_id);
        if (empty($infoData)) {
            $infoData = new Currency();
            $infoData->currencies_id = $currencies_id;
            $infoData->sort_order = $sort_order;
            $infoData->status = 1;
            $infoData->save();
        } else {
            $infoData->sort_order = $sort_order;
            $infoData->save();
        }

        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_SORT_ORDER_UPDATE');
    }
}
