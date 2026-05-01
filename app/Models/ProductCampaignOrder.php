<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCampaignOrder extends BaseModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'item_id',
        'member_id',
        'external_name',
        'external_contact',
        'quantity',
        'total_amount',
        'payment_status',
        'delivery_status',
        'payment_method',
        'notes',
        'registered_by',
        'paid_at',
        'delivered_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(ProductCampaign::class, 'campaign_id');
    }

    public function item()
    {
        return $this->belongsTo(ProductCampaignItem::class, 'item_id');
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function registrar()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
