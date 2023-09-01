<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\LandingPlan;
use App\Models\LandingPlanGroup;


use Illuminate\Http\Request;
use ApiHelper;
use App\Mail\StatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\StatusUpdateMail;

class LandingPlanController extends Controller
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

        $cName =  '';

        /*Fetching plan data*/
        $plan_query = LandingPlan::where('group_id', $request->group_id);
        /*Checking if search data is not empty*/
        if (!empty($search))
            $plan_query = $plan_query
                ->where("plan_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBy) && !empty($ASCTYPE)) {

            $plan_query = $plan_query->orderBy($sortBy, $ASCTYPE);
        } else {
            $plan_query = $plan_query->orderBy('sort_order', 'ASC');
        }


        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $plan_count = $plan_query->count();

        $plan_list = $plan_query->skip($skip)->take($perPage)->get();

        if (!empty($plan_list)) {
            $plan_list->map(function ($data) {
                //    $data->status = ($data->status == "1")?'active':'deactive';

                $data->isFeatured = ($data->featured == "1") ? 'Yes' : 'No';

                return $data;
            });
        }


        if ($request->has('group_id')) {
            //getting category Name
            $grpName = LandingPlanGroup::where('group_id', $request->group_id)->first();
            $cName = !empty($grpName) ? $grpName->group_name : '';
        }


        /*Binding data into a variable*/
        $res = [
            'data' => $plan_list,
            'current_page' => $current_page,
            'group_name' => $cName,
            'total_records' => $plan_count,
            'total_page' => ceil((int)$plan_count / (int)$perPage),
            'per_page' => $perPage,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }


    public function create(Request $request)
    {
        $api_token = $request->api_token;
        $grp_data = LandingPlanGroup::all();

        $data = [
            'grp_data' => $grp_data,

        ];

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data, '');
        else
            return ApiHelper::JSON_RESPONSE(false, [], '');
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
        $color = $request->color;

        $validator = Validator::make($request->all(), [
            'plan_name' => 'required',

        ], [
            'plan_name.required' => 'PLAN_NAME_REQUIRED',

        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }
        $data = LandingPlan::create([
            'plan_name' => $plan_name,
            'plan_desc' => $plan_desc,
            'color' => $color,
            'group_id' => $request->group_id

        ]);
        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PLAN_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PLAN_ADD');
        }
    }

    //This Function is used to get the details of plan data
    public function details(Request $request)
    {
        $plan_id = $request->plan_id;
        $data = LandingPlan::where('plan_id', $plan_id)->first();
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
        $data_list = LandingPlan::where('plan_id', $plan_id)->first();
        $grp_data = LandingPlanGroup::all();

        $data = [
            'grp_data' => $grp_data,
            'data_list' => $data_list
        ];

        return ApiHelper::JSON_RESPONSE(true, $data, '');
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

        $color = $request->color;

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
        $data = LandingPlan::where('plan_id', $plan_id)->update([
            'plan_name' => $plan_name,
            'plan_desc' => $plan_desc,

            'color' => $color,

            'group_id' => $request->group_id
        ]);

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_PLAN_UPDATE');
    }

    //This Function is used to get the change the plan status
    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $plan_id = $request->plan_id;
        $sub_data = LandingPlan::where('plan_id', $plan_id)->first();

        if ($sub_data->status == '1') {
            $data = LandingPlan::where('plan_id', $plan_id)->update(['status' => '0']);
            $status = 'Deactivated';
        } else {
            $data = LandingPlan::where('plan_id', $plan_id)->update(['status' => '1']);
            $status = 'Activated';
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }

    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $plan_id = $request->plan_id;
        $sort_order = $request->sort_order;
        $featureData =  LandingPlan::find($plan_id);
        $featureData->sort_order = $sort_order;
        $featureData->save();

        return ApiHelper::JSON_RESPONSE(true, $featureData, 'SUCCESS_SORT_ORDER_UPDATE');
    }


    public function isfeatured(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $plan_id = $request->plan_id;
        $sub_data = LandingPlan::where('plan_id', $plan_id)->first();

        if ($sub_data->featured == '1') {
            $data = LandingPlan::where('plan_id', $plan_id)->update(['featured' => '0']);
        } else {
            $data = LandingPlan::where('plan_id', $plan_id)->update(['featured' => '1']);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_IS_FEATURED_UPDATE');
    }
}
