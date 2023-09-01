<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\LandingPlanGroup;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\Currency;

class LandingPlanGroupController extends Controller
{
    public $page = 'service_plan';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    //This Function is used to show the list of plan
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
        $ASCTYPE = $request->orderBY;

        /*Fetching plan data*/
        $plan_query = LandingPlanGroup::query();
        /*Checking if search data is not empty*/
        if (!empty($search))
            $plan_query = $plan_query
                ->where("group_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBy) && !empty($ASCTYPE)) {

            $plan_query = $plan_query->orderBy($sortBy, $ASCTYPE);
        } else {
            $plan_query = $plan_query->orderBy('group_id', 'ASC');
        }


        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $plan_count = $plan_query->count();

        $plan_grp_list = $plan_query->skip($skip)->take($perPage)->get();

        if (!empty($plan_grp_list)) {
            $plan_grp_list->map(function ($data) {
                //    $data->status = ($data->status == "1")?'active':'deactive';
                //   $data->planTrial = ($data->plan_trial == "1") ? 'Yes' : 'No';

                return $data;
            });
        }


        /*Binding data into a variable*/
        $res = [
            'data' => $plan_grp_list,
            'current_page' => $current_page,
            'total_records' => $plan_count,
            'total_page' => ceil((int)$plan_count / (int)$perPage),
            'per_page' => $perPage,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }
    //This Function is used to add the plan data
    public function add(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $group_name = $request->group_name;
        $group_key = $request->group_key;
        $plan_trial = $request->plan_trial;
        $trial_day = $request->trial_day;
        $currency = $request->currency;

        $validator = Validator::make($request->all(), [
            'group_name' => 'required',

        ], [
            'group_name.required' => 'GROUP_NAME_REQUIRED',

        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }
        $data = LandingPlanGroup::create([
            'group_name' => $group_name,
            'group_key' => $group_key,
            'plan_trial' => $plan_trial,
            'trial_day' => $trial_day,
            'currency' => $currency

        ]);
        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PLAN_GROUP_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PLAN_GROUP_ADD');
        }
    }


    //This Function is used to show the particular plan data
    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $group_id = $request->group_id;
        $data = LandingPlanGroup::find($group_id);
        if (!empty($data->currency))
            // return ApiHelper::JSON_RESPONSE(true, $data->currency, '');
            $data->selected_currency = Currency::select('currencies_name as label', 'currencies_code as value')->where('currencies_code', $data->currency)->get();

        $data->selected_currency =  $data->selected_currency ==  '' ? [''] :  $data->selected_currency;









        $currencyList = array();
        $curryList = Currency::all();
        foreach ($curryList as $key => $curr) {
            if (!empty($curr))
                array_push(
                    $currencyList,
                    [
                        "value" => $curr->currencies_code,
                        "label" => $curr->currencies_code . '-' . $curr->currencies_name,
                    ]
                );
        }

        $res = [
            'data_list' => $data,
            'currencyList' => $currencyList,

        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    //This Function is used to update the particular plan data
    public function update(Request $request)
    {

        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        /*fetching data from api*/
        $group_id = $request->group_id;
        $group_name = $request->group_name;
        $group_key = $request->group_key;
        $plan_trial = $request->plan_trial;
        $trial_day = $request->trial_day;
        $currency = $request->currency;

        /*validating data*/
        $validator = Validator::make($request->all(), [
            'group_name' => 'required',
        ], [
            'group_name.required' => 'GROUP_NAME_REQUIRED',

        ]);
        /*if validation fails then it will show error message*/
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }
        /*updating plan data after validation*/
        $data = LandingPlanGroup::where('group_id', $group_id)->update([
            'group_name' => $group_name,
            'group_key' => $group_key,
            'plan_trial' => $plan_trial,
            'trial_day' => $trial_day,
            'currency' => $currency
        ]);

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PLAN_GROUP_UPDATE');
    }

    //This Function is used to get the change the plan status
    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $group_id = $request->group_id;
        $sub_data = LandingPlanGroup::where('group_id', $group_id)->first();

        if ($sub_data->status == '1') {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['status' => '0']);
        } else {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['status' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }


    public function showPrice(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $group_id = $request->group_id;
        $sub_data = LandingPlanGroup::where('group_id', $group_id)->first();

        if ($sub_data->show_price == '1') {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['show_price' => '0']);
        } else {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['show_price' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PRICE_UPDATE');
    }



    public function showAnnualPrice(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $group_id = $request->group_id;
        $sub_data = LandingPlanGroup::where('group_id', $group_id)->first();

        if ($sub_data->show_annual_price == '1') {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['show_annual_price' => '0']);
        } else {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['show_annual_price' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_ANNUAL_PRICE_UPDATE');
    }


    public function planTrial(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $group_id = $request->group_id;
        $sub_data = LandingPlanGroup::where('group_id', $group_id)->first();

        if ($sub_data->plan_trial == '1') {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['plan_trial' => '0']);
        } else {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['plan_trial' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PLAN_TRIAL_UPDATE');
    }


    public function recurringChange(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $group_id = $request->group_id;
        $sub_data = LandingPlanGroup::where('group_id', $group_id)->first();

        if ($sub_data->recurring == '1') {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['recurring' => '0']);
        } else {
            $data = LandingPlanGroup::where('group_id', $group_id)->update(['recurring' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_RECURRING_UPDATE');
    }


    public function create(Request $request)
    {
        $api_token = $request->api_token;
        $currencyList = array();
        $curryList = Currency::all();
        foreach ($curryList as $key => $curr) {
            if (!empty($curr))
                array_push(
                    $currencyList,
                    [
                        "value" => $curr->currencies_code,
                        "label" => $curr->currencies_code . '-' . $curr->currencies_name,
                    ]
                );
        }


        $res = [

            'currencyList' => $currencyList,

        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }
}
