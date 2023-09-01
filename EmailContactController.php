<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\ContactToGroup;
use App\Models\ContactGroup;
use App\Models\MarketingContact;
use App\Models\ContactNewsLetter;
use App\Models\Country;


class EmailContactController extends Controller
{
    public $page = 'email_contact';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function list(Request $request){

            // Validate user page access
            $api_token = $request->api_token;

            if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
                return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

                $sortBY = $request->sortBy;
                $ASCTYPE = $request->orderBY;
        
                
     
         
      
            $data_list = ContactToGroup::with('group_details','contact_details')->where('group_id',$request->id)->orderBy('id','DESC')->get();
           
           


        if($request->has('id')){        
            //getting category Name
             $grpName=ContactGroup::where('id',$request->id )->first();
             $cName = !empty($grpName) ? $grpName->group_name : '';
        }


   //     $data_list = ContactGroup::where('id',$request->id)->get();

        $res = [
            'data_list'=> $data_list,
            'group_name'=>$cName,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
  

    public function create()
    {
       
        $countrydata = Country::all();
        $group_data =ContactGroup::all();
        $data=[
          
            'country_data'=>$countrydata,
            'group_data'=>$group_data,
        ];

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'');

        
    }

    public function store(Request $request)
    {
       // Validate user page access
       $api_token = $request->api_token;

       if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
           return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

         $contact_data=  MarketingContact::create([
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'company' => $request->company,
            'website' => $request->website,
            'countried_id' =>  $request->countried_id,
            'phone' =>  $request->phone,
            'address'=> $request->address,
            'favourites' =>  $request->favourites,
            'blocked' =>  $request->blocked,
            'trashed' =>  $request->trashed,
            'is_subscribed' =>  $request->is_subscribed,
            // 'is_unsubscribed' =>  $request->is_unsubscribed,
        ]);

           if(!empty($contact_data))       
           {
                ContactToGroup::create([
                    'group_id' => $request->id,
                    'contact_id' => $contact_data->id,
                ]);
           }       
           
           $contactNews=  ContactNewsLetter::create([
                'contact_id'=>$contact_data->id
            ]);

  
        if($contact_data)
            return ApiHelper::JSON_RESPONSE(true,$contact_data,'SUCCESS_CONTACT_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_CONTACT_ADD');

    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        $data_list = MarketingContact::with('contact')->where('id',$request->id)->first();
     //   return ApiHelper::JSON_RESPONSE(true,$data_list->contact,'');
        if (!empty($data_list->contact))  
           
        $data_list->contact = $data_list->contact->group_id;



        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }

    public function update(Request $request)
    { 

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
      
         $id=$request->id;
            
        $data = MarketingContact::where('id',$id)->update([
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'company' => $request->company,
            'website' => $request->website,
            'countried_id' =>  $request->countried_id,
            'phone' =>  $request->phone,
            'address'=> $request->address,
            'favourites' =>  $request->favourites,
            'blocked' =>  $request->blocked,
            'trashed' =>  $request->trashed,
            'is_subscribed' =>  $request->is_subscribed,
            // 'is_unsubscribed' =>  $request->is_unsubscribed,
        ]);

             
        $contact_details = MarketingContact::find($id);

      
          if(!empty($contact_details)){
           ContactToGroup::updateOrCreate([
            
                   'contact_id' =>$contact_details->id,
                ],
                [
                    'group_id' =>$request->group_id,
                ]
         
        );

      } 

                $contactNews=  ContactNewsLetter::where('contact_id',$contact_details->id)->update([
                    'contact_id'=>$contact_details->id,
                ]);



           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_CONTACT_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_CONTACT_UPDATE');
    }


    // public function destroy(Request $request)
    // {
    //     $api_token = $request->api_token;

    //     $status = EmailTemplates::where('template_id',$request->template_id)->delete();
    //     if($status) {
    //         return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_TEMPLATE_DELETE');
    //     }else{
    //         return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TEMPLATE_DELETE');
    //     }
    // }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $template_id = $request->template_id;
        $sub_data = ContactToGroup::find($template_id);
        $sub_data->status = ($sub_data->status == 0 ) ? 1 : 0;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }

//  public function view(Request $request)


//  {

//     // Validate user page access
//     $api_token = $request->api_token;


//     if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
//     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        
//     if($request->type == "default")
//     $response =  SuperEmailTemplates::find($request->update_id);
//      else
//     $response =EmailTemplates::find($request->update_id);








          

    

// //        $role_name = ApiHelper::get_role_from_token($api_token);

// //        $p_email = ApiHelper::get_parentemail_from_token($api_token);
// //        $userBus = UserBusiness::where('users_email', $p_email)->first();
// //        if(!empty($userBus))
// //        $userType = "subscriber";
// //        else
// //        $userType = '';

// //        if ($userType != 'subscriber') {
       
// //         $response = SuperEmailTemplates::find($request->template_id);
    
// //     }
// // else{
// //     $super_list = SuperEmailTemplates::where('template_id',$request->template_id)->get();
// //     if(!empty($super_list->template_id))
// //     {
// //         $response = SuperEmailTemplates::find($request->template_id);   
// //     }

// //     else{
// //     $response  = EmailTemplates::find($request->template_id);
    
// //     }
// // }




       
       
//         return ApiHelper::JSON_RESPONSE(true, $response, '');
//     }
      
public function import_file(Request $request)
{

    $dataList = ApiHelper::read_csv_data($request->fileInfo, "csv/Contact");
    //  dd($dataList);
    foreach ($dataList as $key => $value) {
       // dd($value[0]);
        
        // $details = $value[0];
        // return ApiHelper::JSON_RESPONSE(true, $details, 'SUCCESS_CONTACT_IMPORTED');

       if(isset($value)){


        $contact_name = isset($value[0]) ? $value[0] : 0;
        $contact_email = isset($value[1]) ? $value[1] : 0;
        $company=isset($value[2]) ? $value[2] : 0;
        $website = isset($value[3]) ? $value[3] : 0;
        $phone = isset($value[4]) ? $value[4] : 0;
        $address=isset($value[5]) ? $value[5] : 0;

        $newsletter_data =  MarketingContact::where('contact_email', $contact_email)->first();

        if (empty($newsletter_data))
         {
            $data = [
                'contact_name' => $contact_name,
                'contact_email'=>$contact_email,
                'company'=>$company, 
                'website'=>$website,
                'phone' =>$phone,
                'address' =>$address,
            ];
            $newsletter = MarketingContact::create($data);
        

        $Userdata= [

            'group_id'=>$request->group_id,
            'contact_id'=> $newsletter->id,

        ];

        $userCreate=ContactToGroup::create($Userdata);


        
        $contactNews= [
            'contact_id'=> $newsletter->id,
        ];


        $contact=ContactNewsLetter::create($contactNews);


            
    }
}
        
    }

    return ApiHelper::JSON_RESPONSE(true, $dataList, 'SUCCESS_CONTACT_IMPORTED');
}








}
