<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;


class OrderItemToping extends Model
{
    use HasFactory;
    use LogsActivity;

    protected static $logAttributes = ['*'];

    public function topping()
    {
        return $this->belongsTo(ProductSizeScenarioToping::class, 'toping_id', 'id');
    }

    public function tax()
    {
        return $this->hasOne(ProductSizeScenarioToping::class, 'id', 'order_item_toping_id');
    }
   

}
