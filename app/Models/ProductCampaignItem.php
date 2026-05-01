<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCampaignItem extends BaseModel
{
    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'cost_price',
        'sale_price',
        'stock_quantity',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(ProductCampaign::class, 'campaign_id');
    }

    public function orders()
    {
        return $this->hasMany(ProductCampaignOrder::class, 'item_id');
    }
}
