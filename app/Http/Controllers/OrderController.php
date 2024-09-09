<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\MenuCategory;
use App\Models\MenuCategorySenario;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTax;
use App\Models\OrderItemToping;
use App\Models\OrderItemTopingTax;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductSizeScenarioToping;
use App\Models\Tax;
use Illuminate\Http\Request;
use stdClass;
use Carbon\Carbon;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    public function getCartSummary(Request $request)
    {
        $cart_items = json_decode(json_encode($request->cart_items));
        return response()->json(['status' => true, 'cart' => $this->getValidCartSummaryObject($cart_items)]);
    }

    public function sendToKitchen(Request $request)
    {
        $cart_items = json_decode(json_encode($request->cart_items));
        $validatedCart = $this->getValidCartSummaryObject($cart_items);

        //find restaurant

        //store order
        $ongoingOrder = Order::where('table_orders_id', $request->table_details['id'])->where('status', 'processing')->first();
        if (!$ongoingOrder) {
            //make a new ongoing order
            $ongoingOrder = new Order();
            $ongoingOrder->branch_id = $request->table_details['branch_id'];
            $ongoingOrder->table_orders_id = $request->table_details['id'];
            $ongoingOrder->payment_type = 'cash_handled_by_waiter';
            $ongoingOrder->payment_id = Helper::GetPaymentID();
            $ongoingOrder->status = 'processing'; //completed
            $ongoingOrder->order_delivery_type = 'dine_in';
            $ongoingOrder->total_tax_inclusive = Helper::formatNumber($validatedCart['cart_summary']['total_with_tax'] - $validatedCart['cart_summary']['total_without_tax']);
            $ongoingOrder->total_tax_exclusive = $ongoingOrder->total_tax_inclusive;
            $ongoingOrder->net_total_without_tax = $validatedCart['cart_summary']['total_without_tax'];
            $ongoingOrder->net_total = $validatedCart['cart_summary']['total_with_tax'];
            $ongoingOrder->total_discount = 0;
            $ongoingOrder->total_with_discount_price = $ongoingOrder->net_total;
            $ongoingOrder->delivery_tax_inclusive = 0;
            $ongoingOrder->delivery_tax_exclusive = 0;
            $ongoingOrder->delivery_cost = 0;
            $ongoingOrder->gross_total = $validatedCart['cart_summary']['total_with_tax'];
            $ongoingOrder->language = 'en';
            $ongoingOrder->device_id = $request->device_id;
            $ongoingOrder->is_winorder = 'false';
            $ongoingOrder->save();
        } else {
            //if already exist then calculate total values again
            $ongoingOrder->total_tax_inclusive = Helper::formatNumber($ongoingOrder->total_tax_inclusive + ($validatedCart['cart_summary']['total_with_tax'] - $validatedCart['cart_summary']['total_without_tax']));
            $ongoingOrder->total_tax_exclusive = $ongoingOrder->total_tax_inclusive;
            $ongoingOrder->net_total_without_tax = Helper::formatNumber($ongoingOrder->net_total_without_tax + $validatedCart['cart_summary']['total_without_tax']);
            $ongoingOrder->net_total = Helper::formatNumber($ongoingOrder->net_total + $validatedCart['cart_summary']['total_with_tax']);
            $ongoingOrder->total_discount = 0;
            $ongoingOrder->total_with_discount_price = $ongoingOrder->net_total;
            $ongoingOrder->delivery_tax_inclusive = 0;
            $ongoingOrder->delivery_tax_exclusive = 0;
            $ongoingOrder->delivery_cost = 0;
            $ongoingOrder->gross_total = Helper::formatNumber($ongoingOrder->gross_total + $validatedCart['cart_summary']['total_with_tax']);
            $ongoingOrder->save();
        }

        //get customer device
        $customer = CustomerDevice::where('device_id', $request->device_id)->first();

        // Badge Id
        $badgeId = 'ORDER-' . $ongoingOrder->id . '-' . Str::uuid();

        //if not device then store
        if (!$customer) {
            $customer = new CustomerDevice();
            $customer->device_id = $request->device_id ? $request->device_id : Helper::generateUniqueId();
            $customer->customer_name = $request->customer_name;
            $customer->user_type = $request->customer_name;
            $customer->save();
        }

        //store cart items in table
        foreach ($validatedCart['cart_items'] as $item) {
            $newItem = new OrderItem();
            $newItem->customer_device_id = $customer->id;
            $newItem->orders_id = $ongoingOrder->id;
            $newItem->dish_id = $item->id;
            $newItem->size_id = $item->selected_size_id;
            $newItem->count = $item->count;
            $newItem->dish_price = Helper::formatNumber($item->single_dish_price_without_tax + $item->single_dish_tax_amount);
            $newItem->topping_price = $item->single_dish_sub_topping_total_with_tax;
            $newItem->total = $item->single_dish_sub_total_with_tax;
            $newItem->net_total = $item->dish_grand_total_with_tax;
            $newItem->total_discount = 0;
            $newItem->total_tax_inclusive = Helper::formatNumber($item->dish_grand_total_with_tax - $item->dish_grand_total_without_tax);
            $newItem->total_tax_exclusive = $newItem->total_tax_inclusive;
            $newItem->gross_without_tax_price = $item->dish_grand_total_without_tax;
            $newItem->gross_total = $item->dish_grand_total_with_tax;
            $newItem->gross_total_with_discount = $item->dish_grand_total_with_tax;
            $newItem->status = 'processing';
            $newItem->badge_id = $badgeId;
            $newItem->save();

            //store toppings if available
            foreach ($item->selected_toppings as $topping) {
                $newTopping = new OrderItemToping();
                $newTopping->order_items_id = $newItem->id;
                $newTopping->toping_id = $topping->topping_id;
                $newTopping->count = $topping->count;
                $newTopping->price = Helper::formatNumber($topping->dine_in);
                $newTopping->total = $topping->topping_sub_total_with_tax;
                $newTopping->save();

                //store tax if available
                if (isset($topping->tax_object) && !empty($topping->tax_object)) {
                    $newOrderItemToppingTax = new OrderItemTopingTax();
                    $newOrderItemToppingTax->order_item_toping_id = $newTopping->id;
                    $newOrderItemToppingTax->taxes_id = $topping->tax_object->id;
                    $newOrderItemToppingTax->type = $topping->tax_object->type;
                    $newOrderItemToppingTax->tax_type = $topping->tax_object->apply_as;
                    $newOrderItemToppingTax->amount = $topping->tax_object->dine_in;
                    $newOrderItemToppingTax->total_amount = Helper::formatNumber($topping->topping_sub_total_with_tax - $topping->topping_sub_total_without_tax);
                    $newOrderItemToppingTax->save();
                }
            }

            //store dish tax if available
            if (isset($item->tax_object) && !empty($item->tax_object)) {
                $newOrderItemTax = new OrderItemTax();
                $newOrderItemTax->order_items_id = $newItem->id;
                $newOrderItemTax->taxes_id = $item->tax_object->id;
                $newOrderItemTax->type = $item->tax_object->type;
                $newOrderItemTax->tax_type = $item->tax_object->apply_as;
                $newOrderItemTax->amount = $item->tax_object->dine_in;
                $newOrderItemTax->total_amount = Helper::formatNumber($item->single_dish_tax_amount * $item->count);
                $newOrderItemTax->save();
            }
        }

        //todo: send to winorder


        return response()->json(['status' => true]);
    }

    public function sendToKitchenPOS(Request $request)
    {
        if (!$request->order_delivery_type) {
            return response()->json(['status' => false, 'message' => __('No order delivery type found')]);
        }

        $orderDeliveryType = $request->order_delivery_type;

        if (in_array($orderDeliveryType, ['delivery', 'pickup'])) {
            if (empty($request->customer_details['name']) || empty($request->customer_details['mobile_number'])) {
                return response()->json([
                    'status' => false,
                    'message' => __('Customer name and mobile number are required'),
                    'errors' => [
                        'name' => __('Customer name required'),
                        'mobile_number' => __('Customer mobile number required')
                    ]
                ]);
            }
            // Check if customer already exists, if not create a new one (but don't save yet)
            $customer = Customer::firstOrNew(
                [
                    'first_name' => explode(' ', $request->customer_details['name'])[0],
                    'last_name' => count(explode(' ', $request->customer_details['name'])) > 1 ? implode(' ', array_slice(explode(' ', $request->customer_details['name']), 1)) : '',
                    'mobile_number' => $request->customer_details['mobile_number'],
                    'type' => 'guest',
                    'is_newsalert' => 'false'
                ]
            );

            // If the customer is new, you can modify other fields before saving
            if (!$customer->exists) {
                // Set additional fields if needed
                // $customer->email = $request->customer_details['email'];
                $customer->save(); // Save the new customer
            }
        }

        $cart_items = json_decode(json_encode($request->cart_items));
        $validatedCart = $this->getValidCartSummaryObject($cart_items);

        //find restaurant

        //store order
        $ongoingOrder = [];
        if ($orderDeliveryType === 'dine_in') {
            $ongoingOrder = Order::where('table_orders_id', $request->table_details['id'])->where('status', 'processing')->first();
        }
        // $ongoingOrder = Order::where('table_orders_id', $request->table_details['id'])->where('status', 'processing')->first();
        if (!$ongoingOrder) {
            //make a new ongoing order
            $ongoingOrder = new Order();
            $ongoingOrder->branch_id = $request->table_details['branch_id'];
            $ongoingOrder->table_orders_id = $request->table_details['id'];
            $ongoingOrder->payment_type = 'cash_handled_by_waiter';
            $ongoingOrder->payment_id = Helper::GetPaymentID();
            $ongoingOrder->status = $orderDeliveryType === 'dine_in' ? 'processing' : 'completed'; //completed
            $ongoingOrder->order_delivery_type = $orderDeliveryType;
            $ongoingOrder->total_tax_inclusive = Helper::formatNumber($validatedCart['cart_summary']['total_with_tax'] - $validatedCart['cart_summary']['total_without_tax']);
            $ongoingOrder->total_tax_exclusive = $ongoingOrder->total_tax_inclusive;
            $ongoingOrder->net_total_without_tax = $validatedCart['cart_summary']['total_without_tax'];
            $ongoingOrder->net_total = $validatedCart['cart_summary']['total_with_tax'];
            $ongoingOrder->total_discount = 0;
            $ongoingOrder->total_with_discount_price = $ongoingOrder->net_total;
            $ongoingOrder->delivery_tax_inclusive = 0;
            $ongoingOrder->delivery_tax_exclusive = 0;
            $ongoingOrder->delivery_cost = 0;
            $ongoingOrder->gross_total = $validatedCart['cart_summary']['total_with_tax'];
            $ongoingOrder->language = 'en';
            $ongoingOrder->device_id = $request->device_id;
            $ongoingOrder->is_winorder = 'false';
            $ongoingOrder->name = $request->customer_details && $request->customer_details['name'] ? $request->customer_details['name'] : null;
            $ongoingOrder->mobile_number = $request->customer_details && $request->customer_details['mobile_number'] ? $request->customer_details['mobile_number'] : null;
            $ongoingOrder->save();
        } else {
            //if already exist then calculate total values again
            $ongoingOrder->total_tax_inclusive = Helper::formatNumber($ongoingOrder->total_tax_inclusive + ($validatedCart['cart_summary']['total_with_tax'] - $validatedCart['cart_summary']['total_without_tax']));
            $ongoingOrder->total_tax_exclusive = $ongoingOrder->total_tax_inclusive;
            $ongoingOrder->net_total_without_tax = Helper::formatNumber($ongoingOrder->net_total_without_tax + $validatedCart['cart_summary']['total_without_tax']);
            $ongoingOrder->net_total = Helper::formatNumber($ongoingOrder->net_total + $validatedCart['cart_summary']['total_with_tax']);
            $ongoingOrder->total_discount = 0;
            $ongoingOrder->total_with_discount_price = $ongoingOrder->net_total;
            $ongoingOrder->delivery_tax_inclusive = 0;
            $ongoingOrder->delivery_tax_exclusive = 0;
            $ongoingOrder->delivery_cost = 0;
            $ongoingOrder->gross_total = Helper::formatNumber($ongoingOrder->gross_total + $validatedCart['cart_summary']['total_with_tax']);
            $ongoingOrder->save();
        }

        //get customer device
        $customer = CustomerDevice::where('device_id', $request->device_id)->first();

        // Badge Id
        $badgeId = 'ORDER-' . $ongoingOrder->id . '-' . Str::uuid();

        //if not device then store
        if (!$customer) {
            $customer = new CustomerDevice();
            $customer->device_id = $request->device_id ? $request->device_id : Helper::generateUniqueId();
            $customer->customer_name = $request->customer_name;
            $customer->user_type = $request->customer_name;
            $customer->save();
        }

        //store cart items in table
        foreach ($validatedCart['cart_items'] as $item) {
            $newItem = new OrderItem();
            $newItem->customer_device_id = $customer->id;
            $newItem->orders_id = $ongoingOrder->id;
            $newItem->dish_id = $item->id;
            $newItem->size_id = $item->selected_size_id;
            $newItem->count = $item->count;
            $newItem->dish_price = Helper::formatNumber($item->single_dish_price_without_tax + $item->single_dish_tax_amount);
            $newItem->topping_price = $item->single_dish_sub_topping_total_with_tax;
            $newItem->total = $item->single_dish_sub_total_with_tax;
            $newItem->net_total = $item->dish_grand_total_with_tax;
            $newItem->total_discount = 0;
            $newItem->total_tax_inclusive = Helper::formatNumber($item->dish_grand_total_with_tax - $item->dish_grand_total_without_tax);
            $newItem->total_tax_exclusive = $newItem->total_tax_inclusive;
            $newItem->gross_without_tax_price = $item->dish_grand_total_without_tax;
            $newItem->gross_total = $item->dish_grand_total_with_tax;
            $newItem->gross_total_with_discount = $item->dish_grand_total_with_tax;
            $newItem->status = 'processing';
            $newItem->badge_id = $badgeId;
            $newItem->save();

            //store toppings if available
            foreach ($item->selected_toppings as $topping) {
                $newTopping = new OrderItemToping();
                $newTopping->order_items_id = $newItem->id;
                $newTopping->toping_id = $topping->topping_id;
                $newTopping->count = $topping->count;
                $newTopping->price = Helper::formatNumber($topping->dine_in);
                if ($orderDeliveryType === 'dine_in') {
                    $newTopping->price = Helper::formatNumber($topping->dine_in);
                } elseif ($orderDeliveryType === 'delivery') {
                    $newTopping->price = Helper::formatNumber($topping->delivery);
                } elseif ($orderDeliveryType === 'pickup') {
                    $newTopping->price = Helper::formatNumber($topping->pickup);
                }
                $newTopping->total = $topping->topping_sub_total_with_tax;
                $newTopping->save();

                //store tax if available
                if (isset($topping->tax_object) && !empty($topping->tax_object)) {
                    $newOrderItemToppingTax = new OrderItemTopingTax();
                    $newOrderItemToppingTax->order_item_toping_id = $newTopping->id;
                    $newOrderItemToppingTax->taxes_id = $topping->tax_object->id;
                    $newOrderItemToppingTax->type = $topping->tax_object->type;
                    $newOrderItemToppingTax->tax_type = $topping->tax_object->apply_as;
                    if ($orderDeliveryType === 'dine_in') {
                        $newOrderItemToppingTax->amount = $topping->tax_object->dine_in;
                    } elseif ($orderDeliveryType === 'delivery') {
                        $newOrderItemToppingTax->amount = $topping->tax_object->delivery;
                    } elseif ($orderDeliveryType === 'pickup') {
                        $newOrderItemToppingTax->amount = $topping->tax_object->pickup;
                    }
                    $newOrderItemToppingTax->total_amount = Helper::formatNumber($topping->topping_sub_total_with_tax - $topping->topping_sub_total_without_tax);
                    $newOrderItemToppingTax->save();
                }
            }

            //store dish tax if available
            if (isset($item->tax_object) && !empty($item->tax_object)) {
                $newOrderItemTax = new OrderItemTax();
                $newOrderItemTax->order_items_id = $newItem->id;
                $newOrderItemTax->taxes_id = $item->tax_object->id;
                $newOrderItemTax->type = $item->tax_object->type;
                $newOrderItemTax->tax_type = $item->tax_object->apply_as;
                if ($orderDeliveryType === 'dine_in') {
                    $newOrderItemTax->amount = $item->tax_object->dine_in;
                } elseif ($orderDeliveryType === 'delivery') {
                    $newOrderItemTax->amount = $item->tax_object->delivery;
                } elseif ($orderDeliveryType === 'pickup') {
                    $newOrderItemTax->amount = $item->tax_object->pickup;
                }
                $newOrderItemTax->total_amount = Helper::formatNumber($item->single_dish_tax_amount * $item->count);
                $newOrderItemTax->save();
            }
        }

        //todo: send to winorder


        return response()->json(['status' => true]);
    }

    private function getValidCartSummaryObject($cart_items)
    {

        $summaryWithCartItems = [];

        $summary['total_with_tax'] = 0;
        $summary['total_without_tax'] = 0;

        //walkthrough cart items
        foreach ($cart_items as $dish) {
            //get dish from DB
            $resultDish = Product::find($dish->id);
            if ($resultDish) {
                $dish_price = $resultDish->dine_in;
                //check has size
                $resultSize = null;
                if ($dish->selected_size_id) {
                    $resultSize = ProductSize::find($dish->selected_size_id);
                    if ($resultSize) {
                        $dish_price = $resultSize->dine_in;
                    }
                }

                //todo: check discount


                $amountWithoutTax = $dish_price;
                $taxAmount = 0; //default 0 no tax

                //check tax
                $resultMenuCategory = MenuCategory::find($resultDish->menu_categories_id);
                if ($resultMenuCategory->tax) {
                    $resultTax = Tax::find($resultMenuCategory->tax);
                    if ($resultTax) {
                        list($amountWithoutTax, $taxAmount) = Helper::getTaxValues($resultTax, $dish_price);
                        $dish->tax_object = $resultTax;
                    }
                }

                //single dish prices
                $dish->single_dish_price_without_tax = Helper::formatNumber($amountWithoutTax);
                $dish->single_dish_tax_amount = Helper::formatNumber($taxAmount);



                //line total dish prices
                $dish->dish_sub_total_without_tax = Helper::formatNumber($amountWithoutTax * $dish->count);
                $dish->dish_sub_total_with_tax = Helper::formatNumber(($amountWithoutTax + $taxAmount) * $dish->count);



                $dish->dish_grand_total_with_tax = $dish->dish_sub_total_with_tax;
                $dish->dish_grand_total_without_tax = $dish->dish_sub_total_without_tax;

                $dish->dish_sub_topping_total = 0;

                //calculate topping total if available
                if (isset($dish->selected_toppings) && !empty($dish->selected_toppings)) {
                    foreach ($dish->selected_toppings as $topping) {
                        $resultTopping = ProductSizeScenarioToping::find($topping->topping_id);
                        if ($resultTopping) {

                            $topping_price = $resultTopping->dine_in;

                            $resultTax = Tax::where('id', function ($query) use ($resultTopping) {
                                $query->select('topping_tax')
                                    ->from('menu_category_senarios')
                                    ->where('id', function ($query) use ($resultTopping) {
                                        $query->select('menu_category_senarios_id')
                                            ->from('product_size_scenarios')
                                            ->where('id', function ($query) use ($resultTopping) {
                                                $query->select('product_size_scenarios_id')
                                                    ->from('product_size_scenario_topings')
                                                    ->where('id', $resultTopping->id)
                                                    ->first(); // Ensure to get the scalar value
                                            })
                                            ->first(); // Ensure to get the scalar value
                                    })
                                    ->first(); // Ensure to get the scalar value
                            })->first();


                            $amountWithoutTax = $topping_price;
                            $taxAmount = 0; //default 0 no tax

                            if ($resultTax) {
                                list($amountWithoutTax, $taxAmount) = Helper::getTaxValues($resultTax, $topping_price);
                                $topping->tax_object = $resultTax;
                            }

                            //single topping prices
                            $topping->single_topping_price_without_tax = Helper::formatNumber($amountWithoutTax);
                            $topping->single_topping_tax_amount = Helper::formatNumber($taxAmount);

                            //line total dish prices
                            $topping->topping_sub_total_without_tax = Helper::formatNumber($amountWithoutTax * $topping->count);
                            $topping->topping_sub_total_with_tax = Helper::formatNumber(($amountWithoutTax + $taxAmount) * $topping->count);
                            //grand dish total +=
                            $dish->dish_grand_total_with_tax += $topping->topping_sub_total_with_tax * $dish->count;
                            $dish->dish_grand_total_without_tax += $topping->topping_sub_total_without_tax * $dish->count;
                            $dish->dish_sub_topping_total += $topping->topping_sub_total_with_tax * $dish->count;
                        }
                    }
                }


                $dish->single_dish_sub_topping_total_with_tax = Helper::formatNumber($dish->dish_sub_topping_total);
                $dish->single_dish_sub_total_with_tax = Helper::formatNumber($dish->dish_sub_topping_total + $dish->single_dish_price_without_tax + $dish->single_dish_tax_amount);

                $dish->dish_grand_total_with_tax = Helper::formatNumber($dish->dish_grand_total_with_tax);
                $dish->dish_grand_total_without_tax = Helper::formatNumber($dish->dish_grand_total_without_tax);
                $summary['total_with_tax'] += $dish->dish_grand_total_with_tax;
                $summary['total_without_tax'] += $dish->dish_grand_total_without_tax;
            }
        }
        $summary['total_with_tax'] = Helper::formatNumber($summary['total_with_tax']);
        $summary['total_without_tax'] = Helper::formatNumber($summary['total_without_tax']);
        $summaryWithCartItems['cart_items'] = $cart_items;
        $summaryWithCartItems['cart_summary'] = $summary;
        return $summaryWithCartItems;
    }

    public function getOrderDetails(Request $request)
    {

        // Find the order by ID
        $order = Order::where('table_orders_id', $request->table_id)->where('status', 'processing')->with('orderItems')->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => __('Not found')]);
        }

        // Get order items
        $orderItems = $order->orderItems()->with('orderItemToppings', 'orderItemTaxes', 'product')->get();

        // Structure the response data
        $orderDetails = [
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'table_orders_id' => $order->table_orders_id,
            'payment_type' => $order->payment_type,
            'payment_id' => $order->payment_id,
            'status' => $order->status,
            'order_delivery_type' => $order->order_delivery_type,
            'total_tax_inclusive' => $order->total_tax_inclusive,
            'total_tax_exclusive' => $order->total_tax_exclusive,
            'net_total_without_tax' => $order->net_total_without_tax,
            'net_total' => $order->net_total,
            'total_discount' => $order->total_discount,
            'total_with_discount_price' => $order->total_with_discount_price,
            'delivery_tax_inclusive' => $order->delivery_tax_inclusive,
            'delivery_tax_exclusive' => $order->delivery_tax_exclusive,
            'delivery_cost' => $order->delivery_cost,
            'gross_total' => $order->gross_total,
            'language' => $order->language,
            'device_id' => $order->device_id,
            'customer_device' => [
                'device_id' => $order->customerDevice->device_id,
                'customer_name' => $order->customerDevice->customer_name,
                'user_type' => $order->customerDevice->user_type
            ],
            'order_items' => [],
        ];

        foreach ($orderItems as $item) {

            $itemDetails = [
                'item_id' => $item->id,
                'dish_id' => $item->dish_id,
                'size_id' => $item->size_id,
                'count' => $item->count,
                'dish_name' =>  $item->size_id != null ?  $item->productSize->product->name . ' (' . $item->productSize->name . ')' : $item->product->name,
                'dish_price' => $item->dish_price,
                'topping_price' => $item->topping_price,
                'total' => $item->total,
                'net_total' => $item->net_total,
                'total_discount' => $item->total_discount,
                'total_tax_inclusive' => $item->total_tax_inclusive,
                'total_tax_exclusive' => $item->total_tax_exclusive,
                'gross_without_tax_price' => $item->gross_without_tax_price,
                'gross_total' => $item->gross_total,
                'gross_total_with_discount' => $item->gross_total_with_discount,
                'status' => $item->status,
                'customer_device_id' => $item->customer_device_id,
                'customer_device' => [
                    'device_id' => $item->customerDevice->device_id,
                    'customer_name' => $item->customerDevice->customer_name,
                    'user_type' => $item->customerDevice->user_type
                ],
                'toppings' => [],
                'taxes' => [],
                'badge_id' => $item->badge_id,
            ];

            // Get item toppings
            foreach ($item->orderItemToppings as $topping) {
                $toppingDetails = [
                    'topping_id' => $topping->id,
                    'topping_name' => $topping->topping->name,
                    'toping_id' => $topping->toping_id,
                    'count' => $topping->count,
                    'price' => $topping->price,
                    'total' => $topping->total,
                ];

                // Get topping taxes
                $toppingTaxes = OrderItemTopingTax::where('order_item_toping_id', $topping->id)->get();
                foreach ($toppingTaxes as $toppingTax) {
                    $toppingDetails['taxes'][] = [
                        'taxes_id' => $toppingTax->taxes_id,
                        'type' => $toppingTax->type,
                        'tax_type' => $toppingTax->tax_type,
                        'amount' => $toppingTax->amount,
                        'total_amount' => $toppingTax->total_amount,
                    ];
                }

                $itemDetails['toppings'][] = $toppingDetails;
            }

            // Get item taxes
            foreach ($item->orderItemTaxes as $tax) {
                $itemDetails['taxes'][] = [
                    'taxes_id' => $tax->taxes_id,
                    'type' => $tax->type,
                    'tax_type' => $tax->tax_type,
                    'amount' => $tax->amount,
                    'total_amount' => $tax->total_amount,
                ];
            }

            $orderDetails['order_items'][] = $itemDetails;
        }

        return response()->json(['status' => true, 'order_details' => $orderDetails]);
    }

    public function getOrderStatus(Request $request)
    {
        $order = Order::find($request->order_id);
        return response()->json(['status' => $order->status == 'processing' ? false : true]);
    }

    public function completeOrder(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$request->paymentMethod || !isset($request->paymentMethod)) {
            return response()->json(['status' => false]);
        }

        if ($order && $order->status == 'processing') {
            $payment = new OrderPayment();
            $payment->orders_id = $order->id;
            $payment->customer_id = $order->customerDevice->id;
            $payment->amount = $order->gross_total;
            $payment->payment_type = $request->paymentMethod;
            $payment->waiter_id = auth()->user()->id;
            $payment->save();

            $order->status = 'completed';
            $order->save();

            return response()->json(['status' => true, 'msg' => __('Order Completed')]);
        }

        return response()->json(['status' => false]);
    }

    public function sendTrackingStatus(Request $request)
    {

        $arr = [
            'SendTrackingStatus' => false
        ];
        print_r(json_encode($arr));
        exit;
    }

    protected function authVerification($arr)
    {
        $un = 'WINORDER';
        $pw = "JXFD1skOvQ6jUCkDkZVGXM";
        if ($arr['username'] == $un && $arr['password'] == $pw) {
            return true;
        }
        return false;
    }

    public function winorderGetNewOrder(Request $request)
    {


        $orderList = [];
        $username = "username";
        $password = "password";

        if ($request->hasHeader($username) && $request->hasHeader($password)) {
            if ($this->authVerification(["username" => $request->header($username), "password" => $request->header($password)])) {
            } else {
                print_r(json_encode(['OrderList' => $orderList]));
                exit;
            }
        } else {
            print_r(json_encode(['OrderList' => $orderList]));
            exit;
        }

        //find orders that not sync with winorder
        $orderIds = OrderItem::where('is_winorder', 'false')->pluck('orders_id')->unique()->values()->toArray();

        $orderList = [];

        foreach ($orderIds as $orderId) {
            $orderObj = new stdClass();
            $order = Order::find($orderId);
            $orderObj->OrderID = $order->payment_id . rand(5, 10);

            $AddInfo = new stdClass();
            $AddInfo->DeliverLumpSum = 0;
            $AddInfo->DiscountValue = 0;
            $AddInfo->CurrencyStr = "€";
            $AddInfo->PaymentType = 'Barzahlung';
            $AddInfo->DeliverType = 'dine-in';
            $AddInfo->Comment = "Table: " . $order->table->name;
            $orderObj->AddInfo = $AddInfo;

            $ArticleList = [];
            foreach ($order->winOrderNotSendItems as $item) {
                $Article = new stdClass();
                $Article->Price = Helper::formatNumber($item->dish_price);
                $Article->ArticleSize = $item->size_id != null ? $item->productSize->name : '';
                $Article->ArticleNo = $item->product->dish_number == "" ? "G" . $item->product->id : "G" . $item->product->dish_number;
                $Article->ArticleName = $item->size_id != null ? $item->productSize->product->name : $item->product->name;
                $Article->Count = $item->count;
                $Article->Tax = Helper::formatNumber($item->total_tax_exclusive);
                $Article->Comment = "Table: " . $order->table->name;

                $SubArticleList = [];
                foreach ($item->orderItemToppings as $topping) {
                    $SubArticle = new stdClass();
                    $SubArticle->Price = Helper::formatNumber($topping->price);
                    $SubArticle->ArticleName = $topping->topping->name;
                    $SubArticle->ArticleNo = "G" . $topping->toping_id;
                    $SubArticle->Count = $topping->count;
                    $SubArticle->Tax = $topping->tax ? Helper::formatNumber($topping->tax->total_amount) : '';
                    $SubArticleList[] = $SubArticle;
                }

                $Article->SubArticleList = ['SubArticle' => $SubArticleList];
                $ArticleList[] = $Article;
                //mark as sent
                $item->is_winorder = 'true';
                $item->save();
            }

            $orderObj->ArticleList = ['Article' => $ArticleList];

            $StoreData = new stdClass();
            $StoreData->StoreId = $order->branch_id;
            $StoreData->StoreName = $order->branch->name;
            $orderObj->StoreData = $StoreData;

            $ServerData = new stdClass();
            $ServerData->Agent = "WinOrder V6.0.0.0";
            $ServerData->IpAddress = request()->ip();
            $ServerData->CreateDateTime = Carbon::now()->toDateTimeString();
            $ServerData->Referer = "WinOrder";
            $orderObj->ServerData = $ServerData;

            $Customer = new stdClass();
            $Customer->CustomerNo = intval($order->table->name);

            $DeliveryAddress = new stdClass();
            $DeliveryAddress->PhoneNo = "";
            $DeliveryAddress->Email = "";
            $DeliveryAddress->Title = "";
            $DeliveryAddress->FirstName = "";
            $DeliveryAddress->LastName = "";
            $DeliveryAddress->HouseNo = "";
            $DeliveryAddress->Street = "";
            $DeliveryAddress->DescriptionOfWay = "";
            $DeliveryAddress->Zip = "";
            $DeliveryAddress->City = "";
            $DeliveryAddress->State = "";
            $DeliveryAddress->Country = "";
            $DeliveryAddress->Company = "";
            $DeliveryAddress->PayPalEmail = "";
            $DeliveryAddress->Fax = "";
            $Customer->DeliveryAddress = $DeliveryAddress;

            $BillAddress = new stdClass();
            $BillAddress->Title = "";
            $BillAddress->FirstName = "";
            $BillAddress->LastName = "";
            $BillAddress->Company = "";
            $BillAddress->Street = "";
            $BillAddress->HouseNo = "";
            $BillAddress->Zip = "";
            $BillAddress->City = "";
            $BillAddress->Email = "";
            $BillAddress->PhoneNo = "";
            $Customer->BillAddress = $BillAddress;

            $orderObj->Customer = $Customer;
            $orderObj->CreateDateTime = Carbon::now()->toDateTimeString();

            $orderList[] = ['Order' => $orderObj];
        }

        print_r(json_encode(['OrderList' => $orderList]));
        exit;

        /*$data = [
            "OrderList" => [
                "Order" => [
                    "CreateDateTime" => Carbon::now()->toDateTimeString(),
                    "OrderID" => "21",
                    "Customer" => [
                        "CustomerNo" => "25",
                        "DeliveryAddress" => [
                            "Title" => "Herr",
                            "FirstName" => "Erika",
                            "LastName" => "Mustermann",
                            "Company" => "PixelPlanet GmbH",
                            "Street" => "Hoyaer Str",
                            "HouseNo" => "13",
                            "AddAddress" => "Am Brommyplatz",
                            "DescriptionOfWay" => "Optional description",
                            "Zip" => "28205",
                            "City" => "Bremen",
                            "State" => "Bremen",
                            "Country" => "Deutschland",
                            "Email" => "info@pixelplanet.de",
                            "PayPalEmail" => "info@pixelplanet.de",
                            "PhoneNo" => "0421/247780",
                            "Fax" => "0421/2477824"
                        ],
                        "BillAddress" => [
                            "Title" => "",
                            "FirstName" => "",
                            "LastName" => "",
                            "Company" => "",
                            "Street" => "",
                            "HouseNo" => "",
                            "Zip" => "",
                            "City" => "",
                            "State" => "",
                            "Country" => "",
                            "Email" => "",
                            "PhoneNo" => "",
                            "Mobile" => "",
                            "Fax" => "",
                            "Comment" => ""
                        ]
                    ],
                    "ServerData" => [
                        "IpAddress" => request()->ip(),
                        "Agent" => "Mozilla Firefox",
                        "Referer" => "http://www.winorder.de",
                        "CreateDateTime" => Carbon::now()->toDateTimeString()
                    ],
                    "StoreData" => [
                        "StoreId" => "1",
                        "StoreName" => "PixelPlanet Testshop"
                    ],
                    "AddInfo" => [
                        "DateTimeOrder" => "15:00:00",
                        "DiscountValue" => "2",
                        "CurrencyStr" => "€",
                        "DeliverLumpSum" => "5.00",
                        "DeliverType" => "Lieferung",
                        "AcceptedByEmployeeNo" => "-1",
                        "DeliveredByEmployeeNo" => "-1",
                        "Comment" => "Fahrer soll hupen",
                        "PaymentType" => "Online bezahlt",
                        "PaymentFee" => "1.20",
                        "MinOrderValue" => "20.00",
                        "MinQuantitySurcharge" => "1.00",
                        "TransactionID" => "ID0815",
                        "Tip" => "1.00",
                        "Total" => "11.00"
                    ],
                    "ArticleList" => [
                        "Article" => [
                            [
                                "ArticleNo" => "23A",
                                "ArticleName" => "Grundpizza",
                                "ArticleSize" => "klein (26 cm)",
                                "Count" => "1",
                                "Price" => "5.9",
                                "Tax" => "",
                                "Deposit" => "",
                                "Comment" => "",
                                "SubArticleList" => [
                                    "SubArticle" => [
                                        [
                                            "ArticleNo" => "",
                                            "ArticleName" => "",
                                            "Count" => "",
                                            "Price" => "",
                                            "Partition" => "",
                                            "Tax" => "",
                                            "Deposit" => "",
                                            "Comment" => ""
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        print_r(json_encode($data));
        exit;*/
        //todo: auth

        //find orders that not sync with  winorder
        /*$orderIds = OrderItem::where('is_winorder', 'false')->pluck('orders_id')->unique()->values()->toArray();

        $orderList = [];

        foreach ($orderIds as $orderId) {

            $orderObj = new stdClass();

            $order = Order::find($orderId);
            $orderObj->OrderID = $order->payment_id;

            $AddInfo = new stdClass();

            $AddInfo->DeliverLumpSum = 0;
            $AddInfo->DiscountValue = 0;
            $AddInfo->DiscountValue = 0;
            $AddInfo->CurrencyStr = "\ u00e2 \ u201a \ u00ac";
            $AddInfo->PaymentType = 'Barzahlung';
            $AddInfo->DeliverType = 'dine-in';
            $AddInfo->Comment = "Table: " . $order->table->name;

            $orderObj->AddInfo = $AddInfo;


            $ArticleList = [];

            foreach ($order->winOrderNotSendItems as $item) {
                $Article = new stdClass();
                $Article->Price = Helper::formatNumber($item->dish_price);
                $Article->ArticleSize = $item->size_id != null ? $item->productSize->name : '';
                $Article->ArticleNo = $item->product->dish_number == "" ? "G" . $item->product->id : "G" . $item->product->dish_number;
                $Article->Count = $item->count;
                $Article->Tax =  Helper::formatNumber($item->total_tax_exclusive);
                $Article->Comment = "Table: " . $order->table->name;

                $SubArticleList = [];

                foreach ($item->orderItemToppings as $topping) {

                    $SubArticle = new stdClass();
                    $SubArticle->Price =  Helper::formatNumber($topping->price);
                    $SubArticle->ArticleName = $topping->topping->name;
                    $SubArticle->ArticleNo = "G" . $topping->toping_id;
                    $SubArticle->Count = $topping->count;
                    $SubArticle->Tax = $topping->tax ?  Helper::formatNumber($topping->tax->total_amount) : '';

                    $SubArticle->SCENO_PO = $topping->positioning && $topping->positioning->scenarioPosition ? $topping->positioning->scenarioPosition : null;
                    $SubArticle->TOPPG_PO = $topping->positioning && $topping->positioning->toppingPosition ? $topping->positioning->toppingPosition : null;

                    $SubArticleList[] = $SubArticle;
                }

                $Article->SubArticleList = [
                    'SubArticle' => $SubArticleList
                ];

                $ArticleList[] = $Article;

                //mark as sent
                //$item->is_winorder = 'true';
                //$item->save();
            }

            $orderObj->ArticleList = $ArticleList;

            $StoreData = new stdClass();
            $StoreData->StoreId = $order->branch_id;
            $StoreData->StoreName = $order->branch->name;

            $orderObj->StoreData = $StoreData;

            $ServerData = new stdClass();
            $ServerData->Agent = "WinOrder V6.0.0.0";
            $ServerData->IpAddress = request()->ip();
            $ServerData->CreateDateTime = Carbon::now()->toDateTimeString();
            $ServerData->Referer = "WinOrder";

            $orderObj->ServerData = $ServerData;

            $Customer = new stdClass();
            $Customer->CustomerNo = "GM" . $order->customerDevice->id;

            $DeliveryAddress = new stdClass();
            $DeliveryAddress->PhoneNo = "0421/247780";
            $DeliveryAddress->Email = "info@pixelplanet.de";
            $DeliveryAddress->Title = "Herr";
            $DeliveryAddress->FirstName = "Erika";
            $DeliveryAddress->LastName = "Mustermann";
            $DeliveryAddress->HouseNo = "13";
            $DeliveryAddress->Street = "Hoyaer Str";
            $DeliveryAddress->DescriptionOfWay = "Optional description";
            $DeliveryAddress->Zip = "28205";
            $DeliveryAddress->City = "Bremen";
            $DeliveryAddress->State = "Bremen";
            $DeliveryAddress->Country = "Deutschland";
            $DeliveryAddress->Company = "PixelPlanet GmbH";
            $DeliveryAddress->PayPalEmail = "info@pixelplanet.de";
            $DeliveryAddress->Fax = "0421/2477824";

            $Customer->DeliveryAddress = $DeliveryAddress;

            $BillAddress = new stdClass();
            $BillAddress->Title = "";
            $BillAddress->FirstName = "";
            $BillAddress->LastName = "";
            $BillAddress->Company = "";
            $BillAddress->Street = "";
            $BillAddress->HouseNo = "";
            $BillAddress->Zip = "";
            $BillAddress->City = "";
            $BillAddress->Email = "";
            $BillAddress->PhoneNo = "";

            $Customer->BillAddress = $BillAddress;

            $orderObj->Customer = $Customer;

            $orderObj->CreateDateTime = Carbon::now()->toDateTimeString();

            $orderList[] = [ 'order' => $orderObj];
        }

        print_r(json_encode([
            'OrderList' => $orderList
        ]));

        exit;*/
    }

    public function orderList(Request $request)
    {
        // $permission_in_roles = Helper::checkFunctionPermission('order_view');
        // if (!$permission_in_roles) {
        //     return abort('403');
        // }
        $branch_id = $request->header('Branch');
        $status = $request->input('status') ? $request->input('status') : '';
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
        $qry = Order::join('tables', 'orders.table_orders_id', 'tables.id')
            ->where([['orders.branch_id', $branch_id], ['orders.payment_id', 'like', '%' . $searchValue . '%']])
            ->select('orders.*', 'tables.name as table_name');
        if ($status !== '') {
            $qry->where('orders.status', $status);
        }
        $order_list = $qry
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);
        // dd($order_list);
        return response()->json(['status' => true, 'data' => $order_list]);
    }

    public function updateOrderStatus(Request $request)
    {
        $order = Order::find($request->id);
        $order->status = $request->status;
        try {
            $order->save();
            return response()->json(['status' => true, 'message' => __('Order updated successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('Order update failed')]);
        }
    }

    public function cashierCompleteOrder(Request $request)
    {
        // dd($request->all());
        $order = Order::find($request->order_id);

        if (!$request->paymentMethod || !isset($request->paymentMethod)) {
            return response()->json(['status' => false]);
        }

        if ($order && $order->status == 'processing') {
            $payment = new OrderPayment();
            $payment->orders_id = $order->id;
            $payment->customer_id = $order->customerDevice->id;
            $payment->amount = $order->gross_total;
            $payment->payment_type = $request->paymentMethod;
            $payment->waiter_id = $request->cashier_id;
            $payment->save();

            $order->status = 'completed';
            $order->save();

            return response()->json(['status' => true, 'msg' => __('Order completed successfully')]);
        }

        return response()->json(['status' => false]);
    }

    public function getKitchenDetails(Request $request)
    {
        // Find the processing orders
        $branch_id = $request->header('Branch');
        $orders = Order::where('branch_id', $branch_id)
            ->whereIn('status', ['completed', 'processing'])
            ->with('orderItems', 'table')
            ->get();

        if (count($orders) <= 0) {
            return response()->json(['status' => false, 'message' => __('No orders found')]);
        }

        $allorderDetails = [];

        foreach ($orders as $key => $order) {

            // Get order items
            $groupedOrderItems = $order->orderItems()->where('status', 'processing')->with('orderItemToppings', 'orderItemTaxes', 'product')->get()->groupBy('badge_id');

            foreach ($groupedOrderItems as $badge_id => $orderItems) {

                // Structure the response data
                $orderDetails = [
                    'order_id' => $order->id,
                    'branch_id' => $order->branch_id,
                    'table_orders_id' => $order->table_orders_id,
                    'payment_type' => $order->payment_type,
                    'payment_id' => $order->payment_id,
                    'status' => $order->status,
                    'order_delivery_type' => $order->order_delivery_type,
                    'total_tax_inclusive' => $order->total_tax_inclusive,
                    'total_tax_exclusive' => $order->total_tax_exclusive,
                    'net_total_without_tax' => $order->net_total_without_tax,
                    'net_total' => $order->net_total,
                    'total_discount' => $order->total_discount,
                    'total_with_discount_price' => $order->total_with_discount_price,
                    'delivery_tax_inclusive' => $order->delivery_tax_inclusive,
                    'delivery_tax_exclusive' => $order->delivery_tax_exclusive,
                    'delivery_cost' => $order->delivery_cost,
                    'gross_total' => $order->gross_total,
                    'language' => $order->language,
                    'device_id' => $order->device_id,
                    'customer_device' => [],
                    'order_items' => [],
                    'created_at' => $order->created_at->format('d-m-Y H:i'),
                    'table_number' => $order->table->table_number,
                ];

                foreach ($orderItems as $item) {

                    $itemDetails = [
                        'item_id' => $item->id,
                        'dish_id' => $item->dish_id,
                        'size_id' => $item->size_id,
                        'count' => $item->count,
                        'dish_name' =>  $item->size_id != null ?  $item->productSize->product->name . ' (' . $item->productSize->name . ')' : $item->product->name,
                        'dish_price' => $item->dish_price,
                        'topping_price' => $item->topping_price,
                        'total' => $item->total,
                        'net_total' => $item->net_total,
                        'total_discount' => $item->total_discount,
                        'total_tax_inclusive' => $item->total_tax_inclusive,
                        'total_tax_exclusive' => $item->total_tax_exclusive,
                        'gross_without_tax_price' => $item->gross_without_tax_price,
                        'gross_total' => $item->gross_total,
                        'gross_total_with_discount' => $item->gross_total_with_discount,
                        'status' => $item->status,
                        'customer_device_id' => $item->customer_device_id,
                        'customer_device' => [
                            'device_id' => $item->customerDevice->device_id,
                            'customer_name' => $item->customerDevice->customer_name,
                            'user_type' => $item->customerDevice->user_type
                        ],
                        'toppings' => [],
                        'taxes' => [],
                        'badge_id' => $item->badge_id,
                    ];

                    // Get item toppings
                    foreach ($item->orderItemToppings as $topping) {
                        $toppingDetails = [
                            'topping_id' => $topping->id,
                            'topping_name' => $topping->topping->name,
                            'toping_id' => $topping->toping_id,
                            'count' => $topping->count,
                            'price' => $topping->price,
                            'total' => $topping->total,
                        ];

                        // Get topping taxes
                        $toppingTaxes = OrderItemTopingTax::where('order_item_toping_id', $topping->id)->get();
                        foreach ($toppingTaxes as $toppingTax) {
                            $toppingDetails['taxes'][] = [
                                'taxes_id' => $toppingTax->taxes_id,
                                'type' => $toppingTax->type,
                                'tax_type' => $toppingTax->tax_type,
                                'amount' => $toppingTax->amount,
                                'total_amount' => $toppingTax->total_amount,
                            ];
                        }

                        $itemDetails['toppings'][] = $toppingDetails;
                    }

                    // Get item taxes
                    foreach ($item->orderItemTaxes as $tax) {
                        $itemDetails['taxes'][] = [
                            'taxes_id' => $tax->taxes_id,
                            'type' => $tax->type,
                            'tax_type' => $tax->tax_type,
                            'amount' => $tax->amount,
                            'total_amount' => $tax->total_amount,
                        ];
                    }

                    $orderDetails['order_items'][] = $itemDetails;
                }

                $allorderDetails[] = $orderDetails;
            }
        }
        return response()->json(['status' => true, 'allorderDetails' => $allorderDetails]);
    }

    public function confirmOrderReady(Request $request)
    {
        if (count($request->order_items) <= 0) {
            return response()->json(['status' => false, 'message' => __('Sorry, No order item found')]);
        }
        try {
            foreach ($request->order_items as $key => $order_item) {
                $orderReadyItem = OrderItem::find($order_item['item_id']);
                $orderReadyItem->status = 'completed';
                $orderReadyItem->save();
            }
            return response()->json(['status' => true, 'message' => __('Order Ready Successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('Something went wrong')]);
        }
    }

    public function deleteOrderItem(Request $request)
    {
        if (!$request->selectedDish['item_id']) {
            return response()->json(['status' => false, 'message' => __('No item found')]);
        }
        try {
            $deleteOrderItem = OrderItem::find($request->selectedDish['item_id']);
            $allItemsCountInThisOrder = OrderItem::where('orders_id', $deleteOrderItem->orders_id)->count();
            if ($deleteOrderItem->delete()) {
                $arr = [
                    'status' => true,
                    'msg' =>  __('Dish deleted successfully'),
                ];
                if ($allItemsCountInThisOrder <= 1) {
                    $cancelOrder = Order::find($deleteOrderItem->orders_id);
                    $cancelOrder->status = 'cancelled';
                    $cancelOrder->save();
                }
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' =>  __('Dish not deleted'),
                ];
                return response()->json($arr);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('Something went wrong')]);
        }
    }
}
