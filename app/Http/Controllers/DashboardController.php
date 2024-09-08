<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use App\Models\Order;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{

     // Define the constant array for components
     private const COMPONENTS = [
        1 => 'Table Management',
        2 => 'Orders',
        3 => 'POS',
        4 => 'Kitchen',
        5 => 'Today Order Summary ',
        6 => 'Active Orders ',
        7 => 'Monthly Order Summary ',
        8 => 'Active Tables ',
    ];

    public function getComponents()
{
    return response()->json(['status' => true, 'data' => self::COMPONENTS]);
}

public function getTodayOrderCount(Request $request)
    {
    
    $today = Carbon::today();

    $todayOrderCount = Order::whereDate('created_at', $today)
    ->count();
   
    return response()->json(['status' => true, 'today_order_count' => $todayOrderCount]);
    }



public function getCompletedOrders(Request $request)
{
    
    // $completedOrders = Order::where('status', 'completed')->get();

    // return response()->json([
    //     'status' => true,
    //     'orders' => $completedOrders
    // ]);
    
        $branch_id = $request->header('Branch');
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
    
        $order_list = Order::join('tables', 'orders.table_orders_id', 'tables.id')
            ->where([
                ['orders.branch_id', $branch_id],
                ['orders.status', 'processing'], 
                ['orders.payment_id', 'like', '%' . $searchValue . '%']
            ])
            ->select('orders.*', 'tables.table_number as table_number')
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);
    
        return response()->json([
            'status' => true,
            'data' => $order_list
        ]);
}
    

public function getMonthlyOrderCount(Request $request)
{
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();

    $monthlyOrderCount = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])
        ->count();

    return response()->json(['status' => true, 'monthly_order_count' => $monthlyOrderCount]);
}


    /**
     * Return Users List
     *
     * @param  Request  $request
     * @return string
     */
    public function dashboardList(Request $request)
    {

        /*$permission_in_roles = Helper::checkFunctionPermission('customerdevices_view');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';

        $dashboard = Dashboard::where([['name', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        return response()->json(['status' => true, 'data' => $dashboard]);
    }

    public function dashboardCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'components' => 'required|array',
        ]);

        // Validate component IDs
        $components = array_filter($request->components, function ($componentId) {
            return array_key_exists($componentId, self::COMPONENTS);
        });

        if (count($components) !== count($request->components)) {
            return response()->json(['status' => false, 'message' => 'Invalid component IDs']);
        }

        $dashboard = new Dashboard();
        $dashboard->name = $request->name;
        $dashboard->components = json_encode($components);

        try {
            $dashboard->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-dashboard_created_successful')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-dashboard_created_failed'), $e]);
        }
    }

    public function dashboardUpdate(Request $request)
    {
        /*$permission_in_roles = Helper::checkFunctionPermission('customerdevices_update');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $dashboard = Dashboard::where("id", $request->id)->first();
        if (!$dashboard) {
            return abort('404');
        }

        $request->validate([
            // 'id' => 'required', 'unique:dashboard,id,' . $request->id,
            'name' => 'required'
        ]);

        // Validate component IDs
        $components = array_filter($request->components, function ($componentId) {
            return array_key_exists($componentId, self::COMPONENTS);
        });

        if (count($components) !== count($request->components)) {
            return response()->json(['status' => false, 'message' => 'Invalid component IDs']);
        }

        $dashboard->name = $request->name;
        $dashboard->components = json_encode($components);

        try {
            $dashboard->save();
            return response()->json(['status' => true, 'message' => __('lang.t-dashboard_updated_successful')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-dashboard_update_failed'), 'error' => $e->getMessage()]);
        }
    }

    public function dashboardDelete(Request $request)
    {

        $dashboard = Dashboard::find($request->id);
        if (!$dashboard) {
            return response()->json(['status' => false, 'message' => __('lang.t-delete_failed')]);
        } else {
            if ($dashboard->delete()) {
                $arr = [
                    'status' => true,
                    'msg' =>  __('lang.t-dashboard_delete_successful')
                ];
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' =>  __('lang.t-dashboard_delete_failed')
                ];
                return response()->json($arr);
            }
        }
    }
}
