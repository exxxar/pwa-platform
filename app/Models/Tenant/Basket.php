<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Basket extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'collection_id',
        'count',
        'comment',
        'tenant_user_id',
        'tenant_id',
        'ordered_at',
        //   'container_id',
        'params',
        'table_id',
        'table_approved_at',
        'tenant_partner_id',
    ];

    protected $casts = [
        'params' => 'array',
        'ordered_at' => 'datetime',
        'table_approved_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function tenantUser()
    {
        return $this->belongsTo(TenantUser::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /*  public function productCollection()
      {
          return $this->belongsTo(ProductCollection::class);
      }*/

    /* public function table()
     {
         return $this->belongsTo(Table::class);
     }*/

    /* public function partner()
     {
         return $this->belongsTo(Partner::class, 'tenant_partner_id');
     }*/

    /* |-------------------------------------------------------------------------- | Anonymous box |-------------------------------------------------------------------------- */
    public function isAnonymousBox(): bool
    {
        $params = is_array($this->params) ? $this->params : (json_decode($this->params, true) ?? []);
        return ($params['type'] ?? null) === 'anonymous_box';
    }

    public function getAnonymousBoxAmount(): float
    {
        $params = is_array($this->params) ? $this->params : (json_decode($this->params, true) ?? []);
        return (float) ($params['amount'] ?? 0);
    }

    public function getAnonymousBoxPartnerId()
    {
        $params = is_array($this->params) ? $this->params : (json_decode($this->params, true) ?? []);
        return $params['partner_id'] ?? null;
    }
}
