<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = ['product_id', 'name', 'value','section','order_position'];

    public function product() {
        return $this->belongsTo(Product::class);
    }
}
