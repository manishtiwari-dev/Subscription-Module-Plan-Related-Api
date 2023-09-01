<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionPlanToIndustry;
use App\Models\PlanPrice;
use App\Models\SubscriptionPlan;

use Illuminate\Http\Request;
use ApiHelper;
use App\Mail\StatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\StatusUpdateMail;

class PlanPriceController extends Controller
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
        //ApiHelper::perPageItem()
        $perPage = !empty($request->perPage) ? (int)$request->perPage : 10;
        $search = $request->search;
        $sortBy = $request->sortBy;
        $ASCTYPE = $request->orderBy;


        /*Fetching plan data*/
        $plan_query = PlanPrice::where('plan_id', $request->plan_id);
        /*Checking if search data is not empty*/
        if (!empty($search))
            $plan_query = $plan_query
                ->where("plan_price", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $plan_query = $plan_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $plan_query = $plan_query->orderBy('id', 'ASC');
        }


        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $plan_count = $plan_query->count();

        $plan_list = $plan_query->skip($skip)->take($perPage)->get();

        if (!empty($plan_list)) {
            $plan_list->map(function ($data) {
                $data->setup_fee_discount = ($data->setup_fee_discount == "1") ? 'Yes' : 'No';

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

        if ($request->has('plan_id')) {
            //getting plan Name
            $plnName = SubscriptionPlan::where('plan_id', $request->plan_id)->first();
            $cName = !empty($plnName) ? $plnName->plan_name : '';
        }

        /*Binding data into a variable*/
        $res = [
            'data' => $plan_list,
            'plan_name' => $cName,
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

        $plan_id = $request->plan_id;
        $plan_price = $request->plan_price;
        $plan_duration = $request->plan_duration;
        $plan_discount = $request->plan_discount;
        $discount_type = $request->discount_type;
        $setup_fee = $request->setup_fee;
        $setup_fee_discount = $request->setup_fee_discount;
        $status = $request->status;

        $data = PlanPrice::create([
            'plan_id' => $plan_id,
            'plan_price' => $plan_price,
            'plan_duration' => $plan_duration,
            'plan_discount' => $plan_discount,
            'discount_type' => $discount_type,
            'setup_fee' => $setup_fee,
            'setup_fee_discount' => $setup_fee_discount,
        ]);


        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PLAN_PRICE_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PLAN_PRICE_ADD');
        }
    }

    //This Function is used to get the details of plan data
    public function details(Request $request)
    {
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
        $id = $request->id;
        $data = PlanPrice::where('id', $id)->first();
        $plan_list = SubscriptionPlan::all();
        $res = [
            'data_list' => $data,
            'plan_list' => $plan_list,
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
        $id = $request->id;
        $plan_id = $request->plan_id;
        $plan_price = $request->plan_price;
        $plan_duration = $request->plan_duration;
        $plan_discount = $request->plan_discount;
        $discount_type = $request->discount_type;
        $setup_fee = $request->setup_fee;
        $setup_fee_discount = $request->setup_fee_discount;

        $status = $request->status;
        /*validating data*/
        // $validator = Validator::make($request->all(),[
        //     'plan_name' => 'required',
        //     'plan_amount' => 'required',
        //     'plan_duration' => 'required',
        // ],[
        //     'plan_name.required'=>'PLAN_NAME_REQUIRED',
        //     'plan_amount.required'=>'PLAN_AMOUNT_REQUIRED',
        //     'plan_duration.required'=>'PLAN_DURATION_REQUIRED',
        // ]);
        // /*if validation fails then it will show error message*/
        // if ($validator->fails()) {
        //     return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        // }
        /*updating plan data after validation*/
        $data = PlanPrice::where('id', $id)->update([
            'plan_id' => $plan_id,
            'plan_price' => $plan_price,
            'plan_duration' => $plan_duration,
            'plan_discount' => $plan_discount,
            'discount_type' => $discount_type,
            'setup_fee' => $setup_fee,
            'setup_fee_discount' => $setup_fee_discount,

            'status' => $status,
        ]);

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PLAN_PRICE_UPDATE');
    }

    //This Function is used to get the change the plan status
    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $id = $request->id;
        $sub_data = PlanPrice::where('id', $id)->first();
        if ($sub_data->status == '1') {
            $data = PlanPrice::where('id', $id)->update(['status' => '0']);
            $status = 'Deactivated';
        } else {
            $data = PlanPrice::where('id', $id)->update(['status' => '1']);
            $status = 'Activated';
        }
        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }


    public function create(Request $request)
    {
        $api_token = $request->api_token;
        $plan_list = SubscriptionPlan::all();
        $res = [

            'plan_list' => $plan_list,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }
}
