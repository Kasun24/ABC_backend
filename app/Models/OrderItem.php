<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;


class OrderItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['*'];

    public function orderItemToppings()
    {
        return $this->hasMany(OrderItemToping::class, 'order_items_id')->with('topping');
    }

    public function orderItemTaxes()
    {
        return $this->hasMany(OrderItemTax::class, 'order_items_id');
    }

    public function customerDevice()
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id', 'id');
    }

    public function product()
    {
            return $this->belongsTo(Product::class, 'dish_id', 'id');
         
    }

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class, 'size_id', 'id')->with('product');
        
    }
    

}
