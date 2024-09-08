<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Return a list of permissions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissionList(Request $request)
    {
        $permissions = Permission::all();

        return response()->json(['status' => true, 'data' => $permissions]);
    }
}