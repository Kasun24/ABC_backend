<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $guarded = [];
    protected static $logAttributes = ['*'];

    
    /**
     * Get the Prices for the Product.
     */
    public function prices()
    {
        return $this->hasMany('App\Models\ProductPrice','products_id','id');
    }
}
