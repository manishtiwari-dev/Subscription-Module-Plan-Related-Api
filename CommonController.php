<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use ApiHelper;
use DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
// use Image;

class CommonController extends Controller
{
    public function language(Request $request){
        $all_lang = Language::all();
        return ApiHelper::JSON_RESPONSE(true,$all_lang,''); 
    }
    public function demo(Request $request){
        
        // $url = str_replace("blob:", "",$request->croppedImageProfile);

        // $mage = Image::make($url);

        return ApiHelper::JSON_RESPONSE(true,$request->file('croppedImageProfile'),'SUCCESS_DETAILS_UPDATE'); 
        
        $multiple_image = [];
        if($request->multiImage > 0 ){
            for ($i=0; $i < $request->multiImage ; $i++) { 
                $image_name = "image_key_".$i;
                $multiple_image[$i] = $request->$image_name->store("demoImage");
            }
        }
        
        $profile_image = '';
        if($request->profile_image){
            $profile_image = $request->profile_image->store('profile');
        }

        $status = DB::table('demo')->insert([
            'multIimage'=> implode(',', $multiple_image),
            'singleImage'=>$profile_image,
        ]);

        return ApiHelper::JSON_RESPONSE(true,$status,'SUCCESS_DETAILS_UPDATE');   
    }
}
