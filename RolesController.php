<?php

namespace Modules\Department\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Modules\Department\Models\Role;
use Modules\Department\Models\RoleToPermission;
use Illuminate\Support\Str;
use DB;
use App\Models\Module;
use App\Models\ModuleSection;
use Modules\Department\Models\Permission;


class RolesController extends Controller
{
    public $page = 'role';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    /*  sectionList */
    public function section_list(Request $request){
        $api_token = $request->api_token;
        $language = $request->language;
        $module_listItem = [];

     //   $moduleList=[];
        
        $sectionList = [];

        $usType = ($request->userType == 'administrator') ? 0 : 2;

        $utype = '1,'.$usType;

     


       $industry_id = ApiHelper::get_industry_id_by_api_token($api_token);

        $moduleList = Module::with(['section_list'=>function($query){$query->orderBy('sort_order', 'ASC')->where('status',1);}])->whereRelation('module_list', 'industry_id',  $industry_id)->whereRaw('access_priviledge IN('.$utype.')')->where('status','1')->orderBy('sort_order','ASC')->get();
        // if(!empty($module)){
        //     foreach ($module as $key => $mod) {

        //         array_push($moduleList, $mod);

        //         if(!empty($mod->section_list)){
        //             foreach ($mod->section_list as $key => $value) {
        //                 array_push($sectionList, $value);    
        //             }
        //         }
        //     }
        // }

           
        $moduleList = $moduleList->map(function($data) use ($language)  {

            $cate = $data->section_list()->where('status',1)->orWhere('sort_order','ASC')->first();

            $data->section_name = ($cate == null) ? '' : $cate->section_name;
            

           
      
            return $data;
        });

       


       // $sectionList = ModuleSection::where('status','1')->orWhere('parent_section_id','0')->orderBy('sort_order','ASC')->get();
     //   $module_listItem['section_list'] =  $sectionList;
        $module_listItem['module_list'] =  $moduleList;
        $module_listItem['permission_list'] = Permission::all();
        return ApiHelper::JSON_RESPONSE(true,$module_listItem,'');
    }

    public function roles_all(Request $request){

        $api_token = $request->api_token;

        $roles_list = Role::all();

        return ApiHelper::JSON_RESPONSE(true,$roles_list,'');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        // get all request val
        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;


        $data_query = Role::query();

        // search
        if(!empty($search))
            $data_query = $data_query->where("roles_name","LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('roles_id','ASC');
        }

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;     // apply page logic

        $data_count = $data_query->count(); // get total count

        $data_list = $data_query->skip($skip)->take($perPage)->get();  
        
        // get pagination data
        $data_list = $data_list->map(function($data){

            $sectionlist = ApiHelper::byRoleIdSectionsPermissionList($data->roles_id);
            
            // $secListItem = '';
            // foreach ($sectionlist as $key => $sec) {
            //     $perName = '(';
            //     foreach ($sec->permissions as $per) {
            //         $perName .= $per->permissions_name.', ';
            //     }
            //     $perName .= ')';
            //     $secListItem .= $sec->section_name.' '.$perName.', ';
            // }
            $data->permissionList = $sectionlist;
            $data->userCount = $data->users()->count();
            return $data;
        });

        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$data_count,
            'total_page'=>ceil((int)$data_count/(int)$perPage),
            'per_page'=>$perPage
        ];
        return ApiHelper::JSON_RESPONSE(true,$res ,'');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $user_id = ApiHelper::get_adminid_from_token($api_token);

        $role_name = $request->role_name;
        $status = Role::where('roles_key',Str::slug($role_name))->first();

        if($status !== null)    return ApiHelper::JSON_RESPONSE(false,[],'ROLE_EXISTS');
        
        $role = Role::create([
            'roles_name' => $role_name,
            'roles_key' => Str::slug($role_name)
        ]);

        $permissionList = [];
        $sectionList = [];

        $findDif = '';
    

        if(sizeof($request->permission) > 0 ){
            foreach ($request->permission as $sectionId => $permission) {

                if(!empty($permission)){

                    foreach ($permission as $pId => $pTyeId) {
                        
                        if(!empty($pTyeId)){
                            array_push($permissionList, [
                                'section_id'=>(int) $sectionId,
                                'roles_id'=>$role->roles_id,
                                'permissions_ids'=>(int)$pId,
                                'permission_types_id'=>(int)$pTyeId,
                            ]);
                        }

                    }
                }
                
            }
        }
        
        // return ApiHelper::JSON_RESPONSE(true,$permissionList,'ROLE_CREATED');


        $role->sections()->attach($permissionList);

        // $role->role_permissions()->attach($permissionList);
        
        if($role)   return ApiHelper::JSON_RESPONSE(true,$sectionList,'ROLE_CREATED');
        else return ApiHelper::JSON_RESPONSE(false,[],'UNABLE_CREATE_ROLE');

        

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
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        

        $role_list = Role::with('permissionsTypeList')->find($request->updateId);
        
        // $role_list->sections = ApiHelper::byRoleIdSectionsPermissionList($role_list->roles_id);
        return ApiHelper::JSON_RESPONSE(true,$role_list,'');

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */



    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $roles_id =$request->roles_id;
        $sort_order=$request->sort_order;
        
        $infoData =  Role::find($roles_id);
        if(empty($infoData)){
            $infoData = new Role();
            $infoData->roles_id=$roles_id;
            $infoData->sort_order =$sort_order;
            $infoData->status =1;

            $infoData->save();
        
        }else{
            $infoData->sort_order = $sort_order;
            $infoData->save();
        }
       
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_SORT_ORDER_UPDATE');
    }    







    public function update(Request $request)
    {
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $user_id = ApiHelper::get_adminid_from_token($api_token);

        $role_name = $request->role_name;

        $role = Role::where('roles_name',$role_name)->first();
        
        // if new role coming than will update else not
        if($role !== null){
            Role::find($request->updatedId)->update(['roles_name' => $role_name, 'roles_key' => Str::slug($role_name) ]);
            $role = Role::find($request->updatedId);
        }


        // role permission updated
        $permissionList = [];
        if(sizeof($request->permission) > 0 ){
            
            $role->sections()->detach();    // detach relationship data 

            foreach ($request->permission as $sectionId => $permission) {

                if(!empty($permission)){

                    foreach ($permission as $pId => $pTyeId) {
                        
                        if(!empty($pTyeId)){
                            array_push($permissionList, [
                                'section_id'=>(int) $sectionId,
                                'roles_id'=>$role->roles_id,
                                'permissions_ids'=>(int)$pId,
                                'permission_types_id'=>(int)$pTyeId,
                            ]);
                        }

                    }
                }
                
            }
            $role->sections()->attach($permissionList);         // attach permission list
        }

        if($role)   return ApiHelper::JSON_RESPONSE(true,$role,'ROLE_UPDATED');
        else return ApiHelper::JSON_RESPONSE(false,[],'UNABLE_UPDATE_ROLE');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $id = $request->deleteId;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pagestatus)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $role = Role::find($id);
        $role->sections()->detach();
        $status = Role::destroy($id);
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'ROLE_DELETED');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'NOT_DELETED_ROLE');
        }
    }


   
}
