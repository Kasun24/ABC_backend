<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    // /**
    //  * Return View With Vue Component Name => Login
    //  *
    //  * @param  Request  $request
    //  * @return view
    //  */
    // public function users(Request $request)
    // {
    //     return view('logged', ['component' => 'users']);
    // }

    /**
     * Return Users List
     *
     * @param  Request  $request
     * @return string
     */
    public function usersList(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('user_view');
        if (!$permission_in_roles) {
            return response()->json(['status' => false, 'data' => __('lang.t-you_dont_have_permission_to_perform_this_action')], 403);
        }

        $length = $request->input('length') ? $request->input('length') : 10;
        $sortBy = $request->input('column') ? $request->input('column') : 'id';
        $orderBy = $request->input('dir') ? $request->input('dir') : 'asc';
        $searchValue = $request->input('search') ? $request->input('search') : '';
        $role_id = $request->input('role_id') ? $request->input('role_id') : 0;

        $query = User::query();

        if ($role_id != 0) {
            $query->where('role_id', $role_id);
        }

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%');
            });
        }

        $users = $query->orderBy($sortBy, $orderBy)
            ->paginate($length);

        return response()->json(['status' => true, 'data' => $users]);
    }



    public function createUser(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('user_add');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $request->validate([
            'name' => 'required',
            'string',
            'max:100',
            'email' => 'required',
            'string',
            'email',
            'max:100',
            'unique:users',
            'password' => 'required',
            'string',
            'min:8',
            'confirmed',
            'role_id' => 'required',
            'numeric',
            'branches' => 'array',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role_id = $request->role_id;
        $user->branches = json_encode($request->branches);


        if ($request->file('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $user->image = $imageName;
        } elseif ($request->input('image')) {
            $imageData = $request->input('image');

            if ($imageData) {
                if (strpos($imageData, ';') !== false) {
                    list($type, $imageData) = explode(';', $imageData);
                } else {
                    return response()->json(['status' => false, 'message' => __('lang.t-invalid_image_format')]);
                }

                if (strpos($imageData, ',') !== false) {
                    list(, $imageData) = explode(',', $imageData);
                    $imageData = base64_decode($imageData);
                    $imageName = time() . '.png';
                    Storage::disk('public')->put('images/user/' . $imageName, $imageData);
                    $user->image = $imageName;
                } else {
                    return response()->json(['status' => false, 'message' => __('lang.t-invalid_image_format')]);
                }
            }
        }

        $user->status = $request->status;

        try {
            $user->save();
            return response()->json(['status' => true, 'message' =>  __('lang.t-user_registered_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-user_register_failed'), $e]);
        }
    }


    /*
    * Return one user data
     */
    public function getUser(Request $request, $user_id)
    {
        $permission_in_roles = Helper::checkFunctionPermission('user_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $user = User::find($user_id);
        if (!$user) {
            return abort('404');
        } else {
            $arr = [
                'status' => true,
                'data' => $user,
            ];
            return response()->json($arr);
        }
    }

    public function updateUser(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('user_update');
        if (!$permission_in_roles) {
            return abort('403');
        }

        $user = User::find($request->id);
        if (!$user) {
            return abort('404');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users,email,' . $request->id,
            'role_id' => 'required',
            'numeric',
            'exists:roles,id',
            'branches' => 'array',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role_id = $request->role_id;
        $user->branches = json_encode($request->branches);

        // Check if image removal is requested
        if ($request->has('remove_image') && $request->remove_image) {
            // Delete the old image if exists
            if ($user->image) {
                Storage::disk('public')->delete('images/user/' . $user->image);
                $user->image = null; // Set image column to null in database
            }
        } elseif ($request->file('image')) {
            // Handle file upload
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);

            // Delete the old image if exists
            if ($user->image) {
                Storage::disk('public')->delete('images/user/' . $user->image);
            }

            // Set the new image
            $user->image = $imageName;
        } elseif ($request->input('image')) {
            // Handle base64 image
            $imageData = $request->input('image');

            if ($imageData) {
                if (strpos($imageData, ';') !== false) {
                    list($type, $imageData) = explode(';', $imageData);
                } else {
                    return response()->json(['status' => false, 'message' => __('lang.t-invalid_image_format')]);
                }

                if (strpos($imageData, ',') !== false) {
                    list(, $imageData) = explode(',', $imageData);
                    $imageData = base64_decode($imageData);
                    $imageName = time() . '.png';

                    // Store the new image
                    Storage::disk('public')->put('images/user/' . $imageName, $imageData);

                    // Delete the old image if exists
                    if ($user->image) {
                        Storage::disk('public')->delete('images/user/' . $user->image);
                    }

                    // Set the new image
                    $user->image = $imageName;
                } else {
                    return response()->json(['status' => false, 'message' => __('lang.t-invalid_image_format')]);
                }
            }
        }

        $user->status = $request->status;

        try {
            $user->save();
            return response()->json(['status' => true, 'message' => __('lang.t-user_updated_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => __('lang.t-user_update_failed'), 'error' => $e->getMessage()]);
        }
    }




    /**
     * Delete user
     *
     * @param  Request  $request
     * @return Response
     */
    public function deleteUser(Request $request)
    {
        $permission_in_roles = Helper::checkFunctionPermission('user_delete');
        if (!$permission_in_roles) {
            return abort('403');
        }
        $user = User::find($request->id);
        if (!$user) {
            return response()->json(['status' => false, 'message' => __('lang.t-user_delete_failed')]);
        } else {
            if ($user->delete()) {
                $arr = [
                    'status' => true,
                    'msg' => __('lang.t-user_deleted_successfully')
                ];
                return response()->json($arr);
            } else {
                $arr = [
                    'status' => false,
                    'msg' => __('lang.t-user_delete_failed')
                ];
                return response()->json($arr);
            }
        }
    }

    public function getSpecificUser(Request $request, $user_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return abort('404');
        } else {
            $arr = [
                'status' => true,
                'data' => $user,
            ];
            return response()->json($arr);
        }
    }

    public function getImage(Request $request)
    {

        if (Storage::disk('public')->exists('images/user/' . $request->name)) {
            $file = Storage::disk('public')->get('images/user/' . $request->name);
            return response($file, 200)
                ->header('Content-Type', 'image/png');
        } else {
            abort(404);
        }
    }
    public function updateUserBranch(Request $request)
    {
        $user = User::find($request->user_id);
        $branch_id = $request->branch_id;
        $user->branch_id = $branch_id;
        $user->save();
        return response()->json(['status' => true, 'message' => __('lang.t-user_updated_successfully')]);
    }
}
