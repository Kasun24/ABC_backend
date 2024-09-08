<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\MenuCategory;
use App\Models\MenuCategorySenario;
use App\Models\Order;
use App\Models\OrderItemTax;
use App\Models\OrderItemTopingTax;
use App\Models\OrderTax;
use App\Models\OrderTaxDelivery;
use App\Models\Product;
use App\Models\ProductSizeScenarioToping;
use App\Models\Tax;
use App\Models\TopingScenario;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    /**
     * Return taxes List
     *
     * @param  Request  $request
     * @return string
     */
    public function taxesList(Request $request)
    {

        $permission_in_roles = Helper::checkFunctionPermission('taxes_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $branch_id = $request->header('Branch');
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';

        $taxes = Tax::where([['branch_id', $branch_id], ['title', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        return response()->json(['status' => true, 'data' => $taxes]);
    }

    public function createTax(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('taxes_add');
        if (!$permission_in_roles) {
            return abort('403');
        }
        $request->validate([
            'title' => 'required', 'string', 'max:100',
            'type' => 'required',
            'apply_as' => 'required'
        ]);

        if (Tax::where('title',$request->title)->count() > 0) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-this_tax_title_is_already_exists')]);
        }

        $taxes = new Tax();
        $taxes->branch_id = $request->header('Branch');
        $taxes->title = $request->title;
        $taxes->delivery = $request->delivery ? $request->delivery : 0;
        $taxes->pickup = $request->pickup ? $request->pickup : 0;
        $taxes->dine_in = $request->dine_in ? $request->dine_in : 0;
        $taxes->type = $request->type;
        $taxes->apply_as = $request->apply_as;
        $taxes->status = $request->status;
        try {
            $taxes->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-tax_add_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-tax_add_failed')]);
        }
    }


    /*
    * Return one taxes data
     */
    public function getTax(Request $request, $tax_id)
    {
        $permission_in_roles = Helper::checkFunctionPermission('taxes_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $taxes = Tax::find($tax_id);
        if (!$taxes) {
            return abort('404');
        } else {
            $arr = [
                'status' => true,
                'data' => $taxes,
            ];
            return response()->json($arr);
        }
    }

    public function updateTax(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('taxes_update');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $status = Tax::find($request->id);
        if (!$status) {
            return abort('404');
        }

        $request->validate([
            'title' => 'required', 'string', 'max:100',
            'type' => 'required',
            'apply_as' => 'required'
        ]);

        if (Tax::where([['title',$request->title],['id','!=',$request->id]])->count() > 0) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-this_tax_title_is_already_exists')]);
        }

        $taxes = $status;
        $taxes->title = $request->title;
        $taxes->delivery = $request->delivery ? $request->delivery : 0;
        $taxes->pickup = $request->pickup ? $request->pickup : 0;
        $taxes->dine_in = $request->dine_in ? $request->dine_in : 0;
        $taxes->type = $request->type;
        $taxes->apply_as = $request->apply_as;
        $taxes->status = $request->status;

        try {
            $taxes->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-tax_updated_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-tax_update_failed')]);
        }
    }

    /**
     * Delete tax
     *
     * @param  Request  $request
     * @return Response
     */
    public function deleteTax(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('taxes_delete');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $tax = Tax::find($request->id);
        if (!$tax) {
            return response()->json(['status' => false, 'message' => __('lang.t-tax_delete_failed')]);
        } else {
            $massagesArry = [];
            if (TopingScenario::where('tax', $tax->id)->exists()) {
                array_push($massagesArry,'Toping scenario');
            }
            if (MenuCategory::where('tax', $tax->id)->exists()) {
                array_push($massagesArry,'Menucategory');
            }
            if (Product::where('tax', $tax->id)->exists()) {
                array_push($massagesArry,'Product');
            }
            if (MenuCategorySenario::where('topping_tax', $tax->id)->exists()) {
                array_push($massagesArry,'MenuCategory Senario');
            }
            if (ProductSizeScenarioToping::where('tax', $tax->id)->exists()) {
                array_push($massagesArry,'Product size scenario toping');
            }
            if (Order::where('transaction_fee_tax_details', $tax->id)->exists()) {
                array_push($massagesArry,'Orders');
            }
            if (OrderItemTax::where('taxes_id', $tax->id)->exists()) {
                array_push($massagesArry,'Order item tax');
            }
            if (OrderItemTopingTax::where('taxes_id', $tax->id)->exists()) {
                array_push($massagesArry,'Order item toping tax');
            }
            if (OrderTax::where('taxes_id', $tax->id)->exists()) {
                array_push($massagesArry,'Order tax');
            }
            if (OrderTaxDelivery::where('taxes_id', $tax->id)->exists()) {
                array_push($massagesArry,'Order tax delivery');
            }
            if (count($massagesArry) > 0) {
                $message = __('lang.t-unable_to_delete_selected_tax_because_it_is_in_use_various_locations').implode(', ', $massagesArry).'. ';
                $arr = [
                    'status' => false,
                    'msg' =>  $message
                ];
                return response()->json($arr);
            } else {
                if ($tax->delete()) {
                    $arr = [
                        'status' => true,
                        'msg' =>  __('lang.t-tax_delete_successfully')
                    ];
                    return response()->json($arr);
                } else {
                    $arr = [
                        'status' => false,
                        'msg' =>  __('lang.t-tax_delete_failed')
                    ];
                    return response()->json($arr);
                }
            }
        }
    }
}
