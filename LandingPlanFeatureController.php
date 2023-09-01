<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Modules\Department\Models\Role;
use Modules\Department\Models\RoleToPermission;
use Illuminate\Support\Str;
use DB;
use Modules\Department\Models\Permission;
use App\Models\LandingPlanGroup;
use App\Models\LandinPlanFeature;
use Modules\Ecommerce\Models\Feature;




class LandingPlanFeatureController extends Controller
{
    public $page = 'service_plan';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';



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
        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? $request->perPage : 10;
        // $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $data_query = LandinPlanFeature::where('group_id', $request->group_id);
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $data_query = $data_query->orderBy('sort_order', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;     // apply page logic
        $data_count = $data_query->count(); // get total count
        $data_list = $data_query->get();


        if ($request->has('group_id')) {
            //getting category Name
            $grpName = LandingPlanGroup::where('group_id', $request->group_id)->first();
            $cName = !empty($grpName) ? $grpName->group_name : '';
        }




        $res = [
            'data' => $data_list,
            'current_page' => $current_page,
            'total_records' => $data_count,
            'total_page' => ceil((int)$data_count / (int)$perPage),
            'per_page' => $perPage,
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
        //  $grp_data = ServicePlanGroup::all();
        $grp_data = LandingPlanGroup::select('group_name as label', 'group_id as value')->get();
        $parent_feature = LandinPlanFeature::where('parent_id', 0)->get();

        $data = [
            'grp_data' => $grp_data,
            'parent_feature' => $parent_feature
        ];

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data, '');
        else
            return ApiHelper::JSON_RESPONSE(false, [], '');
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
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $group_ary = explode(',', $request->group_id);

        if (!empty($group_ary))
            $planfeature = []; {
            foreach ($group_ary as $key => $value) {
                $planfeature = LandinPlanFeature::create([
                    'feature_title' => $request->feature_title,
                    'feature_details' => $request->feature_details,
                    'group_id' => $value,
                    'parent_id' => $request->parent_id
                ]);
            }
        }


        if ($planfeature) {
            return ApiHelper::JSON_RESPONSE(true, $planfeature, 'SUCCESS_SERVICE_PLAN_FEATURE_ADDED');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_SERVICE_PLAN_FEATURE_ADDED');
        }
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        $feature_list = LandinPlanFeature::find($request->feature_id);
        $parent_feature = LandinPlanFeature::where('parent_id', 0)->get();

        // if (!empty($feature_list)) {
        //     $planGroup = $feature_list->planGroup;
        //     return ApiHelper::JSON_RESPONSE(true, $planGroup, '');
        //     $selected_group = [];
        //     if (!empty($planGroup)) {
        //         foreach ($planGroup as $key => $cat) {
        //             $label = ($cat !== null) ? $cat->group_name : '';
        //             array_push($selected_group, [
        //                 "label" => $label,
        //                 "value" => $cat->group_id,
        //             ]);
        //         }
        //     }
        //     $feature_list->selected_group = $selected_group;
        // }

        $grp_data = LandingPlanGroup::all();


        $data = [
            'grp_data' => $grp_data,
            'data_list' => $feature_list,
            'parent_feature' => $parent_feature
        ];

        return ApiHelper::JSON_RESPONSE(true, $data, '');
    }


    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $feature_id = $request->feature_id;
        $sort_order = $request->sort_order;
        $featureData =  LandinPlanFeature::find($feature_id);
        $featureData->sort_order = $sort_order;
        $featureData->save();

        return ApiHelper::JSON_RESPONSE(true, $featureData, 'SUCCESS_SORT_ORDER_UPDATE');
    }



    public function update(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $planfeature = LandinPlanFeature::where('feature_id', $request->feature_id)->update([
            'feature_title' => $request->feature_title,
            'feature_details' => $request->feature_details,
            'sort_order' => $request->sort_order,
            'group_id' => $request->group_id,
            'parent_id' => $request->parent_id
        ]);
        if ($planfeature)   return ApiHelper::JSON_RESPONSE(true, $planfeature, 'SERVICE_PLAN_FEATURE_UPDATED');
        else return ApiHelper::JSON_RESPONSE(false, [], 'UNABLE_UPDATE_SERVICE_PLAN_FEATURE');
    }



    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        $feature_id = $request->feature_id;
        $infoData = LandinPlanFeature::find($feature_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');
    }



    public function changeMenuCollapse(Request $request)
    {
        $api_token = $request->api_token;
        $feature_id = $request->feature_id;
        $infoData = LandinPlanFeature::find($feature_id);
        $infoData->menu_collapse = ($infoData->menu_collapse == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_MENU_COLLAPSE_UPDATE');
    }
}
