<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Customer;
use App\Models\CustomerDevice;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Return Users List
     *
     * @param  Request  $request
     * @return string
     */
    public function customerDevicesList(Request $request)
    {

        /*$permission_in_roles = Helper::checkFunctionPermission('customerdevices_view');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';

        $users = CustomerDevice::where([['customer_name', 'like', '%' . $searchValue . '%']])
            ->orWhere([['user_type', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        return response()->json(['status' => true, 'data' => $users]);
    }

    public function customerDevicesCreate(Request $request)
    {
        /*$permission_in_roles = Helper::checkFunctionPermission('customerdevices_add');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $request->validate([
            'device_id' => 'required',
            'unique:customer_devices',
            'user_type' => 'required'
        ]);

        $customerDevice = new CustomerDevice();
        $customerDevice->device_id = $request->device_id;
        $customerDevice->customer_name = $request->customer_name;
        $customerDevice->user_type = $request->user_type;

        try {
            $customerDevice->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-customer_device_registered_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-customer_device_register_failed'), $e]);
        }
    }

    public function customerDevicesUpdate(Request $request)
    {
        /*$permission_in_roles = Helper::checkFunctionPermission('customerdevices_update');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $customerDevice = CustomerDevice::where("device_id", $request->device_id)->first();
        if (!$customerDevice) {
            return abort('404');
        }

        $request->validate([
            'device_id' => 'required',
            'unique:customer_devices,device_id,' . $request->id,
            'user_type' => 'required'
        ]);

        $customerDevice->customer_name = $request->customer_name;
        $customerDevice->user_type = $request->user_type;

        try {
            $customerDevice->save();
            return response()->json(['status' => true, 'message' => __('lang.t-customer_device_updated_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-customer_device_update_failed'), 'error' => $e->getMessage()]);
        }
    }

    public function customerList(Request $request)
    {
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
        $customers = Customer::where(function ($query) use ($searchValue) {
            $query->where('first_name', 'like', '%' . $searchValue . '%')
                ->orWhere('last_name', 'like', '%' . $searchValue . '%');
        })
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);
        return response()->json(['status' => true, 'data' => $customers]);
    }

    public function allCustomerList(Request $request)
    {
        $customers = Customer::all();
        return response()->json(['status' => true, 'data' => $customers]);
    }

    public function customerCreate(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'mobile_number' => 'required',
            'unique:customers'
        ]);
        $customer = new Customer();
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->mobile_number = $request->mobile_number;
        $customer->email = $request->email;
        try {
            $customer->save();
            return response()->json(['status' => true, 'message' => __('lang.t-customer_added_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-customer_add_failed'), 'error' => $e->getMessage()]);
        }
    }
    public function customerUpdate(Request $request)
    {
        $customer = Customer::where("id", $request->id)->first();
        if (!$customer) {
            return abort('404');
        }
        $request->validate([
            'first_name' => 'required',
            'mobile_number' => 'required',
            'unique:customers,contact_number,' . $request->id,
        ]);
        $customer->first_name = $request->first_name;
        $customer->last_name = $request->last_name;
        $customer->mobile_number = $request->mobile_number;
        $customer->email = $request->email;
        try {
            $customer->save();
            return response()->json(['status' => true, 'message' => __('lang.t-customer_updated_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-customer_update_failed'), 'error' => $e->getMessage()]);
        }
    }
    public function customerDelete(Request $request)
    {
        $customer = Customer::where("id", $request->id)->first();
        if (!$customer) {
            return abort('404');
        }
        try {
            $customer->delete();
            return response()->json(['status' => true, 'message' => __('lang.t-customer_deleted_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-customer_delete_failed'), 'error' => $e->getMessage()]);
        }
    }
}
