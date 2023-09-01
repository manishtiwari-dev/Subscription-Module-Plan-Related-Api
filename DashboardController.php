<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\AppSettingsGroup;

use App\Models\Currency;
use App\Models\Language;
use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use Modules\CRM\Models\CRMQuotation;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\OrderItems;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\Reviews;
use Modules\CRM\Models\Enquiry;
use Modules\CRM\Models\CRMCustomer;
use App\Models\Industry;
use App\Models\Subscription;
use App\Models\SubscriberHistory;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPlan;
use App\Models\LandingPlan;

use App\Models\SubscriptionTransaction;
use Modules\CRM\Models\Super\CRMTickets;
use Modules\Listing\Models\Listing;
use Modules\Listing\Models\BusinessCategory;
use DateTime;
use DateInterval;
use DatePeriod;
use App\Events\GlobalEventBetweenSuperAdminAndAdmin;
use App\Events\PrEventInternal;
use Carbon\Carbon;


class DashboardController extends Controller
{


    public function index(Request $request)
    {

        // dispatch global event
        // GlobalEventBetweenSuperAdminAndAdmin::dispatch('working');

        // dispatch private event
        // PrEventInternal::dispatch('pribvate-working');

        // Validate user page access
        $api_token = $request->api_token;
        $userType = $request->userType;

        $res = [];
        $industry_id = ApiHelper::get_industry_id_by_api_token($api_token);
        if ($userType == 'subscriber') {
            if ($industry_id == 1) {
                //  return ApiHelper::JSON_RESPONSE(true,$res,'');
                $res = $this->sales_admin_dashboard();
            } else {
                $res = $this->service_admin_dashboard();
            }
        } else {
            $res = $this->super_dashboard();
        }


        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    /* admin dashboard */
    public function sales_admin_dashboard()
    {
        $response = [];

        $response['total_orders'] = Order::count();
        $response['total_users'] = User::count();
        $response['total_quatations'] = CRMQuotation::count();
        $response['No_of_products'] = Product::count();
        $response['No_of_categories'] = Category::count();
        $response['total_enquiry'] = Enquiry::count();

        $response['No_of_reviews'] = Reviews::count();

        $response['total_enquiry_list'] = Enquiry::orderBy('enquiry_id', 'DESC')->take(5)->get();

        $response['total_customer_list'] = CRMCustomer::orderBy('customer_id', 'DESC')->take(5)->get();

        $response['total_orders_list'] = Order::selectRaw(' *, 
            CASE
                WHEN order_status = 1 THEN "Pending"
                WHEN order_status = 2 THEN "InProcess"
                WHEN order_status = 3 THEN "Dispatch"
                ELSE "Deliverd"
            END AS order_status,
            CASE
                WHEN payment_status = 1 THEN "Pending"
                WHEN payment_status = 2 THEN "Paid"
                ELSE "Failed"
            END AS payment_status
            ')->orderBy('order_id', 'DESC')->take(5)->get();


        $months = range(1, 12);

        $start = new DateTime;
        $start->setDate($start->format('Y'), $start->format('n'), 1); // Normalize the day to 1
        $start->setTime(0, 0, 0); // Normalize time to midnight
        $start->sub(new DateInterval('P12M'));
        $interval = new DateInterval('P1M');
        $recurrences = 12;

        foreach (new DatePeriod($start, $interval, $recurrences, true) as $date) {
            $month_name[] =  $date->format('F');
            $month_range[] =  $date->format('m');
        }



        // $month_name=['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July',
        // 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];


        $chartdata = [];

        $options = [
            'charts' => ['id' => "basic-bar"],
            "xaxis" => ["categories" => $month_name]
        ];



        foreach ($month_range as $key => $month) {

            $orderCount = Order::whereRaw('MONTH(created_at) = ' . $month)->count();

            array_push($chartdata, $orderCount);
        }


        $orders_query = Order::with('item', 'item.product')->select('*')
            ->whereMonth('created_at', Carbon::now()->month)
            ->where('payment_status', '1');

        $no_of_order =   $orders_query->count();
        $total_order_amount = $orders_query->sum('grand_total');

        $orders = $orders_query->get();

        $tax_type = ApiHelper::getKeySetVal('tax_type');
        $pc_profit_margin = ApiHelper::getAddOnSetVal('pc_profit_margin');


        $profit_amount = $total_profit = $total_product_price = $total_shipping_cost = $total_tax = 0;
        foreach ($orders as $key => $order) {

            $tax = $order->tax_amount;

            foreach ($order->item as $key => $item) {

                $product_total =  $item->total_price;
                if (!empty($item->product->source)) {
                    if ($item->product->source != 2) {
                        $profit_percentage = $item->product->profit_percent;
                    } else {
                        $profit_percentage = $pc_profit_margin;
                    }
                }

                if ($tax_type == 'inclusive') {
                    $product_profit_price = $product_total - $tax;
                } else {
                    $product_profit_price = $product_total;
                }

                $profit_amount = $product_profit_price * $profit_percentage / 100;

                $total_product_price += $product_profit_price - $profit_amount;
                $total_shipping_cost += $order->shipping_total;
                $total_tax += $tax;
                $total_profit += $profit_amount;
            }
        }


        $monthlyorder = [
            'no_order' => $no_of_order,
            'total_order_amount' => $total_order_amount,
            'label' => [
                'Profit', 'Product Cost', 'Shipping Cost', 'Tax'
            ],
            'values' => [
                ceil($total_profit), ceil($total_product_price), ceil($total_shipping_cost), ceil($total_tax)
            ]
        ];

        $response['sales'] = $monthlyorder;


        // $response['productdetails'] = [ 'options'=>$optionorder, 'series'=>[$seriesorder] ];

        $series = ["name" => "ORDER", "data" => $chartdata];

        $response['chartItem10Year'] = ['options' => $options, 'series' => [$series]];



        return $response;
    }



    /* super admin dashboard */
    public function service_admin_dashboard()
    {
        $response = [];

        $response['total_subscription'] = Subscription::count();
        $response['total_users'] = User::where('status', 1)->count();
        $response['total_quotations'] = CRMQuotation::where('status', 1)->count();
        $response['no_of_listing'] = Listing::where('status', 1)->count();
        $response['no_of_categories'] = BusinessCategory::where('status', 1)->count();
        $response['no_of_reviews'] = Reviews::where('status', 1)->count();

        $response['latest_enquiry_list'] = Enquiry::orderBy('enquiry_id', 'DESC')->take(5)->get();

        $response['plan_list'] = LandingPlan::orderBy('plan_id', 'DESC')->take(5)->get();
        $response['total_customer_list'] = CRMCustomer::orderBy('customer_id', 'DESC')->take(5)->get();

        $months = range(0, 30);

        $chartdata = [];
        $planId = [];
        $options = [
            'charts' => ['id' => "basic-bar"],
            "xaxis" => ["categories" => $months]
        ];



        foreach ($months as $key => $month) {

            $subsCount = SubscriptionHistory::all();

            if (!empty($subsCount)) {
                foreach ($subsCount as $key => $sub) {

                    array_push($planId, $sub->plan_id);
                }
            }


            $planCount = LandingPlan::where('plan_id', $planId)->whereRaw('DAY(created_at) = ' . $month)->count();

            array_push($chartdata, $planCount);
        }




        $series = ["name" => "Plan", "data" => $chartdata];

        $response['chartItem1Month'] = ['options' => $options, 'series' => [$series]];

        return $response;
    }


    /* super admin dashboard */
    public function super_dashboard()
    {
        $response = [];

        $response['active_subscription'] = Subscription::where('status', 1)->count();
        $response['pending_subscription'] = Subscription::where('status', 0)->count();
        $response['expired_subscription'] = Subscription::where('status', 2)->count();
        $response['total_users'] = User::count();
        $response['total_tickets'] = CRMTickets::count();

        $response['latest_subhistory_list'] = Subscription::orderBy('subs_history_id', 'DESC')->take(5)->get();
        $response['latest_transaction_list'] = SubscriptionTransaction::orderBy('subs_txn_id', 'DESC')->take(5)->get();
        $response['latest_tickets_list'] = CRMTickets::orderBy('id', 'DESC')->take(5)->get();



        $months = range(0, 30);

        //  $month_name=['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July',
        //  'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];


        $chartdata = [];

        $options = [
            'charts' => ['id' => "basic-bar"],
            "xaxis" => ["categories" => $months]
        ];


        foreach ($months as $key => $month) {



            $subsCount = Subscription::whereRaw('DAY(created_at) = ' . $month)->count();

            array_push($chartdata, $subsCount);
        }




        $series = ["name" => "Subscription", "data" => $chartdata];

        $response['chartItem10Year'] = ['options' => $options, 'series' => [$series]];

        return $response;
    }
}
