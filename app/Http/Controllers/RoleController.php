<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\PermissionsInRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Return roles List
     *
     * @param  Request  $request
     * @return string
     */
    public function rolesList(Request $request)
    {


        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
        $dashboard_id = $request->input('dashboard_id') ? $request->input('dashboard_id') : 0;

        $query = User::query();

        if ($dashboard_id != 0) {
            $query->where('dashboard_id', $dashboard_id);
        }

        $roles = Role::where([['role', 'like', '%' . $searchValue . '%']])
            ->orderBy($sortBy, $orderBy)
            ->paginate($length);

        foreach ($roles as $role) {
            //get role available users
            $role->users = User::where('role', $role->role)->get();
        }

        return response()->json(['status' => true, 'data' => $roles]);
    }

    public function createRole(Request $request)
    {
        $request->validate([
            'role' => 'required','string','max:100',
        ]);

        if (Role::where('role',$request->role)->count() > 0) {
            return response()->json(['status' => false, 'message' =>  __('This role is already exists')]);
        }

        $roles = new Role();
        $roles->role = $request->role;
        $roles->dashboard_id = $request->dashboard_id;
        $roles->status = $request->status;

        try {
            $roles->save();

            //store permissions
            foreach ($request->permissions as $permission) {
                $PermissionsInRole = new PermissionsInRole();
                $PermissionsInRole->role_id = $roles->id;
                $PermissionsInRole->permission_id = $permission;
                $PermissionsInRole->save();
            }

            return response()->json(['status' => true, 'message' =>  __('Role added successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' =>  __('Role addition failed')]);
        }
    }


    /*
    * Return one role data
     */
    public function getRole(Request $request, $role_id)
    {
        $role = Role::find($role_id);
        if (!$role) {
            return abort('404');
        }

        $permissions = PermissionsInRole::where('role_id', $role_id)->get()->pluck('permission_id')->toArray();

        return response()->json([
            'status' => true,
            'data' => [
                'role_id' => $role->id,
                'role' => $role->role,
                'status' => $role->status,
                'permissions' => $permissions,
                'dashboard_id' => $role->dashboard_id,
            ],
        ]);
    }

    public function updateRole(Request $request)
    {
        $role = Role::find($request->role_id);
        if (!$role) {
            return abort('404');
        }

        $request->validate([
            'role' => 'required','string','max:100'
        ]);

        if (Role::where([['role',$request->role],['id','!=',$role->id]])->count() > 0) {
            return response()->json(['status' => false, 'message' =>  __('This role is already exists')]);
        }

        $role->role = $request->role;
        $role->dashboard_id = $request->dashboard_id;
        $role->status = $request->status;

        try {
            $role->save();

            // Store permissions
            $ids = [];

            foreach ($request->permissions as $permission_id) {
                $PermissionsInRole = PermissionsInRole::where('permission_id', $permission_id)
                    ->where('role_id', $role->id)
                    ->first();

                if (!$PermissionsInRole) {
                    $PermissionsInRole = new PermissionsInRole();
                    $PermissionsInRole->role_id = $role->id;
                    $PermissionsInRole->permission_id = $permission_id;
                    $PermissionsInRole->save();
                }
                $ids[] = $PermissionsInRole->id;
            }

            // Delete permissions not available in the request
            PermissionsInRole::where('role_id', $role->id)
                ->whereNotIn('id', $ids)
                ->delete();

            return response()->json(['status' => true, 'message' => __('Role updated successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('Role update failed')]);
        }
    }


    /**
     * Delete role
     *
     * @param  Request  $request
     * @return Response
     */
    public function deleteRole(Request $request)
    {

        $role = Role::find($request->id);
        if (!$role) {
            return response()->json(['status' => false, 'message' => __('Role deletion failed')]);
        } else {
            if ($role->delete()) {
                $arr = [
                    'status' => true,
                    'msg' =>  __('Role deleted successfully')
                ];
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' =>  __('Role deletion failed')
                ];
                return response()->json($arr);
            }
        }
    }

    public function getRolePermission(Request $request, $role_id)
    {
        $permissions = PermissionsInRole::where('role_id', $role_id)->pluck('permission_id')->toArray();
        return response()->json(['status' => true, 'data' => $permissions]);
    }
}
