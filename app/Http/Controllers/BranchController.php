<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Branch;
use App\Models\Discount;
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
            return response()->json(['status' => false, 'message' =>  __('This branch name is already exists')]);
        }

        $branches = new Branch();
        $branches->name = $request->name;
        $branches->bill_split = $request->bill_split;
        if ($request->gm_id) {
            $branches->gm_id = $request->gm_id;
        }

        try {
            $branches->save();
            return response()->json(['status' => true, 'message' =>  __('Branch added successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('Branch add failed'), 'error' => $e->getMessage()]);
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
            return response()->json(['status' => false, 'message' =>  __('This branch name is already exists')]);
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
            return response()->json(['status' => true, 'message' =>  __('Branch updated successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('Branch update failed'), 'error' => $e->getMessage()]);
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
            return response()->json(['status' => false, 'message' => __('Branch delete failed')]);
        } else {
            $massagesArr = [];
            if (User::whereJsonContains('branches', $branch->id)->exists()) {
                array_push($massagesArr,'users');
            }
            if (count($massagesArr) > 0) {
                $message = __('Unable to delete branch. Because it is used in ') . implode(", ", $massagesArr);
                $arr = [
                    'status' => false,
                    'msg' =>  $message
                ];
                return response()->json($arr);
            } else {
                if ($branch->delete()) {
                    $arr = [
                        'status' => true,
                        'msg' =>  __('Branch deleted successfully')
                    ];
                    return response()->json($arr);
                } else {
                    $arr = [
                        'status' => false,
                        'msg' =>  __('Branch delete failed')
                    ];
                    return response()->json($arr);
                }
            }
        }
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
            return response()->json(['status' => false, 'message' => __('Branch delete failed')]);
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

            $message = __('You are not allowed to delete branch') . implode(', ', $massagesArry) . __('This action cannot be undone');

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
