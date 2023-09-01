<?php


namespace App\Http\Controllers\Api;

use App\Models\SubscriberBusiness;
use App\Models\SubscriptionTransaction;
use App\Models\BusinessInfo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\LandUser;
use App\Models\Subscription;
use App\Models\Industry;
use App\Models\Super\IndustryCategory;
use Modules\CRM\Models\Super\SuperEnquiry;
use DateTime;

use ApiHelper;

use Carbon\Carbon;




class SubscriberBusinessController extends Controller
{
    public $page = 'businesses';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';




    public function index_all(Request $request)
    {
        $api_token = $request->api_token;
        $productItem = array();
        $businessList = SubscriberBusiness::all();
        foreach ($businessList as $key => $buss) {
            if (!empty($buss))
                array_push(
                    $productItem,
                    [
                        "value" => $buss->business_id,
                        "label" => $buss->business_name . '/' . $buss->business_unique_id . '/' . $buss->business_email . '/',
                    ]
                );
        }

        $approvalStatus = SubscriptionTransaction::all();

        $res = [

            'businessList' => $productItem,
            'approvalStatus' => $approvalStatus,

        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function create(Request $request)
    {
        $api_token = $request->api_token;
        //   $countryList = Country::all();
        //$enquiryList = SuperEnquiry::orderBy('enquiry_id', 'DESC')->get();
        $enquiryList = SuperEnquiry::select('customer_email as label', 'enquiry_id as value')->orderBy('enquiry_id', 'DESC')->get();
        $countryList = Country::select('countries_name as label', 'countries_id as value')->get();


        $res = [

            'country' => $countryList,
            'enquiryList' => $enquiryList,

        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function enquiryList(Request $request)
    {
        $api_token = $request->api_token;
        $enquiryList = SuperEnquiry::where('enquiry_id', $request->enquiry_id)->first();

        // $res = [

        //     'enquiryList' => $enquiryList,

        // ];

        return ApiHelper::JSON_RESPONSE(true, $enquiryList, '');
    }

    //This Function is used to show the list of subscribers
    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? (int)$request->perPage : ApiHelper::perPageItem();
        $search = $request->search;
        $sortBy = $request->sortBy;
        $orderBy = $request->orderBy;
        $status = $request->status;
        // $myDateTime = $request->start_date;
        // $start_date = $myDateTime->format('Y-m-d');
        //    return ApiHelper::JSON_RESPONSE(true,  $start_date, '');

        /*Fetching subscriber data*/

        $subscriber_query = SubscriberBusiness::with('subscription')->where('business_email', '!=', null);
        /*Checking if search data is not empty*/
        if (!empty($search)) {
            $subscriber_query = $subscriber_query
                ->where("business_unique_id", "LIKE", "%{$search}%")
                ->orWhere("business_name", "LIKE", "%{$search}%")
                ->orWhere("business_email", "LIKE", "%{$search}%");
        }

        if ($request->has('status')) {
            if ($request->status != null) {

                $subscriber_query = $subscriber_query->where('status', $request->status);
            }
        }

        /* Add Start Date Filter  */
        if ($request->has('start_date')) {
            if ($request->start_date != null) {
                $dateTime = Carbon::parse($request->start_date);
                $start_date = $dateTime->format('Y-m-d');
                // $myDateTime = DateTime::createFromFormat('Y-m-d\Th:i', $request->start_date);
                //   return ApiHelper::JSON_RESPONSE(true,  $myDateTime, '');
                //     $start_date = $myDateTime->format('Y-m-d');
                $subscriber_query = $subscriber_query->whereDate('signup_at', '>=', $start_date);
            }
        }

        /* Add End Date Filter  */
        if ($request->has('end_date')) {
            if ($request->end_date != null) {
                $dateTime = Carbon::parse($request->end_date);
                $end_date = $dateTime->format('Y-m-d');
                // $myDateTime = DateTime::createFromFormat('Y-m-d\Th:i', $request->end_date);
                // $end_date = $myDateTime->format('Y-m-d');
                $subscriber_query = $subscriber_query->whereDate('signup_at', '<=', $end_date);
            }
        }






        /* order by sorting */
        if (!empty($sortBy) && !empty($orderBy))
            $subscriber_query = $subscriber_query->orderBy($sortBy, $orderBy);
        else
            $subscriber_query = $subscriber_query->orderBy('business_id', 'DESC');

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $subscriber_count = $subscriber_query->count();

        $subscriber_list = $subscriber_query->skip($skip)->take($perPage)->get();

        $subscriber_list = $subscriber_list->map(function ($data) {

            if (!empty($data->business_info->country)) {
                $data->business_country = $data->business_info->country->countries_name;
            }

            $subsList = Subscription::with('industry_details', 'industry_category_details')->where('business_id', $data->business_id)->first();

            $data->subs = $subsList;
            if (!empty($data->landingUser)) {

                $data->UserType = ($data->landingUser->user_type == 1) ? 'Subscriber' : (($data->landingUser->user_type == 2) ? 'Affiliate' : 'Both');
            }

            return $data;
        });

        // $filtered = $subscriber_list->filter(function ($value, $key) use ($status) {

        //     //  return ApiHelper::JSON_RESPONSE(true, $value, '');


        //     $return_stat = false;
        //     if (!empty($value)) {
        //         $status_exist = 0;

        //         if (isset($status)) {
        //             if ($value->status == $status)
        //                 $status_exist = 1;
        //         } else {
        //             $status_exist = 1;
        //         }


        //         if ($status_exist) {
        //             return true;
        //         } else {
        //             return false;
        //         }
        //     } else if (isset($status)) {
        //         return false;
        //     } else {
        //         return true;
        //     }
        // });

        // $subscriber_list = $filtered->all();




        $subscriber_signUp = SubscriberBusiness::all();

        /*Binding data into a variable*/
        $res = [
            'data' => $subscriber_list,
            'subscriber_signUp' => $subscriber_signUp,
            'current_page' => $current_page,
            'total_records' => $subscriber_count,
            'total_page' => ceil((int)$subscriber_count / (int)$perPage),
            'per_page' => $perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    //This Function is used to get the details of subscriber data
    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;
        $business_id = $request->business_id;
        $sub_data = SubscriberBusiness::find($business_id);
        $sub_data->status = $request->status;
        $sub_data->save();

        return ApiHelper::JSON_RESPONSE(true, $sub_data, 'SUCCESS_STATUS_UPDATE');
    }


    //This Function is used to show the particular subscriber data
    public function edit(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $business_id = $request->business_id;
        $data = SubscriberBusiness::with('business_info', 'business_info.country')->where('business_id', $business_id)->first();

        if (!empty($data->business_info->billing_country)) {
            $data->selectedCountry = Country::select('countries_name as label', 'countries_id as value')->whereRaw('countries_id IN(' . $data->business_info->billing_country . ') ')->get();
        }

        $country_list = Country::select('countries_name as label', 'countries_id as value')->get();

        $res = [
            'country_list' => $country_list,
            'data_list' => $data
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    //This Function is used to update the particular subscriber data
    public function update(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }
        $business_id = $request->business_id;
        $billing_create = $request->only(['billing_city', 'billing_contact_name', 'billing_country', 'billing_phone', 'billing_state', 'billing_street_address', 'billing_zipcode', 'billing_company_name']);

        $business_name = $request->business_name;
        $business_email = $request->business_email;
        $validator = Validator::make($request->all(), [
            'business_name' => 'required',
            'business_email' => 'required',
        ], [
            'business_name.required' => 'BUSINESS_NAME_REQUIRED',
            'business_email.required' => 'BUSINESS_EMAIL_REQUIRED',
        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $data = SubscriberBusiness::where('business_id', $business_id)->update([
            'business_name' => $business_name,
            'business_email' => $business_email,
            'business_phone' => $request->billing_phone
        ]);

        $business_data = SubscriberBusiness::find($business_id);


        $landuser = LandUser::where('id', $business_data->user_id)->update([
            'first_name' => $business_name,
            'email' => $business_email,

        ]);

        $billing_create['billing_default'] = '1';
        $billing_create['status'] = 1;
        $billing_create['billing_email'] = $business_email;

        if (!empty($billing_create))
            $billing_data = BusinessInfo::where('business_id', $business_id)->update($billing_create);


        if ($business_data) {
            return ApiHelper::JSON_RESPONSE(true, $business_data, 'SUCCESS_SUBSCRIBER_BUSINESS_UPDATE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_SUBSCRIBER_BUSINESS_UPDATE');
        }
    }

    //This Function is used to add the subscriber data
    public function add(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $passwd = config('auth.default_password');

        $billing_create = $request->only(['billing_city', 'billing_contact_name', 'billing_country', 'billing_phone', 'billing_state', 'billing_street_address', 'billing_zipcode',]);
        $business_unique_id = $request->business_unique_id;
        $business_name = $request->business_name;
        $business_email = $request->business_email;
        $validator = Validator::make($request->all(), [
            'business_name' => 'required',
            'business_email' => 'required',
        ], [
            'business_name.required' => 'BUSINESS_NAME_REQUIRED',
            'business_email.required' => 'BUSINESS_EMAIL_REQUIRED',
        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }


        $landing_user = LandUser::where('email', $business_email)->first();
        if (empty($landing_user)) {
            $landuser = LandUser::create([
                'first_name' => $request->billing_contact_name,
                'email' => $business_email,
                'password' => Hash::make($passwd),
            ]);
        } else {

            return ApiHelper::JSON_RESPONSE(
                false,
                [],
                'EMAIL_ALREADY_EXISTS'
            );
        }


        $business_user = SubscriberBusiness::where('business_email', $business_email)->first();
        if (empty($business_user)) {
            $data = SubscriberBusiness::insertGetId([
                'business_unique_id' => ApiHelper::generate_random_token('alpha_numeric', 15),
                'business_name' => $business_name,
                'business_email' => $business_email,
                'business_phone' => $request->billing_phone,
                'user_id' => $landuser->id
            ]);
        } else {

            return ApiHelper::JSON_RESPONSE(
                false,
                [],
                'EMAIL_ALREADY_EXISTS'
            );
        }








        $billing_create['business_id'] = $data;
        $billing_create['billing_default'] = '1';
        $billing_create['status'] = 1;
        $billing_create['billing_email'] = $business_email;
        $billing_create['billing_company_name'] = $business_name;
        $billing_data = BusinessInfo::create($billing_create);




        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_SUBSCRIBER_BUSINESS_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_SUBSCRIBER_BUSINESS_ADD');
        }
    }
}
