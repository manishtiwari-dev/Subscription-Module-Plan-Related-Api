<?php

namespace App\Http\Controllers\Api;

use ApiHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\DbUpdate;
use App\Models\Subscription;



class DbUpdateController extends Controller
{
    public $page = 'addon_manager';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $active_subscriber_query = Subscription::where('db_suffix', '!=', null)->where('status', 1)->orderBy('db_suffix', 'ASC')->get();
        $inactive_subscriber_query = Subscription::where('db_suffix', '!=', null)->where('status', 0)->orderBy('db_suffix', 'ASC')->get();

        $res = [
            'active_data_list' => $active_subscriber_query,
            'inactive_data_list' => $inactive_subscriber_query,

        ];


        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }



    // public function edit(Request $request)
    // {
    //     $setting_data = ApiHelper::addOnsetting();
    //     $countrydata = ApiHelper::allSupportCountry();
    //     $res = [
    //         'settinglist' => $setting_data,
    //         'country' => $countrydata

    //     ];

    //     return ApiHelper::JSON_RESPONSE(true, $res, '');
    // }


    public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $saveData = $request->db_name;
        $formdata = $request->all();
        //   return ApiHelper::JSON_RESPONSE(true, $formdata['query'], 'SUCCESS_DB_ADDED');
        $Dbdata = '';

        if (!empty($saveData)) {
            foreach ($saveData as $key => $data) {
                if (!empty($data)) {
                    $db_name = $request->db_prefix . "_" . $data;
                    //     return ApiHelper::JSON_RESPONSE(true,  $query, 'SUCCESS_DB_ADDED');

                    $Dbdata = DbUpdate::create([
                        'db_name' => $db_name,
                        'query' => $formdata['query']
                    ]);
                }
            }
        }



        if ($Dbdata)
            return ApiHelper::JSON_RESPONSE(true, $Dbdata, 'SUCCESS_DB_ADDED');
        else
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_DB_ADDED');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {

        //
    }

    public function ChangeStatus(Request $request)
    {
    }
}
