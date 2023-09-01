<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Module;
use App\Models\ModuleSection;
use Illuminate\Http\Request;
use ApiHelper;
use Modules\Department\Models\Permission;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public $page = 'module';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';



    public function index_all(Request $request)
    {
        $api_token = $request->api_token;
        $module_list = Module::orderBy('sort_order', 'ASC')->get();

        foreach ($module_list as $mkey => $module) {
            $module_section = ModuleSection::where('module_id', $module->module_id)->where('parent_section_id', '0')->get();
            foreach ($module_section as $skey => $section) {
                $module_section[$skey]['submenu'] = ModuleSection::where('parent_section_id', $section['section_id'])->get();
            }
            $module_list[$mkey]['menu'] = $module_section;
        }
        return ApiHelper::JSON_RESPONSE(true, $module_list, '');
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
        /*Fetching module data*/
        $module_list = Module::orderBy('sort_order', 'ASC')->get();

        $module_list = $module_list->map(function ($module_status) {
            $section_listing = $module_status->section_list()->where('parent_section_id', 0)->orderBy('sort_order', 'ASC')->get();

            $section_listing_data = $section_listing->map(function ($section) {
                $section->sub_section = ModuleSection::where('parent_section_id', $section->section_id)->orderBy('sort_order', 'ASC')->get();
                return $section;
            });

            $module_status->section_list = $section_listing_data;

            return $module_status;
        });

        $res = [
            'data' => $module_list,

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
        $module_name = $request->module_name;
        $module_icon = $request->module_icon;
        $access_priviledge = $request->access_priviledge;
        $sort_order = $request->sort_order;

        $validator = Validator::make($request->all(), [
            'module_name' => 'required',
            'access_priviledge' => 'required',
            'sort_order' => 'required',
        ], [
            'module_name.required' => 'MODULE_NAME_REQUIRED',
            'access_priviledge.required' => 'ACCESS_PRIVILEGE_REQUIRED',
            'sort_order.required' => 'SORT_ORDER_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        $data = Module::create([
            'module_name' => $module_name,
            'module_icon' => $module_icon,
            'access_priviledge' => $access_priviledge,
            'sort_order' => $sort_order,
            'module_slug' => Str::slug($module_name, '_'),
        ]);

        $res = $this->getModuleListForSideMenu($api_token);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_MODULE_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false, $res, 'ERROR_MODULE_ADD');
    }

    public function store_module_section(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $module_id = $request->module_id;
        $parent_section_id = $request->parent_section_id;
        $section_name = $request->section_name;
        $section_icon = $request->section_icon;
        $section_url = $request->section_url;
        $sort_order = $request->sort_order;

        $validator = Validator::make($request->all(), [
            'module_id' => 'required',
            'parent_section_id' => 'required',
            'section_name' => 'required',
            'sort_order' => 'required',
        ], [
            'module_id.required' => 'MODULE_ID_REQUIRED',
            'parent_section_id.required' => 'ACCESS_PRIVILEGE_REQUIRED',
            'section_name.required' => 'SECTION_NAME_REQUIRED',
            'sort_order.required' => 'SORT_ORDER_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        $data = ModuleSection::create([
            'module_id' => $module_id,
            'parent_section_id' => $parent_section_id,
            'section_name' => $section_name,
            'section_icon' => $section_icon,
            'section_url' => $section_url,
            'sort_order' => $sort_order,
            'section_slug' => Str::slug($section_name, '_'),
        ]);

        $res = $this->getModuleListForSideMenu($api_token);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_MODULE_SECTION_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false, $res, 'ERROR_MODULE_SECTION_ADD');
    }

    public function section_list(Request $request)
    {
        $api_token = $request->api_token;
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $module_list = [];
        $module_list['section_list'] = ModuleSection::where('status', 'completion_status', 1)->where('parent_section_id', '0')->orderBy('sort_order', 'ASC')->get();
        $module_list['permission_list'] = Permission::all();
        return ApiHelper::JSON_RESPONSE(true, $module_list, '');
    }


    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $module = Module::find($request->module_id);
        return ApiHelper::JSON_RESPONSE(true, $module, '');
    }
    public function update(Request $request)
    {

        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }


        $module_name = $request->module_name;


        $validator = Validator::make($request->all(), [
            'module_name' => 'required',
            'access_priviledge' => 'required',
            'sort_order' => 'required',
        ], [
            'module_name.required' => 'MODULE_NAME_REQUIRED',
            'access_priviledge.required' => 'ACCESS_PRIVILEGE_REQUIRED',
            'sort_order.required' => 'SORT_ORDER_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());

        $inst = $request->except(['api_token', 'module_id']);
        $inst['module_slug'] = Str::slug($module_name, '_');

        $data = Module::where('module_id', $request->module_id)->update($inst);

        $res = $this->getModuleListForSideMenu($api_token);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_MODULE_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false, $res, 'ERROR_MODULE_UPDATE');
    }
    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;

        if ($request->type == "module")
            $infoData = Module::find($request->update_id);
        else
            $infoData = ModuleSection::find($request->update_id);

        if ($request->has('quick_access'))
            $infoData->quick_access = ($infoData->quick_access == 0) ? 1 : 0;
        else if ($request->has('completion_status'))
            $infoData->completion_status = ($infoData->completion_status == 0) ? 1 : 0;
        else
            $infoData->status = ($infoData->status == 0) ? 1 : 0;

        $infoData->save();

        $res = $this->getModuleListForSideMenu($api_token);

        return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_STATUS_UPDATE');
    }
    public function section_edit(Request $request)
    {
        $api_token = $request->api_token;
        $section_list = ModuleSection::find($request->section_id);
        return ApiHelper::JSON_RESPONSE(true, $section_list, '');
    }
    public function section_update(Request $request)
    {

        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }


        $validator = Validator::make($request->all(), [
            'module_id' => 'required',
            'parent_section_id' => 'required',
            'section_name' => 'required',
            'sort_order' => 'required',
        ], [
            'module_id.required' => 'MODULE_ID_REQUIRED',
            'parent_section_id.required' => 'ACCESS_PRIVILEGE_REQUIRED',
            'section_name.required' => 'SECTION_NAME_REQUIRED',
            'sort_order.required' => 'SORT_ORDER_REQUIRED',
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());

        $inst = $request->except(['api_token', 'SECTION_TYPE']);
        if ($request->has('SECTION_TYPE'))
            $inst['parent_section_id'] = $request->SECTION_TYPE == 'parent' ? 0 : $request->parent_section_id;

        $inst['section_slug'] = Str::slug($request->section_name, '_');
        // $inst['module_id'] = (int)$inst['module_id'];


        $data = ModuleSection::where('section_id', $request->section_id)->update($inst);

        $res = $this->getModuleListForSideMenu($api_token);

        if ($data)
            return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_MODULE_SECTION_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false, $res, 'ERROR_MODULE_SECTION_UPDATE');
    }
    public function update_sort_order(Request $request)
    {

        $api_token = $request->api_token;

        if ($request->type == "module")
            $infoData = Module::find($request->update_id);
        else
            $infoData = ModuleSection::find($request->update_id);

        $infoData->sort_order = (int)$request->sort_order;
        $res = $infoData->save();

        $res = $this->getModuleListForSideMenu($api_token);

        return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_SORT_ORDER_UPDATE');
    }


    public function update_section_source(Request $request)
    {

        $api_token = $request->api_token;


        $infoData = ModuleSection::find($request->update_id);

        $infoData->section_source = (int)$request->section_source;
        $res = $infoData->save();

        $res = $this->getModuleListForSideMenu($api_token);

        return ApiHelper::JSON_RESPONSE(true, $res, 'SUCCESS_SECTION_SOURCE_UPDATE');
    }




    // public function getModuleListForSideMenu()
    // {
    //     $returnItem = [];
    //     $quickInc = 0;
    //     $selectionItem = [];
    //     $module_list = Module::where('status', 1)->where('access_priviledge', 0)->orderBy('sort_order', 'ASC')->get();
    //     foreach ($module_list as $mkey => $module) {
    //         $module_section = ModuleSection::where('module_id', $module->module_id)->orWhere('status', 1)->orWhere('parent_section_id', '0')->orderBy('sort_order', 'ASC')->get();
    //         foreach ($module_section as $skey => $section) {
    //             $module_section[$skey]['submenu'] = ModuleSection::where('parent_section_id', $section['section_id'])->orWhere('status', 1)->orderBy('sort_order', 'ASC')->get();
    //         }
    //         $module_list[$mkey]['menu'] = $module_section;
    //         // module wise quicklist view
    //         $quick_section = ModuleSection::where('module_id', $module->module_id)->orWhere('status', 'completion_status', 1)->orderBy('sort_order', 'ASC')->limit(4)->get();


    //         if (sizeof($quick_section) > 0) {
    //             foreach ($quick_section as $key => $value) {
    //                 $selectionItem[$quickInc] = $value;
    //                 $quickInc++;
    //             }
    //         }
    //     }

    //     $returnItem['module_list'] = $module_list;
    //     $returnItem['quick_list'] = $selectionItem;

    //     return $returnItem;
    // }




    public function getModuleListForSideMenu($api_token)
    {
        // dd( $industry_id) ;
        //  $api_token = $request->api_token;
        $returnItem = [];
        $quickInc = 0;
        $selectionSection = [];
        $selectionItem = [];

        // $module_list = Module::where('status',1)->where('access_priviledge',0)->orderBy('sort_order','ASC')->get();
        $module_list = Module::where('status', 1)->where('access_priviledge', 0)->orWhere('access_priviledge', 1)->orderBy('sort_order', 'ASC')->get();
        foreach ($module_list as $mkey => $module) {
            $modulequery = ModuleSection::query();

            $module_section = $modulequery->where('module_id', $module->module_id)->where('status', 1)->where('parent_section_id', '0')->orderBy('sort_order', 'ASC')->get();


            foreach ($module_section as $skey => $section) {
                $module_section[$skey]['submenu'] = ModuleSection::where('parent_section_id', $section['section_id'])->where('status', 1)->orderBy('sort_order', 'ASC')->get();
            }
            $module_list[$mkey]['menu'] = $module_section;
            // module wise quicklist view


            $selected_section = ModuleSection::where('module_id', $module->module_id)->where('status', 1)->get();

            $quick_section = ModuleSection::where('module_id', $module->module_id)->orWhere('status', 'completion_status', 1)->orderBy('sort_order', 'ASC')->limit(4)->get();

            $res = ApiHelper::get_parentemail_from_token($api_token);



            if (!empty($res->quick_access)) {
                $myArray = explode(',', $res->quick_access);

                if ($myArray) {
                    foreach ($myArray as $key => $value) {
                        if ($value != '') {
                            $quick_section = ModuleSection::where('section_id', $value)->where('status', 1)->get();
                        }

                        if (sizeof($quick_section) > 0) {
                            foreach ($quick_section as $key => $value) {
                                $selectionItem[$quickInc] = $value;
                                $quickInc++;
                            }
                        }
                    }
                }

                $returnItem['quick_list'] = $selectionItem;
            } else {
                $returnItem['quick_list'] = [];
            }
        }

        $returnItem['module_list'] = $module_list;
        //   $returnItem['quick_list'] = $selectionItem;
        $returnItem['select_section_list'] = $selected_section;


        return $returnItem;
    }
}
