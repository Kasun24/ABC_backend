<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\ActivityLog;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request){
    
        $permission_in_roles = Helper::checkFunctionPermission('activity_log_view');
        if (!$permission_in_roles) {
            return abort('403');
        }

        if (Auth::user()->role_id == 1) {
            $activityLog = Activity::orderBy('created_at','desc')->get();
        } else {
            $activityLog = Activity::where('causer_id',Auth::user()->id)->orderBy('created_at','desc')->get();
        }

        foreach ($activityLog as $key => $value) {
            $modelName = basename(str_replace('\\', '/', $activityLog[$key]['subject_type']));
            $activityLog[$key]['causer'] = $value->causer;
            $activityLog[$key]['subject'] = $modelName;
            $activityLog[$key]['properties'] = $value->properties; //json_decode($value['properties']);
        }
    
        return response()->json(['status' => true,'data' => $activityLog]);

    }
}
