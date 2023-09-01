<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Industry;
use App\Models\IndustryModule;
use App\Models\Module;
use Modules\WebsiteSetting\Models\WebFunctions;

use App\Models\WebFunctionsIndustry;

use Illuminate\Http\Request;
use App\Models\Super\IndustryCategory;

use ApiHelper;

class IndustryController extends Controller
{

    public $page = 'industry';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';



    public function index_all(Request $request)
    {

        $api_token = $request->api_token;

        $industry_list = Industry::select('industry_name as label', 'industry_id as value')->get();
        $industry_category = IndustryCategory::all();

        $res = [
            'industry_category' => $industry_category,

            'industry_list' => $industry_list,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
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
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $industry_section_query = Industry::query();
        if (!empty($search))
            $industry_section_query = $industry_section_query->where("industry_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $industry_section_query = $industry_section_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $industry_section_query = $industry_section_query->orderBy('sort_order', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;
        $industry_count = $industry_section_query->count();
        $industry_list = $industry_section_query->skip($skip)->take($perPage)->get();
        $industry_list = $industry_section_query->get();

        $industry_list = $industry_list->map(function ($data) {
            $permissionListBox = [];
            if (!empty($data->modules)) {
                foreach ($data->modules as $key => $per) {
                    $permissionListBox[$key] = $per->module_name;
                }
            }
            $data->module_list = implode(",\n", $permissionListBox);
            return $data;
        });

        $industry_list = $industry_list->map(function ($data) {
            $permissionList = [];
            if (!empty($data->web_functions)) {
                foreach ($data->web_functions as $key => $per) {
                    $permissionList[$key] = $per->function_name;
                }
            }
            $data->function_list = implode(",\n", $permissionList);
            return $data;
        });

        $res = [
            'data' => $industry_list,
            'current_page' => $current_page,
            'total_records' => $industry_count,
            'total_page' => ceil((int)$industry_count / (int)$perPage),
            'per_page' => $perPage,
        ];
        /*returning data to client side*/
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function store(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $industry_name = $request->industry_name;
        $module_icon = $request->module_icon;
        $access_priviledge = $request->access_priviledge;
        $sort_order = $request->sort_order;
        $module_id = explode(',', $request->module_id);
        $function_id = explode(',', $request->function_id);

        $validator = Validator::make($request->all(), [
            'industry_name' => 'required',
            'sort_order' => 'required',
        ], [
            'industry_name.required' => 'INDUSTRY_NAME_REQUIRED',
            'sort_order.required' => 'SORT_ORDER_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        $data = Industry::create([
            'industry_name' => $industry_name,
            'sort_order' => $sort_order,
            'status' => 1,
        ]);
        $industry_data = Industry::where('industry_id', $data['industry_id'])->first();
        //$module = [];
        foreach ($module_id as $key => $value) {
            $module_data = IndustryModule::create([
                'industry_id' => $industry_data->industry_id,
                'module_id' => $value,
                'sort_order' => $sort_order,
            ]);
        }

        foreach ($function_id as $key => $value) {

            $industry_data = WebFunctionsIndustry::create([

                'function_id' => $value,
                'industry_id' => $industry_data->industry_id,


            ]);
        }


        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_INDUSTRY_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_INDUSTRY_ADD');
    }

    public function edit(Request $request)
    {

        $industry_id = $request->industry_id;
        $data = Industry::with('web_functions', 'modules')->where('industry_id', $industry_id)->first();
        //$data->modules = $data->modules;
        return ApiHelper::JSON_RESPONSE(true, $data, '');
    }

    public function update(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $industry_id = $request->industry_id;
        $industry_name = $request->industry_name;
        $sort_order = $request->sort_order;
        $module_id = explode(',', $request->module_id);
        $function_id = explode(',', $request->function_id);

        $validator = Validator::make($request->all(), [
            'industry_name' => 'required',
            'sort_order' => 'required',
        ], [
            'industry_name.required' => 'INDUSTRY_NAME_REQUIRED',
            'sort_order.required' => 'SORT_ORDER_REQUIRED',
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());


        $data = Industry::where('industry_id', $industry_id)->update([
            'industry_name' => $industry_name,
            'sort_order' => $sort_order,
            'status' => 1,
        ]);

        // module update 
        if (!empty($module_id)) {
            IndustryModule::where('industry_id', $industry_id)->delete();
            foreach ($module_id as $key => $value)
                IndustryModule::create(['industry_id' => $industry_id, 'module_id' => $value]);
        }
        //function update
        if (!empty($function_id)) {
            WebFunctionsIndustry::where('industry_id', $industry_id)->delete();
            foreach ($function_id as $key => $value)
                WebFunctionsIndustry::create(['industry_id' => $industry_id, 'function_id' => $value]);
        }

        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_INDUSTRY_UPDATE');
    }

    public function create(Request $request)
    {
        $api_token = $request->api_token;
        $industry_id = $request->industry_id;
        $module_list = Module::select('module_name as label', 'module_id as value')->where('access_priviledge', 1)->orWhere('access_priviledge', 2)->get();

        $webfunction = WebFunctions::select('function_name as label', 'function_id as value')->get();

        $data_list = Industry::with('web_functions')->where('industry_id', $industry_id)->first();


        $IndustryTypeList = Industry::select('industry_name as label', 'industry_id as value')->get();

        $res = [
            'module_list' => $module_list,
            'webfunction' => $webfunction,
            'IndustryTypeList' => $IndustryTypeList,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function changeStatus(Request $request)
    {
        $industry_id = $request->industry_id;
        $sub_data = Industry::with('modules')->where('industry_id', $industry_id)->first();
        $sub_data->status = ($sub_data->status == 0) ? 1 : 0;
        $sub_data->save();

        return ApiHelper::JSON_RESPONSE(true, $sub_data, 'SUCCESS_STATUS_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $DETAIL = Industry::with('modules')->where('industry_id', $request->industry_id)->first();

        if (!empty($DETAIL)) $DETAIL->modules()->detach();   // relation data delete
        $DETAIL = Industry::where('industry_id', $request->industry_id)->delete();

        if ($DETAIL) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_INDUSTRY_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_INDUSTRY_DELETE');
        }
    }
    public function fun_select(Request $request)
    {

        $webfunction = WebFunctions::select('function_name as label', 'function_id as value')->get();
        $res = [

            'webfunction' => $webfunction,

        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }
}
