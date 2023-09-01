<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use ApiHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;
use App\Models\CategoryDetails;
use App\Models\Language;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'category.view')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;
        
        $data_query = Category::where('created_by',ApiHelper::get_adminid_from_token($api_token));
        
        if(!empty($search))
            // $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('id','ASC');
        }
                    
        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;
        
        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_list->map(function($data){
            $data->category_name = $data->categorydetails[0]->category_name;
            $data->description = $data->categorydetails[0]->description;
            $data->status = ($data->categories_status == 1) ? "active":"deactive"; 
            return $data;
        });
        
        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$user_count,
            'total_page'=>ceil((int)$user_count/(int)$perPage),
            'per_page'=>$perPage
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
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
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,'category.create')){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        // validation check 
        $rules = [
            'category_name.*' => 'required|string',
            'sort_order' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }

        // store category 
        $saveData = $request->only('sort_order');
        $saveData['categories_slug'] = Str::slug($request->category_name);
        $saveData['created_by'] = ApiHelper::get_adminid_from_token($api_token);

        if($request->has("icon") && $request->file('icon'))
            $saveData['categories_icon'] = $request->file('icon')->store("category");
        
        if($request->has("image") && $request->file('image'))
            $saveData['categories_image'] = $request->file('image')->store("category");

        $cat = Category::create($saveData);

        // store cat details
        foreach (Language::all() as $key => $value) {
            $cat_name = $value->name."_category_name";
            $desc = $value->name."_description";
            CategoryDetails::create([
                'categories_id'=>$cat->id,
                'category_name'=>$request->$cat_name,
                'description'=>$request->$desc,
                'languages_id'=>$value->id,
            ]);   
        }

        if($cat){
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_CATEGORY_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_CATEGORY_ADD');
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

      public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $categories_id = $request->categories_id;
        $infodata=Category::find($categories_id);
        $infodata->is_featured = ($infodata->is_featured == 0 ) ? 1 : 0;         
        $infodata->save();
      
        return ApiHelper::JSON_RESPONSE(true,$infodata,'SUCCESS_STATUS_UPDATE');
    }
}
