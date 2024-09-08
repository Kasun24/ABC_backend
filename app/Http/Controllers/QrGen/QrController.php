<?php

namespace App\Http\Controllers\QrGen;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;


class QrController extends Controller
{

    public function generateTableQRCode(Request $request)
    {
        require_once(app_path().'/Http/Controllers/QrGen/phpqrcode/qrlib.php');
        // Generate QR code
        \QRcode::png($request->url, false, 0, 5); 
        exit;

    }



}