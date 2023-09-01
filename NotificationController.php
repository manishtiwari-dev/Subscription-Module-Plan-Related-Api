<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use ApiHelper;

use App\Events\MyEvent;


class NotificationController extends Controller
{

    public function get_all_notification(Request $request){
        
        $api_token = $request->api_token;
        $user_id = ApiHelper::get_adminid_from_token($api_token);
        $list = Notification::where('user_id',$user_id)->get();
        if(!empty($list)){
            $list->map(function($data){
                $data->message  =  $data->getNotiMessage;
                $data->userInfo = $data->userInfo;
                return $data;
            });
        }

        return ApiHelper::JSON_RESPONSE(true,$list,'');
    }

    
    public function get_unread_notification(Request $request){
        
        $api_token = $request->api_token;
        $user_id = ApiHelper::get_user_id_from_token($api_token);
        
        $db_query = Notification::where('user_id',$user_id)->where('is_read',0)->orderBy('notification_id', 'DESC')->take(10);
        $list_count = $db_query->count();
        $list = $db_query->get();

        if(!empty($list)){
            $list->map(function($data){
                $data->message  =  $data->getNotiMessage;
                $data->userInfo = $data->userInfo;
                return $data;
            });
        }

        $res = [
            'list'=>$list,
            'list_count'=>$list_count,
        ];
        
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }


    public function is_read_status(Request $request){
       
        $api_token = $request->api_token;
        $type = $request->type;

        $user_id = $request->user_id;

        $read_id = $request->notification_user_id;
        
        $db_query = Notification::query();

        if($type === 'all')
            $db_query = $db_query->where('user_id',$user_id);
        else
            $db_query = $db_query->where('notification_user_id',$read_id);

        $data = $db_query->update(['is_read'=>1,'read_at'=>date("Y-m-d h:s:i")]);

        return ApiHelper::JSON_RESPONSE(true,$data,'');

    }


    


}
