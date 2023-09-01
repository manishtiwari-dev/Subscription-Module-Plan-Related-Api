<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\NewsLetter;
use Illuminate\Http\Request;
use ApiHelper;



class NewsLetterController extends Controller
{

    public $page = 'newsletter';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function index(Request $request){

       //Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');


        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBy = $request->sortBy;
        $orderBy = $request->orderBy;
        
        /*Fetching subscriber data*/ 
        $newsLetter_query = NewsLetter::query();
        /*Checking if search data is not empty*/
        if(!empty($search))
            $newsLetter_query = $newsLetter_query
                ->where("company_name","LIKE", "%{$search}%")
                ->orWhere("customer_email","LIKE", "%{$search}%")
                ->orWhere("customer_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBy) && !empty($orderBy))
            $newsLetter_query = $newsLetter_query->orderBy($sortBy,$orderBy);
        else
            $newsLetter_query = $newsLetter_query->orderBy('newsletter_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $newsLetter_count = $newsLetter_query->count();

        $newsLetter_list = $newsLetter_query->skip($skip)->take($perPage)->get();
        
         /*Binding data into a variable*/
        $res = [
            'data'=>$newsLetter_list,
            'current_page'=>$current_page,
            'total_records'=>$newsLetter_count,
            'total_page'=>ceil((int)$newsLetter_count/(int)$perPage),
            'per_page'=>$perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');


    }

    
   

    public function store(Request $request)
    {
        $api_token = $request->api_token;


        $validator = Validator::make($request->all(),[
            'company_name' => 'required',
            'customer_name'=>'required',
            'customer_email'=>'required',
           
        ],[
            
            'company_name.required'=>'COMPANY_NAME_REQUIRED',
            'customer_name.required'=>'CUSTOMER_NAME_REQUIRED',
            'customer_email.required'=>'CUSTOMER_EMAIL_REQUIRED',
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $news_letter=$request->except(['api_token']);

    

        $data = NewsLetter::create($news_letter);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_NEWS_LETTER_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_NEWS_LETTER_ADD');

    }

    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = NewsLetter::where('newsletter_id',$request->newsletter_id)->first();
        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }

    public function update(Request $request)
    { 

        $api_token = $request->api_token;

        $validator = Validator::make($request->all(),[
             'company_name' => 'required',
            'customer_name'=>'required',
            'customer_email'=>'required',
           
        ],[
              'company_name.required'=>'COMPANY_NAME_REQUIRED',
            'customer_name.required'=>'CUSTOMER_NAME_REQUIRED',
            'customer_email.required'=>'CUSTOMER_EMAIL_REQUIRED',
             
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $news_letter_update=$request->except(['api_token','newsletter_id']);
        $data = NewsLetter::where('newsletter_id', $request->newsletter_id)->update($news_letter_update);
        
       
        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_NEWS_LETTER_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_NEWS_LETTER_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $status = NewsLetter::where('newsletter_id',$request->newsletter_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_NEWS_LETTER_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_NEWS_LETTER_DELETE');
        }
    }




     public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $newsletter_id = $request->newsletter_id;
        $sub_data = NewsLetter::find($newsletter_id);
        $sub_data->status = $request->status;
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }
    

     public function import_file(Request $request)
    {

        $dataList = ApiHelper::read_csv_data($request->fileInfo, "csv/lead");

        foreach ($dataList as $key => $value) {

            
           // $newsletter_id = $value[0];

            $company_name = $value[0];
            $customer_name = $value[1];
            $customer_email=$value[2];

            $newsletter_data =  NewsLetter::where('company_name', $company_name)->first();

            if (!empty($newsletter_data))
             {
                $data = [
                    'company_name' => $company_name,
                    'customer_name'=>$customer_name,
                    'customer_email'=>$customer_email,
                    
                ];
                $newsletter = NewsLetter::create($data);
               
            }

            
        }

        return ApiHelper::JSON_RESPONSE(true, $dataList, 'SUCCESS_NEWSLETTER_IMPORTED');
    }
}
