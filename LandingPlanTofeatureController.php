<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\Industry;
use Modules\Department\Models\Role;
use Illuminate\Support\Str;
use DB;
use App\Models\LandingPlanGroup;
use App\Models\LandinPlanFeature;
use App\Models\LandingPlanToFeature;
use App\Models\LandingPlan;
use Modules\Ecommerce\Models\Feature;

class LandingPlanTofeatureController extends Controller
{
    public $page = 'service_plan';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pageupdate = 'update';


    // sectionList 
    public function AllIndustryList(Request $request)
    {
        $api_token = $request->api_token;
        $language = $request->language;
        $planList = [];
        $usType = ($request->userType == 'administrator') ? 0 : 2;
        $utype = '1,' . $usType;
        $industryList = Industry::all();
        return ApiHelper::JSON_RESPONSE(true, $industryList, '');
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $cName =  '';
        // Validate user page access
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        // get all request val
        $language = $request->language;
        $usType = ($request->userType == 'administrator') ? 0 : 2;
        $utype = '1,' . $usType;

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? $request->perPage : 10;
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $industry_id = $request->industry_id;

        $feature_query = LandinPlanFeature::with('plan_to_feature')->where('group_id', $request->group_id)->where('parent_id', 0);
        $plan_data =  LandingPlan::where('group_id', $request->group_id)->orderBy('sort_order', 'ASC')->get();
        if (!empty($plan_data)) {
            $plan_data = $plan_data->map(function ($data) {
                $featureList = LandingPlanToFeature::where('plan_id', $data->plan_id)->pluck('status', 'feature_id')->toArray();

                $featureLimit = LandingPlanToFeature::where('plan_id', $data->plan_id)->pluck('feature_limit', 'feature_id')->toArray();


                $data->features = $featureList;
                $data->limits = $featureLimit;
                return $data;
            });
        }


        // search
        if (!empty($search))
            $feature_query = $feature_query->where("feature_title", "LIKE", "%{$search}%");
        // order by sorting 
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $feature_query = $feature_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $feature_query = $feature_query->orderBy('sort_order', 'ASC');
        }

        $data_list = $feature_query->orderBy('sort_order', 'ASC')->get();


        if (!empty($data_list)) {
            $data_list = $data_list->map(function ($data) {
                //  return ApiHelper::JSON_RESPONSE(true, $data->feature_id, '');

                $data->planIndu = LandingPlan::all()->map(function ($planSec) use ($data) {

                    // filter record template_id, section_id
                    $planSec->featurDetails = LandingPlanToFeature::where(
                        ['feature_id' => $data->feature_id, 'plan_id' => $planSec->plan_id]
                    )->get();
                    return $planSec;
                });


                $data->featureGroup = $data->feature_title;


                // getting sub feature
                $sub_feature = LandinPlanFeature::with('plan_to_feature')->where('parent_id', $data->feature_id)->orderBy('sort_order', 'ASC')->get();
                // if (!empty($sub_feature)) {
                //         $sub_feature = $sub_feature->map(function ($sub) {

                //             // $sub->planIndu = LandingPlan::all()->map(function ($planSec) use ($sub) {
                //             //     // filter record template_id, section_id
                //             //     $planSec->featurDetails = LandingPlanToFeature::where(
                //             //         ['feature_id' => $sub->feature_id, 'plan_id' => $planSec->plan_id]
                //             //     )->get();
                //             //     return $planSec;
                //             // });


                //             return $sub;
                //         });

                // }
                $data->sub_feature = $sub_feature;



                return $data;
            });
        }


        if ($request->has('group_id')) {
            //getting category Name
            $grpName = LandingPlanGroup::where('group_id', $request->group_id)->first();
            $cName = !empty($grpName) ? $grpName->group_name : '';
        }


        $res = [
            'feature_list' => $data_list,
            'plan_data' => $plan_data,
            'group_name' => $cName,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $api_token = $request->api_token;


        $api_token = $request->api_token;

        $industry_list = Industry::all();



        $res = [


            'industry_list' => $industry_list,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $api_token = $request->api_token;
        $industry_id = $request->industry_id;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        //  $plan_data=SubscriptionPlan::all();
        $feature_data = LandinPlanFeature::all();
        $plan_data =  LandingPlan::all();

        $plantofeature = '';
        foreach ($plan_data as $planKey => $planVal) {
            foreach ($feature_data as $featKey => $featVal) {
                $plan_id = $planVal->plan_id;
                $feature_id = $featVal->feature_id;
                $feature_status = '';

                if (isset($request->{"status_" . $plan_id . "_" . $feature_id})) {
                    $feature_status = $request->{"status_" . $plan_id . "_" . $feature_id};
                    $feature_limit = $request->{"feature_limit_" . $plan_id . "_" . $feature_id};


                    $plantofeature = LandingPlanToFeature::updateOrCreate(
                        ['plan_id' => $plan_id, 'feature_id' => $feature_id],
                        [
                            'status' => $feature_status,
                            'feature_limit' => $feature_limit
                        ]
                    );
                }
            }
        }

        if ($plantofeature) return ApiHelper::JSON_RESPONSE(true, $plantofeature, 'SUCCESS_SERVICE_PLAN_TO_FEATURE_ADD');
        else return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_SERVICE_PLAN_TO_FEATURE_ADD');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }


    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        $role_list = LandinPlanFeature::with('planTofeature')->find($request->updateId);
        return ApiHelper::JSON_RESPONSE(true, $role_list, '');
    }
}
