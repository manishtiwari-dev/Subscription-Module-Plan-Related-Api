<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Auth;
use Helper;
use ApiHelper;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $permissionList = Permission::all();
        return ApiHelper::JSON_RESPONSE(true,$permissionList,'');   
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
        
        $fail = 'permission already exist [ ';
        $success = 'permission created list [ ';
        $permission_list = explode(',',$request->permission_name);
        foreach ($permission_list as $key => $permission_name) {
            $status = Permission::where('name',$permission_name)->first();
            if($status !== null)
                $fail .= $permission_name.','; 
            else{
                $role = Permission::create(['name' => $permission_name ]);
                if($role)
                    $success .= $permission_name.',';
            }
        }
        return ApiHelper::JSON_RESPONSE(true,$permissionList,$success.' ] And '.$fail.' ]');         
        
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $status = Permission::where('name',$request->permission_name)->first();

        if($status !== null){
            return redirect()->back()->with('warning',"PERMISSION_ALREADY_UPDATED");
        }else{
            $res = Permission::find($id)->update([ 'name' => $request->permission_name ]);
            if($res)
                return redirect()->back()->with('success',"SUCCESS_PERMISSION_UPDATE");
            else
                return redirect()->back()->with('warning',"ERROR_PERMISSION_UPDATE");

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = Permission::destroy($id);
        
        if ($status) {
            return back()->with('success', 'SUCCESS_PERMISSION_DELETE');
        }else{
            return back()->with('warning', 'ERROR_PERMISSION_DELETE');
        }
    }
}
