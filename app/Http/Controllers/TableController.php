<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Table;
use App\Models\TableArea;
use Illuminate\Support\Facades\DB;

class TableController extends Controller
{
    /**
     * Return Restaurant Table List
     *
     * @param  Request  $request
     * @return string
     */
    public function tableList(Request $request)
    {
        $branch_id = $request->header('Branch');
        $permission_in_roles = Helper::checkFunctionPermission('table_area_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $tableAreas = [];
        $tableArea = TableArea::where('branch_id', $branch_id)->get();;
        $table = Table::where('branch_id', $branch_id)->get();

        foreach ($tableArea as $ta) {
            array_push($tableAreas, $ta);
        }

        foreach ($table as $t) {
            array_push($tableAreas, $t);
        }

        return response()->json(['status' => true, 'data' => $tableAreas]);
    }

    public function createTable(Request $request)
{
    // Check permissions
    $permission_in_roles = Helper::checkFunctionPermission('table_area_add');
    if (!$permission_in_roles) {
        return abort('403');
    }

    $branch_id = $request->header('Branch');
    $incomingData = $request->data; // Assume the incoming data is the array structure you provided.

        // Validate incoming data
        foreach ($incomingData as $value) {
            if (!$value['elementId'] || !$value['name'] || !$value['type'] || !isset($value['locationX']) || !isset($value['locationY']) || !$value['width'] || !$value['height']) {
                return response()->json(['status' => false, 'message' => __('Something went wrong'), 'value' => $value]);
            }
        }

    // Group incoming data by name and table_number for tables
    $groupedData = [];
    foreach ($incomingData as $item) {
        if ($item['type'] === 'table') {
            $key = $item['table_number'] . '-' . $item['type'];
        } else {
            $key = $item['name'] . '-' . $item['type'];
        }
        if (!isset($groupedData[$key])) {
            $groupedData[$key] = [];
        }
        $groupedData[$key][] = $item;
    }

    // Check for duplicates and prompt user to change one array if necessary
    foreach ($groupedData as $key => $data) {
        if (count($data) > 1) {
            $table_number = explode('-', $key)[0];
            return response()->json(['status' => false, 'message' => __('Duplicate table'), 'table_number' => $table_number]);
        }
    }

    // Wrap operations in a transaction
    DB::transaction(function () use ($branch_id, $incomingData) {
        // Get all existing records for the specific branch
        $existingTableAreaData = TableArea::where('branch_id', $branch_id)->get();
        $existingTableData = Table::where('branch_id', $branch_id)->get();

        // Create a map of existing records by name and type
        $existingTableAreaMap = $existingTableAreaData->keyBy(function ($item) {
            return $item['name'] . '-' . $item['type'];
        });

        $existingTableMap = $existingTableData->keyBy(function ($item) {
            return $item['table_number'] . '-' . $item['type'];
        });

        // Extract incoming keys for comparison
        $incomingTableAreaMap = [];
        $incomingTableMap = [];
        foreach ($incomingData as $item) {
            if ($item['type'] === "tableZone") {
                $key = $item['name'] . '-' . $item['type'];
                $incomingTableAreaMap[$key] = $item;
            }
            if ($item['type'] === "table") {
                $key = $item['table_number'] . '-' . $item['type'];
                $incomingTableMap[$key] = $item;
            }
        }

        // Determine which records to delete
        $toDeleteTableArea = array_diff(array_keys($existingTableAreaMap->toArray()), array_keys($incomingTableAreaMap));
        $toDeleteTable = array_diff(array_keys($existingTableMap->toArray()), array_keys($incomingTableMap));

        // Delete records for the specific branch
        foreach ($toDeleteTableArea as $key) {
            $existingTableAreaMap[$key]->delete();
        }
        foreach ($toDeleteTable as $key) {
            $existingTableMap[$key]->delete();
        }

        // Process incoming data
        foreach ($incomingData as $item) {
            if ($item['type'] === "tableZone") {
                $key = $item['name'] . '-' . $item['type'];
                if (isset($existingTableAreaMap[$key])) {
                    // Update existing item
                    $oldTableArea = TableArea::find($existingTableAreaMap[$key]->id);
                    $oldTableArea->branch_id = $branch_id;
                    $oldTableArea->elementId = $item['elementId'];
                    $oldTableArea->name = $item['name'];
                    $oldTableArea->type = $item['type'];
                    $oldTableArea->locationX = $item['locationX'];
                    $oldTableArea->locationY = $item['locationY'];
                    $oldTableArea->width = $item['width'];
                    $oldTableArea->height = $item['height'];
                    if (isset($item['classes'])) {
                        $oldTableArea->classes = json_encode($item['classes']);
                    }
                    if (isset($item['addedTables'])) {
                        $oldTableArea->addedTables = json_encode($item['addedTables']);
                    }
                    if (isset($item['color'])) {
                        $oldTableArea->color = $item['color'];
                    }
                    $oldTableArea->save();
                } else {
                    // Create new item
                    $newTableArea = new TableArea();
                    $newTableArea->branch_id = $branch_id;
                    $newTableArea->elementId = $item['elementId'];
                    $newTableArea->name = $item['name'];
                    $newTableArea->type = $item['type'];
                    $newTableArea->locationX = $item['locationX'];
                    $newTableArea->locationY = $item['locationY'];
                    $newTableArea->width = $item['width'];
                    $newTableArea->height = $item['height'];
                    if (isset($item['classes'])) {
                        $newTableArea->classes = json_encode($item['classes']);
                    }
                    if (isset($item['addedTables'])) {
                        $newTableArea->addedTables = json_encode($item['addedTables']);
                    }
                    if (isset($item['color'])) {
                        $newTableArea->color = $item['color'];
                    }
                    $newTableArea->save();
                }
            }
            if ($item['type'] === "table") {
                $key = $item['table_number'] . '-' . $item['type'];
                if (isset($existingTableMap[$key])) {
                    // Update existing item
                    $oldTable = Table::find($existingTableMap[$key]->id);
                    $oldTable->branch_id = $branch_id;
                    $oldTable->elementId = $item['elementId'];
                    $oldTable->table_number = $item['table_number'];
                    $oldTable->name = $item['name'];
                    $oldTable->type = $item['type'];
                    $oldTable->locationX = $item['locationX'];
                    $oldTable->locationY = $item['locationY'];
                    $oldTable->width = $item['width'];
                    $oldTable->height = $item['height'];
                    if (isset($item['relatedArea'])) {
                        $oldTable->relatedArea = $item['relatedArea'];
                    }
                    if (isset($item['classes'])) {
                        $oldTable->classes = json_encode($item['classes']);
                    }
                    if (isset($item['color'])) {
                        $oldTable->color = $item['color'];
                    }
                    $oldTable->save();
                } else {
                    // Create new item
                    $newTable = new Table();
                    $newTable->branch_id = $branch_id;
                    $newTable->elementId = $item['elementId'];
                    $newTable->table_number = $item['table_number'];
                    $newTable->name = $item['name'];
                    $newTable->type = $item['type'];
                    $newTable->locationX = $item['locationX'];
                    $newTable->locationY = $item['locationY'];
                    $newTable->width = $item['width'];
                    $newTable->height = $item['height'];
                    if (isset($item['relatedArea'])) {
                        $newTable->relatedArea = $item['relatedArea'];
                    }
                    if (isset($item['classes'])) {
                        $newTable->classes = json_encode($item['classes']);
                    }
                    if (isset($item['color'])) {
                        $newTable->color = $item['color'];
                    }
                    $newTable->save();
                }
            }
        }
    });

    return response()->json(['status' => true, 'message' => __('Table saved successfully')]);
}



    /*
    * Return one table area data
     */
    public function getTable(Request $request, $table_id)
    {
        $permission_in_roles = Helper::checkFunctionPermission('table_area_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $table = TableArea::find($table_id);
        if (!$table) {
            return abort('404');
        } else {
            $arr = [
                'status' => true,
                'data' => $table,
            ];
            return response()->json($arr);
        }
    }

    public function updateTable(Request $request)
{
    $permission_in_roles = Helper::checkFunctionPermission('table_area_update');
    if (!$permission_in_roles) {
        return abort('403');
    }

    $request->validate([
        'elementId' => 'required',
        'name' => 'required',
        'type' => 'required',
        'locationX' => 'required',
        'locationY' => 'required',
        'width' => 'required',
        'height' => 'required',
        'table_number' => 'required_if:type,table'
    ]);

    if ($request->type === "tableZone") {
        $statusTableArea = TableArea::find($request->id);
        if (!$statusTableArea) {
            // If it doesn't exist, create a new item
            $newTableArea = new TableArea();
            $newTableArea->elementId = $request->elementId;
            $newTableArea->name = $request->name;
            $newTableArea->type = $request->type;
            $newTableArea->locationX = $request->locationX;
            $newTableArea->locationY = $request->locationY;
            $newTableArea->width = $request->width;
            $newTableArea->height = $request->height;
            if (isset($request->classes)) {
                $newTableArea->classes = json_encode($request->classes);
            }
            if (isset($request->addedTables)) {
                $newTableArea->addedTables = json_encode($request->addedTables);
            }
            if (isset($request->color)) {
                $newTableArea->color = $request->color;
            }
            $newTableArea->save();
            return response()->json(['status' => true, 'message' => __('Table updated successfully')]);
        } else {
            // If it exists, update the item
            $oldTableArea = $statusTableArea;
            $oldTableArea->elementId = $request->elementId;
            $oldTableArea->name = $request->name;
            $oldTableArea->type = $request->type;
            $oldTableArea->locationX = $request->locationX;
            $oldTableArea->locationY = $request->locationY;
            $oldTableArea->width = $request->width;
            $oldTableArea->height = $request->height;
            if (isset($request->classes)) {
                $oldTableArea->classes = json_encode($request->classes);
            }
            if (isset($request->addedTables)) {
                $oldTableArea->addedTables = json_encode($request->addedTables);
            }
            if (isset($request->color)) {
                $oldTableArea->color = $request->color;
            }
            $oldTableArea->save();
            return response()->json(['status' => true, 'message' => __('Table updated successfully')]);
        }
    }

    if ($request->type === "table") {
        $statusTable = Table::find($request->id);
        if (!$statusTable) {
            // If it doesn't exist, create a new item
            $newTable = new Table();
            $newTable->elementId = $request->elementId;
            $newTable->table_number = $request->table_number;
            $newTable->name = $request->name;
            $newTable->type = $request->type;
            $newTable->locationX = $request->locationX;
            $newTable->locationY = $request->locationY;
            $newTable->width = $request->width;
            $newTable->height = $request->height;
            if (isset($request->relatedArea)) {
                $newTable->relatedArea = $request->relatedArea;
            }
            if (isset($request->classes)) {
                $newTable->classes = json_encode($request->classes);
            }
            if (isset($request->color)) {
                $newTable->color = $request->color;
            }
            $newTable->save();
            return response()->json(['status' => true, 'message' => __('Table updated successfully')]);
        } else {
            // If it exists, update the item
            $oldTable = $statusTable;
            $oldTable->elementId = $request->elementId;
            $oldTable->table_number = $request->table_number;
            $oldTable->name = $request->name;
            $oldTable->type = $request->type;
            $oldTable->locationX = $request->locationX;
            $oldTable->locationY = $request->locationY;
            $oldTable->width = $request->width;
            $oldTable->height = $request->height;
            if (isset($request->relatedArea)) {
                $oldTable->relatedArea = $request->relatedArea;
            }
            if (isset($request->classes)) {
                $oldTable->classes = json_encode($request->classes);
            }
            if (isset($request->color)) {
                $oldTable->color = $request->color;
            }
            $oldTable->save();
            return response()->json(['status' => true, 'message' => __('Table updated successfully')]);
        }
    }
}


    /**
     * Delete table
     *
     * @param  Request  $request
     * @return Response
     */
    public function deleteTable(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('table_area_delete');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $tableArea = TableArea::find($request->id);
        if (!$tableArea) {
            return response()->json(['status' => false, 'message' => __('Table delete failed')]);
        } else {
            if ($tableArea->type === 'tableZone' && $tableArea->addedTables !== []) {
                return response()->json(['status' => false, 'message' => __('Failed to delete table')]);
            }
            if ($tableArea->delete()) {
                $arr = [
                    'status' => true,
                    'msg' => __('Table deleted successfully')
                ];
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' => __('Table delete failed')
                ];
                return response()->json($arr);
            }
        }
    }

    /**
     * Return Restaurant Table List
     *
     * @param  Request  $request
     * @return string
     */
    public function fetchTableAndBranchDetails(Request $request, $table_number, $branch_id)
    {
        if(!$table_number || !isset($table_number) || !$branch_id || !isset($branch_id)){
            $arr = [
                'status' => false,
                'msg' => 'Table Not Found!'
            ];
        }

        $tableDetails = Table::where([['table_number',$table_number],['branch_id', $branch_id]])->first();
        $arr = [];
        if (!$tableDetails) {
            $arr = [
                'status' => false,
                'msg' => 'Table Not Found!'
            ];
        } else {
            $branchDetails = Branch::find($branch_id);
            $arr = [
                'status' => true,
                'table_details' => $tableDetails,
                'branch_details' => $branchDetails
            ];
        }

        return response()->json($arr);
    }

    public function getTables(Request $request)
    {

        $tables = Table::where('branch_id', 1)->get();
        return response()->json([
            'status' => true,
            'data' => $tables
        ]);
    }

    public function tableListByPOS(Request $request, $branch_id)
    {
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
