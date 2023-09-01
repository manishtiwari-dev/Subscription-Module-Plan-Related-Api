<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\LandingPlan;
use App\Models\LandingPlanGroup;
use App\Models\LandingPlanPrice;

use Illuminate\Http\Request;
use ApiHelper;
use App\Mail\StatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\StatusUpdateMail;

class LandingPlanPriceController extends Controller
{
    public $page = 'service_plan';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    //This Function is used to show the list of plan
    public function index(Request $request)
    {
        $cName = '';
        $grpId = '';
        $groupName = '';
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
        $plan_query = LandingPlanPrice::where('plan_id', $request->plan_id);
        /*Checking if search data is not empty*/
        if (!empty($search))
            $plan_query = $plan_query
                ->where("plan_price", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $plan_query = $plan_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $plan_query = $plan_query->orderBy('plan_duration', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;
        $plan_count = $plan_query->count();
        $plan_list = $plan_query->skip($skip)->take($perPage)->get();
        if (!empty($plan_list)) {
            $plan_list->map(function ($data) {
                // $data->status = ($data->status == "1")?'active':'deactive';
                $data->setup_fee_discount = ($data->setup_fee_discount == "1") ? 'Yes' : 'No';
                $data->discount_type = ($data->discount_type == 0) ? 'No' : (($data->discount_type == 1) ? 'Fixed' : 'Percentage');
                return $data;
            });
        }

        if ($request->has('plan_id')) {
            //getting plan Name
            $plnName = LandingPlan::where('plan_id', $request->plan_id)->first();
            $cName = !empty($plnName) ? $plnName->plan_name : '';
            $grpId = !empty($plnName) ? $plnName->group_id : '';
            $grpName = LandingPlanGroup::where('group_id', $grpId)->first();
            $groupName = !empty($grpName) ? $grpName->group_name : '';
        }

        /*Binding data into a variable*/
        $res = [
            'data' => $plan_list,
            'plan_name' => $cName,
            'groupName' => $groupName,
            'grpId' => $grpId,
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


        $data = LandingPlanPrice::create([
            'plan_id' => $plan_id,
            'plan_price' => $plan_price,
            'plan_duration' => $plan_duration,
            'plan_discount' => $plan_discount,
            'discount_type' => $discount_type,
            'setup_fee' => $setup_fee,
            'setup_fee_discount' => $setup_fee_discount,


        ]);


        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_SERVICE_PLAN_PRICE_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_SERVICE_PLAN_PRICE_ADD');
        }
    }



    //This Function is used to show the particular plan data
    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $id = $request->id;

        $data = LandingPlanPrice::where('id', $id)->first();
        $plan_list = LandingPlan::all();
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



        /*updating plan data */
        $data = LandingPlanPrice::where('id', $id)->update([
            'plan_id' => $plan_id,
            'plan_price' => $plan_price,
            'plan_duration' => $plan_duration,
            'plan_discount' => $plan_discount,
            'discount_type' => $discount_type,
            'setup_fee' => $setup_fee,
            'setup_fee_discount' => $setup_fee_discount,


        ]);

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_SERVICE_PLAN_PRICE_UPDATE');
    }

    //This Function is used to get the change the plan status
    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $id = $request->id;
        $sub_data = LandingPlanPrice::where('id', $id)->first();

        if ($sub_data->status == '1') {
            $data = LandingPlanPrice::where('id', $id)->update(['status' => '0']);
            $status = 'Deactivated';
        } else {
            $data = LandingPlanPrice::where('id', $id)->update(['status' => '1']);
            $status = 'Activated';
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_STATUS_UPDATE');
    }


    public function create(Request $request)
    {
        $api_token = $request->api_token;
        $plan_list = LandingPlan::all();
        $res = [

            'plan_list' => $plan_list,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }
}
