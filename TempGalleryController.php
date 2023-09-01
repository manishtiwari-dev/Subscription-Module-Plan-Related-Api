<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ApiHelper;
use Illuminate\Support\Facades\Storage;
use App\Models\TempImages;
use Image;
use File;

class TempGalleryController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //$res = $request->file('fileInfo');
        $image = $request->file('fileInfo');

        $times = time();
        $extension = $image->getClientOriginalExtension();
        $dir = "temp/" . $times;

        $path = $image->storeAs($dir, $times . '.' . $extension);

        $tmpimage = TempImages::create([
            'images_name' => $times,
            'images_ext' => $extension,
            'images_directory' => $dir,
            'images_size' => '',
        ]);
        $insertedId = $tmpimage->images_id;

        if ($tmpimage) {
            return ApiHelper::JSON_RESPONSE(true, $insertedId, 'SUCCESS_TEMP_IMAGE_UPLOAD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_TEMP_IMAGE_UPLOAD');
        }

        // return ApiHelper::JSON_RESPONSE(true, $insertedId, '');
    }
}
