<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class Alagic extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*'];
}
