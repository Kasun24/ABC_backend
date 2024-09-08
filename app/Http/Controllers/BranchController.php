<?php

namespace App\Http\Controllers;

use App\Helpers\GastroMasterApiHelper;
use App\Helpers\Helper;
use App\Models\Branch;
use App\Models\Discount;
use App\Models\GeneralSetting;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\Query;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Table;
use App\Models\TableArea;
use App\Models\Tax;
use App\Models\TopingScenario;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    // /**
    //  * Return View With Vue Component Name => Login
    //  *
    //  * @param  Request  $request
    //  * @return view
    //  */
    // public function branches(Request $request)
    // {
    //     return view('logged', ['component' => 'branches']);
    // }

    /**
     * Return branches List
     *
     * @param  Request  $request
     * @return string
     */
    public function branchList(Request $request)
    {

        $permission_in_roles = Helper::checkFunctionPermission('branch_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';

        $branches = Branch::where([['name', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        return response()->json(['status' => true, 'data' => $branches]);
    }

    /**
     * Return all branches list
     *
     * @param  Request  $request
     * @return string
     */
    public function branchAllList(Request $request)
    {
        $allBranches = Branch::all();

        return response()->json(['status' => true, 'data' => $allBranches]);
    }

    public function createBranches(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('branch_add');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $request->validate([
            'name' => 'required', 'string', 'max:100',
        ]);

        if (Branch::where('name',$request->name)->count() > 0) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-this_branch_name_is_already_exists')]);
        }

        $branches = new Branch();
        $branches->name = $request->name;
        $branches->bill_split = $request->bill_split;
        if ($request->gm_id) {
            $branches->gm_id = $request->gm_id;
        }

        try {
            $branches->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-branch_add_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-branch_add_failed')]);
        }
    }


    /*
    * Return one branch data
     */
    public function getBranch(Request $request, $branch_id)
    {
        /*$permission_in_roles = Helper::checkFunctionPermission('branch_view');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $branch = Branch::find($branch_id);
        if (!$branch) {
            return abort('404');
        } else {
            $arr = [
                'status' => true,
                'data' => $branch,
            ];
            return response()->json($arr);
        }
    }

    public function updateBranch(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('branch_update');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $status = Branch::find($request->id);
        if (!$status) {
            return abort('404');
        }

        $request->validate([
            'name' => 'required', 'string', 'max:100'
        ]);

        if (Branch::where([['name',$request->name],['id','!=',$request->id]])->count() > 0) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-this_branch_name_is_already_exists')]);
        }

        $branches = $status;
        $branches->name = $request->name;
        $branches->bill_split = $request->bill_split;

        if ($request->gm_id) {
            $branches->gm_id = $request->gm_id;
        } else {
            $branches->gm_id = null;
        }

        try {
            $branches->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-branch_updated_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-branch_updated_failed')]);
        }
    }

    /**
     * Delete branch
     *
     * @param  Request  $request
     * @return Response
     */
    public function deleteBranch(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('branch_delete');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $branch = Branch::find($request->id);
        if (!$branch) {
            return response()->json(['status' => false, 'message' => __('lang.t-branch_delete_failed')]);
        } else {
            $massagesArr = [];
            if (User::whereJsonContains('branches', $branch->id)->exists()) {
                array_push($massagesArr,'users');
            }
            if (count($massagesArr) > 0) {
                $message = __('lang.t-unable_to_delete_selected_branch_because_it_is_in_use_users');
                $arr = [
                    'status' => false,
                    'msg' =>  $message
                ];
                return response()->json($arr);
            } else {
                if ($branch->delete()) {
                    $arr = [
                        'status' => true,
                        'msg' =>  __('lang.t-branch_delete_successfully')
                    ];
                    return response()->json($arr);
                } else {
                    $arr = [
                        'status' => false,
                        'msg' =>  __('lang.t-branch_delete_failed')
                    ];
                    return response()->json($arr);
                }
            }
        }
    }

    public function getApiBranches(Request $request)
    {
        $settings = GeneralSetting::first();
        if ($settings && $settings->api_endpoint && $settings->sec_token) {
            $branches = GastroMasterApiHelper::getBranches();
            if ($branches) {
                return response()->json(['status' => true, 'data' => $branches]);
            }
        }

        return response()->json(['status' => false, 'data' => null]);
    }

    public function syncApiBranch(Request $request)
    {

        GastroMasterApiHelper::syncData($request->branch_id);
        return response()->json(['status' => true, "msg" => __('lang.t-sync_success')]);
    }

    public function allbranchs(Request $request)
    {
        $branches = Branch::all();
        return response()->json(['status' => true, 'data' => $branches]);
    }

    /**
     * Branch retated table list
     *
     * @param  Request  $request
     * @return Response
     */
    public function branchRetatedList(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('branch_delete');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $branch = Branch::find($request->id);
        if (!$branch) {
            return response()->json(['status' => false, 'message' => __('lang.t-branch_delete_failed')]);
        } else {
            $massagesArry = [];
            if (User::whereJsonContains('branches', $branch->id)->exists()) {
                array_push($massagesArry,'users');
            }
            if (Discount::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'discounts');
            }
            if (Query::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'queries');
            }
            if (Tax::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'taxes');
            }
            if (TableArea::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'table areas');
            }
            if (Table::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'tables');
            }
            if (TopingScenario::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'toping scenarios');
            }
            if (MenuCategory::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'menu categories');
            }
            if (Product::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'product');
            }
            if (ProductPrice::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'product prices');
            }
            if (Order::where('branch_id', $branch->id)->exists()) {
                array_push($massagesArry,'orders');

            }

            $message = __('lang.t-you_are_trying_to_delete_a_branch_that_has') . implode(', ', $massagesArry) . __('lang.t-this_action_cannot_be_undone_and_you_wont_be_able_to_access_this_branch_related_data_anymore');

            $arr = [
                'status' => true,
                'data' => $massagesArry,
                "message" => $message,
                "isRetated" => count($massagesArry) > 0 ? true : false
            ];
            return response()->json($arr);
        }
    }
}
