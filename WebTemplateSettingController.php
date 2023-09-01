<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\TemplatesSetting;
use Illuminate\Http\Request;
use ApiHelper;

class WebTemplateSettingController extends Controller
{
    
  public function index(Request $request)
   {
        $api_token = $request->api_token;
       
       $template_list = TemplatesSetting::all();
        
        $detail=[];
        if(!empty( $template_list))

        {
            foreach ($template_list as $key => $template) {
                $detail[$template->template_key] = $template->template_value ;      
            }
        }   
      
        return ApiHelper::JSON_RESPONSE(true,$detail,'');
    } 

   public function store(Request $request)
   {
       $api_token = $request->api_token;

        $detail=TemplatesSetting::updateOrCreate(['template_key'   => $request->template_key],
       [
       'template_value'=>$request->template_value,

       ]
      );

    return ApiHelper::JSON_RESPONSE(true,$detail,'SUCCESS_THEME_UPDATE');  
  
   }
    
   public function updateSetting(Request $request)
   {
       $api_token = $request->api_token;

       $settingData = $request->all();
       if(!empty($settingData)){
        foreach($settingData as $settingKey=>$settingValue ){

            if($settingKey != 'api_token'){
                $detail=TemplatesSetting::updateOrCreate(['template_key'=> $settingKey],
                [
                'template_value'=>$settingValue,
        
                ]);
            }
        }
            
       }
       
        return ApiHelper::JSON_RESPONSE(true,$detail,'SUCCESS_THEME_SETTING_UPDATE');  
  
   }

}
