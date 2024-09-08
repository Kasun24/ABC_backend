<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Query;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    /**
     * Return queries List
     *
     * @param  Request  $request
     * @return string
     */
    public function queryList(Request $request)
    {

        $permission_in_roles = Helper::checkFunctionPermission('query_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $branch_id = $request->header('Branch');
        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';

        $queries = Query::where([['branch_id', $branch_id], ['name', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        return response()->json(['status' => true, 'data' => $queries]);
    }

    public function createquery(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('query_add');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $query = new Query();
        $query->customer_id = $request->customer_id;
        $query->subject = $request->subject;
        $query->message = $request->message;
        $query->branch_id = $request->header('Branch');
        $query->status = 'pending'; // Default status
        $query->user_id = '';
        try {
            $query->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-query_add_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('lang.t-query_add_ailed')]);
        }
    }


    /*
    * Return one query data
     */
    public function getQuery(Request $request, $query_id)
    {
        $permission_in_roles = Helper::checkFunctionPermission('query_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $query = Query::find($query_id);
        if (!$query) {
            return abort('404');
        } else {
            $arr = [
                'status' => true,
                'data' => $query,
            ];
            return response()->json($arr);
        }
    }

    public function updateQuery(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('query_update');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $query = Query::find($request->id);
        if (!$query) {
            return abort('404');
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'status' => 'required|in:pending,in_progress,resolved',
            'response' => 'nullable|string',
        ]);

        $query->subject = $request->subject;
        $query->message = $request->message;
        $query->status = $request->status;
        $query->response = $request->response;
        $query->user_id = $request->user_id; // The logged-in user updating the query

        try {
            $query->save();
            return response()->json(['status' => true, 'message' => __('lang.t-query_updated_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-query_update_failed')]);
        }
    }

    /**
     * Delete query
     *
     * @param  Request  $request
     * @return Response
     */
    public function deleteQuery(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('query_delete');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $query = Query::find($request->id);
        if (!$query) {
            return response()->json(['status' => false, 'message' => __('lang.t-query_delete_failed')]);
        } else {
            if ($query->delete()) {
                $arr = [
                    'status' => true,
                    'msg' =>  __('lang.t-query_delete_successfully')
                ];
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' =>  __('lang.t-query_delete_failed')
                ];
                return response()->json($arr);
            }
        }
    }
}
