<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionPlanToIndustry;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use ApiHelper;
use App\Mail\StatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\StatusUpdateMail;
use App\Models\Industry;


class PlanController extends Controller
{
    public $page = 'plan';
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
        $perPage = !empty($request->perPage) ? (int)$request->perPage : 10;
        $search = $request->search;
        $sortBy = $request->sortBy;
        $ASCTYPE = $request->orderBy;

        /*Fetching plan data*/
        $plan_query = SubscriptionPlan::query();
        /*Checking if search data is not empty*/
        if (!empty($search))
            $plan_query = $plan_query
                ->where("plan_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $plan_query = $plan_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $plan_query = $plan_query->orderBy('sort_order', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;
        $plan_count = $plan_query->count();
        $plan_list = $plan_query->skip($skip)->take($perPage)->get();
        if (!empty($plan_list)) {
            $plan_list->map(function ($data) {
                $data->discount_type = ($data->discount_type == 0) ? 'No' : (($data->discount_type == 1) ? 'Fixed' : 'Percentage');
                return $data;
            });
        }

        $plan_list = $plan_list->map(function ($datalist) {
            $permissionListBox = [];
            if (!empty($datalist->plan_to_industry)) {
                foreach ($datalist->plan_to_industry as $key => $per) {
                    $permissionListBox[$key] = $per->industry_name;
                }
            }
            $datalist->industry_name = implode(",", $permissionListBox);
            return $datalist;
        });
        /*Binding data into a variable*/
        $res = [
            'data' => $plan_list,
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

        $plan_name = $request->plan_name;
        $plan_desc = $request->plan_desc;

        $featured = $request->featured;
        $status = $request->status;
        $industry_id = explode(',', $request->industry_id);
        $validator = Validator::make($request->all(), [
            'plan_name' => 'required',

        ], [
            'plan_name.required' => 'PLAN_NAME_REQUIRED',

        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }
        $data = SubscriptionPlan::create([
            'plan_name' => $plan_name,
            'plan_desc' => $plan_desc,

            'featured' => $featured,
        ]);
        $plan_data = SubscriptionPlan::where('plan_id', $data['plan_id'])->first();
        $plan_industry = [];
        foreach ($industry_id as $key => $value) {
            $plan_industry = SubscriptionPlanToIndustry::create([
                'plan_id' => $plan_data->plan_id,
                'industry_id' => $value,
            ]);
        }

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PLAN_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PLAN_ADD');
        }
    }

    //This Function is used to get the details of plan data
    public function details(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $plan_id = $request->plan_id;
        $data = SubscriptionPlan::where('plan_id', $plan_id)->first();
        if (!empty($data)) {
            /* Fetching data of subscription*/
            $data->subscription = $data->subscription;
            $subscriber = [];

            foreach ($data->subscription as $key => $value) {
                $value->subscriber = $value->subscriber;
                $value->subscriber->business = $value->subscriber->business;
                $subscriber[$key] = $value;
            }
            /* Fetching data of subscriber*/
            $data->subscription = $subscriber;
        }
        return ApiHelper::JSON_RESPONSE(true, $data, '');
    }

    //This Function is used to show the particular plan data
    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $plan_id = $request->plan_id;
        $data = SubscriptionPlan::where('plan_id', $plan_id)->first();
        $data->plan_to_industry = $data->plan_to_industry;
        $industry_list = Industry::select('industry_name as label', 'industry_id as value')->get();

        $res = [
            'data_list' => $data,

            'industry_list' => $industry_list,
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
        $plan_id = $request->plan_id;
        $plan_name = $request->plan_name;
        $plan_desc = $request->plan_desc;
        $featured = $request->featured;
        $status = $request->status;
        $industry_id = explode(',', $request->industry_id);
        /*validating data*/
        $validator = Validator::make($request->all(), [
            'plan_name' => 'required',
        ], [
            'plan_name.required' => 'PLAN_NAME_REQUIRED',

        ]);
        /*if validation fails then it will show error message*/
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }
        /*updating plan data after validation*/
        $data = SubscriptionPlan::where('plan_id', $plan_id)->update([
            'plan_name' => $plan_name,
            'plan_desc' => $plan_desc,
            'featured' => $featured,
            'status' => $status,
        ]);
        $industry_delete = SubscriptionPlanToIndustry::where('plan_id', $plan_id)->delete();
        $plan_industry = [];
        foreach ($industry_id as $key => $value) {
            $plan_industry = SubscriptionPlanToIndustry::create([
                'plan_id' => $plan_id,
                'industry_id' => $value,
            ]);
        }
        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PLAN_UPDATE');
    }

    //This Function is used to get the change the plan status
    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        $plan_id = $request->plan_id;
        $sub_data = SubscriptionPlanToIndustry::with('subscription_plan_details')->where('plan_id', $plan_id)->first();
        if ($sub_data->status == '1') {
            $data = SubscriptionPlanToIndustry::where('plan_id', $plan_id)->update(['status' => '0']);

            $data = $sub_data->subscription_plan_details()->where('plan_id', $plan_id)->update(['status' => '0']);
        } else {
            $data = SubscriptionPlanToIndustry::where('plan_id', $plan_id)->update(['status' => '1']);

            $data = $sub_data->subscription_plan_details()->where('plan_id', $plan_id)->update(['status' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }

    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $plan_id = $request->plan_id;
        $sort_order = $request->sort_order;
        $infoData =  SubscriptionPlan::find($plan_id);
        if (empty($infoData)) {
            $infoData = new SubscriptionPlan();
            $infoData->plan_id = $plan_id;
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
