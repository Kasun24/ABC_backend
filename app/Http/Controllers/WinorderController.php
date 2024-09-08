<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use DB;
use Illuminate\Support\Facades\Auth;
use Response;
use App;
use App\Models\Setting;
use App\Models\Restaurant;
use App\Models\Order;
use App\Models\ProductSizeScenarioToping;

class WinorderController extends Controller
{
    /**
     * Return Response
     *
     * @param array $arr
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function respondJsonReturn($arr){
        return response()->json($arr);
    }



    /**
     * Auth Verification
     *
     * @param array $arr
     * @return bool
     */
    protected function authVerification($arr){
        if(Auth::attempt(['email' => $arr["username"], 'password' => $arr["password"], 'status' => 'true','type' => 'restaurant'])){
            return true;
        }
        return false;
    }

    /**
     * Return Winorder For New Orders
     *
     * @param  Request  $request
     * @return Response
     */
    public function GetNewOrders(Request $request){
        $username = "username";
        $password = "password";
        if ($request->hasHeader($username) && $request->hasHeader($password)){
            if($this->authVerification(["username" => $request->header($username),"password" => $request->header($password)])){
                // get restaurant global settings to get postal code feature status
                $settings = Setting::select('is_postalcode')->first();
				
                $restaurant = Restaurant::find(Helper::ResID());
                if($restaurant && $restaurant->is_winorder == "true"){
                    $order = Order::where([['restaurants_id',Helper::ResID()],['status','paid'],['is_winorder','false']])->select('id')->skip(0)->take(1)->get();
                    $orderList = [];
                    $articleList = [];
                    if(isset($order[0])){
                        App::setLocale("de");
                        $order = Helper::OrderByID($order[0]->id);

                        foreach ($order->items as $k => $v) {

                            $tax_value = '';
                        
                            if(isset($v->taxList[0])){
                                $tax_value = number_format((float)$v->taxList[0]->amount, 2, '.', '');
                            }

                            if(isset($v->topingList) && isset($v->topingList[0])){

                                $sub_list = [];
                                $dish = Helper::ProductByID($v->dish_id);
                                $ArticleSize = "";

                                // Implemented this patch due to the wrong scenario order in winorder data set
                                // corrected the scenario order at 2024-05-21 by Pumayk26

                                // create a brand new array to make it easy for sorting
                                $toppingListForSorting = [];

                                foreach ($v->topingList as $key => $value) {
                                    // get topping and scenario positions 
                                    $toppingDetails = ProductSizeScenarioToping::where([['product_size_scenario_topings.id', $value->toping_id]])
                                                    ->join('product_size_scenarios','product_size_scenarios.id','=','product_size_scenario_topings.product_size_scenarios_id')
                                                    ->select('product_size_scenarios.position as scenarioPosition','product_size_scenario_topings.position as toppingPosition')
                                                    ->first();
                                    $value->positioning = $toppingDetails;
                                    // push the toping details with new position values into newly created array
                                    $toppingListForSorting[] = $value;
                                }

                                // sort toppings list by scenario and topping positions 
                                usort($toppingListForSorting, function ($a, $b) {
                                    if ($a['positioning']['scenarioPosition'] == $b['positioning']['scenarioPosition']) {
                                        return $a['positioning']['toppingPosition'] <=> $b['positioning']['toppingPosition'];
                                    }
                                    return $a['positioning']['scenarioPosition'] <=> $b['positioning']['scenarioPosition'];
                                });

                                foreach ($toppingListForSorting as $key => $value) {
                                    $sub_list[] = [
                                        "Price" => $value->price,
                                        "ArticleName" => $value->name,
                                        "ArticleNo" => "G".$value->toping_id,
                                        "Count" => $value->count,
                                        "Tax" => $tax_value,
                                        // topping and scenario positions data
                                        "SCENO_PO" => $value->positioning && $value->positioning->scenarioPosition ? $value->positioning->scenarioPosition : NULL,
                                        "TOPPG_PO" => $value->positioning && $value->positioning->toppingPosition ? $value->positioning->toppingPosition : NULL
                                    ];
                                }

                                // OLD FOREACH BEFORE SORTING TOPPINGS AND SCENARIOS
                                // foreach ($v->topingList as $key => $value) {
                                //     $sub_list[] = [
                                //         "Price" => $value->price,
                                //         "ArticleName" => $value->name,
                                //         "ArticleNo" => "T".$value->toping_id,
                                //         "Count" => $value->count,
                                //         "Tax" => $tax_value
                                //     ];
                                // }

                                if($dish->is_size == "true"){
                                    $sizeData = DB::table('product_sizes')->where('id',$v->size_id)->select('name')->get();
                                    if(isset($sizeData[0])){
                                        $ArticleSize = $sizeData[0]->name;
                                    }
                                }
    
                                $articleList[] = [
                                    "Price" => $v->dish_price,
                                    "ArticleSize" => $ArticleSize,
                                    "ArticleName" => $dish->name,
                                    "ArticleNo" => $dish->dish_number == "" ? "G".$dish->id : "G".$dish->dish_number,
                                    "Count" => $v->count,
                                    "Tax" => $tax_value,
                                    "SubArticleList" => [
                                        "SubArticle" => $sub_list
                                    ],
                                    "Comment" => $v->comment
                                ];

                            }else{

                                $dish = Helper::ProductByID($v->dish_id);
                                $ArticleSize = "";
                                if($dish->is_size == "true"){
                                    $sizeData = DB::table('product_sizes')->where('id',$v->size_id)->select('name')->get();
                                    if(isset($sizeData[0])){
                                        $ArticleSize = $sizeData[0]->name;
                                    }
                                }

                                $articleList[] = [
                                    "Price" => $v->dish_price,
                                    "ArticleSize" => $ArticleSize,
                                    "ArticleName" => $dish->name,
                                    "ArticleNo" => $dish->dish_number == "" ? "G".$dish->id : "G".$dish->dish_number,
                                    "Count" => $v->count,
                                    "Tax" => $tax_value,
                                    "Comment" => $v->comment
                                ];

                            }

                        }
                        
                        if($order->delivery_time === 'asap'){
                            $articleList[] = [
                                "Comment" => __('lang.lieferzeit_so_schnelle_wie_möglich'),
                                "Price" => "0",
                                "Count" => "1"
                            ];
                        }

                        if($order->remarks != ''){
                            $articleList[] = [
                                "Comment" => $order->remarks,
                                "Price" => "0",
                                "Count" => "1"
                            ];
                        }

                        $AddInfo = [
                            "DeliverLumpSum" => number_format((float)$order->delivery_cost, 2, '.', ''),
                            "DiscountValue" => number_format((float)$order->total_discount, 2, '.', ''),
                            "CurrencyStr" => "\ u00e2 \ u201a \ u00ac",
                            "PaymentType" => ($order->payment_type == 'cod') ? 'Barzahlung' : ($order->payment_type == 'ecCard' ? 'EC Gerät' : 'Online'),
                            "DeliverType" => __('lang.'.$order->order_delivery_type),
                            "Comment" => $order->special_note
                        ];

                        if($order->delivery_time !== 'asap'){
                            $AddInfo["DateTimeOrder"] = $order->delivery_time;
                        }

                        $customer_name = explode(' ',$order->name);
                        // START --------------------- extract order address elements -------------
                        $isPostalCode = $settings->is_postalcode;

                        // define default values for address components
                        $houseNo = '';
                        $street = '';
                        $zip = '';
                        $city = '';

                        if($isPostalCode === 'true'){
                            // Split the address string by ','
                            $addressParts = explode(',',trim($order->delivery_address));

                            // Ensure there are two elements
                            if (count($addressParts) == 2) {
                                // Trim whitespace from each element
                                $element1 = trim($addressParts[0]);
                                $element2 = trim($addressParts[1]);

                                // Split the first element by ' '
                                $element1Parts = explode(' ', $element1);

                                // Check if there are at least two parts in the first element
                                if (count($element1Parts) >= 2) {
                                    // Assign the last part to the houseNumber property
                                    $houseNumber = end($element1Parts);
                                    $houseNo = trim($houseNumber);

                                    // Concatenate the remaining parts to form the street property
                                    array_pop($element1Parts); // Remove the last element (house number)
                                    $street = implode(' ', $element1Parts);
                                    $street = trim($street);

                                    // Split the second element by ' ' to extract postalCode and city
                                    $element2Parts = explode(' ', $element2);

                                    // Check if there are at least two parts in the second element
                                    if (count($element2Parts) >= 2) {
                                        // Assign the last part to the postalCode property
                                        $postalCode = end($element2Parts);
                                        $zip = trim($postalCode);
                                        // Concatenate the remaining parts to form the city property
                                        array_pop($element2Parts); // Remove the last element (postal code)
                                        $cityI = implode(' ', $element2Parts);
                                        $city = trim($cityI);
                                        
                                    } else {
                                        // echo "Invalid address format: Unable to extract postalCode and city.";
                                    }
                                } else {
                                    // echo "Invalid address format: Unable to extract street and houseNumber.";
                                }
                            } else {
                                // echo "Invalid address format: Expected two elements separated by ','.";
                            }
                        }else{
                            $address = explode(',',trim($order->delivery_address));

                            $houseNo = isset($address[1]) ? trim($address[1]) : "";
                            $street = isset($address[0]) ? trim($address[0]) : "";
                            $zip = isset($address[2]) ? trim($address[2]) : "";
                            $city = isset($address[3]) ? trim($address[3]) : "";
                        }

                        // END --------------------- extract order address elements -------------

                        $orderList[] = [
                            'Order' => [
                                "OrderID"=> $order->payment_id,
                                "AddInfo" => $AddInfo,
                                "ArticleList" => [
                                    'Article' => $articleList
                                ],
                                "StoreData" => [
                                    "StoreId" => $restaurant->id,
                                    "StoreName" => $restaurant->name
                                ],
                                "ServerData" => [
                                    "Agent" => "WinOrder V6.0.0.0",
                                    "CreateDateTime" => $order->created_at,
                                    "Referer" => "WinOrder"
                                ],
                                "Customer" => [
                                    "CustomerNo" => isset($order->customerDetails->id) ? ("GM".$order->customerDetails->id) : ("GM0") ,
                                    "DeliveryAddress" => [
                                        "PhoneNo" => isset($order->mobile_number) ? $order->mobile_number : "",
                                        "Email" => isset($order->email) ? $order->email : "",
                                        "Surname" => isset($customer_name[1]) ? $customer_name[1] : "",
                                        "FirstName" => isset($customer_name[0]) ? $customer_name[0] : "",
                                        "LastName" => isset($customer_name[1]) ? $customer_name[1] : "",
                                        "HouseNo" => $houseNo,
                                        "Street" =>  $street,
                                        "DescriptionOfWay" => isset($order->backyard) ? $order->backyard : "",
                                        "Zip" => $zip,
                                        "City" => $city,
                                    ],
                                    "BillAddress" => [
                                        "Title" => '',
                                        "Surname" => isset($order->customerDetails->last_name)? $order->customerDetails->last_name : "",
                                        "FirstName" => isset($order->customerDetails->first_name)? $order->customerDetails->first_name : "",
                                        "LastName" => isset($order->customerDetails->last_name)? $order->customerDetails->last_name : "",
                                        "Company" => "",
                                        "Street" => $street,
                                        "HouseNo" => $houseNo,
                                        "Zip" => $zip,
                                        "City" => $city,
                                        "Email" => isset($order->customerDetails->email)? $order->customerDetails->email : "",
                                        "PhoneNo" => isset($order->customerDetails->mobile_number)? $order->customerDetails->mobile_number : "",
                                    ],
                                ],
                                "CreateDateTime" =>  $order->created_at
                            ]
                        ];

                        $orderSave = Order::find($order->id);
                        $orderSave->is_winorder = "true";
                        $orderSave->save();

                    }

                    $arr = [
                        'OrderList' => $orderList
                    ];
    
                    print_r(json_encode($arr));exit;

                }
            }else{
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }else{
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }



    /**
     * Change Winorder By Order Status
     *
     * @param  Request  $request
     * @return Response
     */
    public function SendTrackingStatus(Request $request){
        $username = "username";
        $password = "password";
        if ($request->hasHeader($username) && $request->hasHeader($password)){
            if($this->authVerification(["username" => $request->header($username),"password" => $request->header($password)])){
                $restaurant = Restaurant::find(Helper::ResID());
                if($restaurant && $restaurant->is_winorder == "true"){

                    $orderid = $request->ordersid;
                    $tracking_status = $request->trackingstatus;
                    $tracking_time = $request->deliver_eta;
                    $order = Order::where([['restaurants_id',Helper::ResID()],['status','paid'],['is_winorder','true'],['payment_id',$orderid]])->select('id')->get();

                    if(isset($order[0])){

                        $orderSave = Order::find($order[0]->id);
                        $orderSave->order_action_date = date('Y-m-d H:i:s');

                        if($tracking_status == '2'){
                            $explod_text_t = explode('T',$tracking_time);
                            $explod_text = explode(':',$explod_text_t[1]);
                            $time = $explod_text[0].':'.$explod_text[1];
                            $date = date("Y-m-d");
                            $timeResturent =  date("Y-m-d H:i", strtotime($date." ".$time));
                            $orderSave->delivery_time_resturent = $timeResturent;
                            $orderSave->status = "accepted";
                        }else if($tracking_status == '7'){
                            $orderSave->status = "rejected";
                        }

                        if($orderSave->save()){
                            // send emails for order status changes
                            $orderSave->restaurantDetails = $restaurant;
                            if($orderSave->status === "rejected"){
                                App::setLocale($orderSave->language);
                                $status = Helper::Send_mail(["order" => $orderSave], $orderSave->email, __('lang.order_rejected_title'), 'mail.order-customer-reject');
                                $orderSave->special_note = json_encode($status);
                            }else if($orderSave->status === "accepted"){
                                App::setLocale($orderSave->language);
                                $status = Helper::Send_mail(["order" => $orderSave], $orderSave->email, __('lang.order_accepted_title'), 'mail.order-customer-accepted');
                            }

                            $arr = [
                                'SendTrackingStatus' => true
                            ];
                            print_r(json_encode($arr));exit;
                        }

                    }

                }
            }else{
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
        $arr = [
            'SendTrackingStatus' => false
        ];
        print_r(json_encode($arr));exit;
    }
}
