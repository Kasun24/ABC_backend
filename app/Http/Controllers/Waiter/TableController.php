<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Order;
use App\Models\TableArea;
use App\Models\Table;

class TableController extends Controller
{
    public function tableList($branch_id, Request $request)
    {
        /*$permission_in_roles = Helper::checkFunctionPermission('table_area_view');
        if (!$permission_in_roles) {
            return abort('403');
        }*/

        $tableAreas = [];
        $tableArea = TableArea::where('branch_id', $branch_id)->get();
        $table = Table::where('branch_id', $branch_id)->get();

        foreach ($tableArea as $ta) {
            array_push($tableAreas, $ta);
        }

        foreach ($table as $t) {
            $processingOrder = Order::where([['table_orders_id',$t->id], ['status','processing']])->first();
            if($processingOrder && isset($processingOrder)){
                $t->orderInProgress = true;
            }
            array_push($tableAreas, $t);
        }

        return response()->json(['status' => true, 'data' => $tableAreas]);
    }

}
