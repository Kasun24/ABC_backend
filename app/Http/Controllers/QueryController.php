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

    //  here send email related from id = customer_id from customers table and name from users table user_id = id 
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

        $queries = Query::select('queries.*', 'customers.email as customer_email', 'users.name as user_name')
            ->join('customers', 'queries.customer_id', '=', 'customers.id')
            ->join('users', 'queries.user_id', '=', 'users.id')
            ->where([['queries.branch_id', $branch_id], ['queries.subject', 'like', '%' . $searchValue . '%']])
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
        $query->branch_id = $request->header('Branch');
        $query->customer_id = $request->customer_id;
        $query->subject = $request->subject;
        $query->message = $request->message;
        $query->status = 'pending'; // Default status
        $query->user_id = $request->user_id;
        try {
            $query->save();
            return response()->json(['status' => true, 'message' =>  __('Query added successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('Query add failed' . $e->getMessage())]);
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
            'status' => 'required|in:pending,resolved',
            'response' => 'nullable|string',
        ]);

        $query->subject = $request->subject;
        $query->message = $request->message;
        $query->status = $request->status;
        $query->response = $request->response;
        $query->user_id = $request->user_id; // The logged-in user updating the query

        try {
            $query->save();
            return response()->json(['status' => true, 'message' => __('Query updated successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('Query update failed' . $e->getMessage())]);
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
            return response()->json(['status' => false, 'message' => __('Query delete failed')]);
        } else {
            if ($query->delete()) {
                $arr = [
                    'status' => true,
                    'msg' =>  __('Query deleted successfully')
                ];
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' =>  __('Query delete failed')
                ];
                return response()->json($arr);
            }
        }
    }
}
