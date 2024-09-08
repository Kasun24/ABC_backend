<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class TableArea extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'classes',
        'location',
        'size',
        'relatedArea',
        'addedTables',
        'color'
    ];
    protected static $logAttributes = ['*'];
}
