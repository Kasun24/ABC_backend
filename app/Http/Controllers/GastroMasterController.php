<?php

namespace App\Http\Controllers;

use App\Helpers\GastroMasterApiHelper;
use Illuminate\Http\Request;

class GastroMasterController extends Controller
{

    public function syncData(Request $request){
        GastroMasterApiHelper::syncData(1);
    }
    
}
