<?php


namespace App\Http\Controllers\Api;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use ApiHelper;


class SubscriberController extends Controller
{
    public $subscriberView = 'subscriber-view';
    public $subscriberManage = 'subscriber-manage';
    public $subscriberDelete = 'subscriber-delete';



    //This Function is used to show the list of subscribers
    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        // if(!ApiHelper::is_page_access($api_token,$this->subscriberView)){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }

        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBy = $request->sortBy;
        $orderBy = $request->orderBy;
        
        /*Fetching subscriber data*/ 
        $subscriber_query = Subscriber::select('subscriber_id','subs_name','subs_email','subs_company_name','subs_contact_no');
        /*Checking if search data is not empty*/
        if(!empty($search))
            $subscriber_query = $subscriber_query
                ->where("subs_name","LIKE", "%{$search}%")
                ->where("subs_email","LIKE", "%{$search}%")
                ->where("subs_company_name", "LIKE", "%{$search}%")
                ->orWhere("subs_contact_no", "LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBy) && !empty($orderBy))
            $subscriber_query = $subscriber_query->orderBy($sortBy,$orderBy);
        else
            $subscriber_query = $subscriber_query->orderBy('subscriber_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $subscriber_count = $subscriber_query->count();

        $subscriber_list = $subscriber_query->skip($skip)->take($perPage)->get();
        
         /*Binding data into a variable*/
        $res = [
            'data'=>$subscriber_list,
            'current_page'=>$current_page,
            'total_records'=>$subscriber_count,
            'total_page'=>ceil((int)$subscriber_count/(int)$perPage),
            'per_page'=>$perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    
    //This Function is used to get the details of subscriber data
    public function details(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        // if(!ApiHelper::is_page_access($api_token,$this->subscriberView)){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }


        $subscriber_id = $request->subscriber_id;
        $data = Subscriber::where('subscriber_id', $subscriber_id)->first();
        if(!empty($data)){
            /* Fetching data of business*/
            $data->business = $data->business;
            $subscriber_history = [];
            /* Fetching data of subscription*/
            $data->subscription = $data->subscription;
            foreach ($data->subscription as $key => $value) {
                $value->subscriber_history = $value->subscriber_history;
                $value->subscription_plan = $value->subscription_plan;
                $subscriber_history[$key] = $value;  
            }
            /* Fetching data of subscriber history*/
            $data->subscription = $subscriber_history;
        }  
        return ApiHelper::JSON_RESPONSE(true,$data,'');
    }

    //This Function is used to show the particular subscriber data
    public function edit(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token,$this->subscriberManage)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }


        $subscriber_id = $request->subscriber_id;
        $data = Subscriber::where('subscriber_id', $subscriber_id)->first();
        return ApiHelper::JSON_RESPONSE(true,$data,'');       
    }
    
    //This Function is used to update the particular subscriber data
    public function update(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        // if(!ApiHelper::is_page_access($api_token,$this->subscriberManage)){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }

        /*fetching data from api*/
        $subscriber_id = $request->subscriber_id;
        $subs_name = $request->subs_name;
        $subs_email = $request->subs_email;
        $business_id = $request->business_id;
        $subs_company_name = $request->subs_company_name;
        $subs_contact_no = $request->subs_contact_no;
        /*validating data*/
        $validator = Validator::make($request->all(),[
            'subs_name' => 'required',
            'subs_email' => 'required',
            'subs_company_name' => 'required',
            'subs_contact_no' => 'required',
        ],[
            'subs_name.required'=>'SUBSCRIBER_NAME_REQUIRED',
            'subs_email.required'=>'SUBSCRIBER_EMAIL_REQUIRED',
            'subs_company_name.required'=>'SUBSCRIBER_COMPANY_NAME_REQUIRED',
            'subs_contact_no.required'=>'SUBSCRIBER_CONTACT_NO_REQUIRED',
        ]);
        /*if validation fails then it will show error message*/
        if ($validator->fails()){
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }
        /*updating subscriber data after validation*/
        $data = Subscriber::where('subscriber_id', $subscriber_id)->update(['subs_name'=>$subs_name, 'subs_email'=>$subs_email, 'business_id'=>$business_id, 'subs_company_name'=>$subs_company_name, 'subs_contact_no'=>$subs_contact_no]);
        return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_SUBSCRIBER_UPDATE');
    }
    
    //This Function is used to add the subscriber data
    public function add(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        // if(!ApiHelper::is_page_access($api_token,$this->subscriberManage)){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }

        $subs_name = $request->subs_name;
        $subs_email = $request->subs_email;
        $business_id = $request->business_id;
        $subs_company_name = $request->subs_company_name;
        $subs_contact_no = $request->subs_contact_no;
        $validator = Validator::make($request->all(),[
            'subs_name' => 'required',
            'subs_email' => 'required',
            'subs_company_name' => 'required',
            'subs_contact_no' => 'required',
        ],[
            'subs_name.required'=>'SUBSCRIBER_NAME_REQUIRED',
            'subs_email.required'=>'SUBSCRIBER_EMAIL_REQUIRED',
            'subs_company_name.required'=>'SUBSCRIBER_COMPANY_NAME_REQUIRED',
            'subs_contact_no.required'=>'SUBSCRIBER_CONTACT_NO_REQUIRED',
        ]);
        
        if ($validator->fails()){
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }
        $data = Subscriber::create([
            'subs_name'=>$subs_name, 
            'subs_email'=>$subs_email,
            'business_id'=>$business_id,
            'subs_company_name'=>$subs_company_name, 
            'subs_contact_no'=>$subs_contact_no
        ]);
        if($data){
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_SUBSCRIBER_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SUBSCRIBER_ADD');
        }
    }

}
