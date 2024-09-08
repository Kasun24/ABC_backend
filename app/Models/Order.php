<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;


class Order extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['*'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'orders_id');
    }

    public function winOrderNotSendItems(){
        return $this->hasMany(OrderItem::class, 'orders_id')->where('is_winorder','false');
    }

    public function customerDevice()
    {
        return $this->belongsTo(CustomerDevice::class, 'device_id', 'device_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_orders_id', 'id');
    }
    

}
