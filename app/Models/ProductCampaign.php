<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCampaign extends BaseModel
{
    protected $fillable = [
        'event_id',
        'title',
        'description',
        'category_id',
        'status',
        'start_at',
        'end_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(EcclesiasticalEvent::class, 'event_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function items()
    {
        return $this->hasMany(ProductCampaignItem::class, 'campaign_id');
    }

    public function orders()
    {
        return $this->hasMany(ProductCampaignOrder::class, 'campaign_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSummaryAttribute()
    {
        $totalOrders = $this->orders()->count();
        $totalPaid = $this->orders()->where('payment_status', 'paid')->sum('total_amount');
        
        // This is a simplified calculation. In a real scenario, we'd need to be careful with large datasets.
        $totalCost = $this->orders()->with('item')->get()->sum(function($order) {
            return $order->quantity * ($order->item->cost_price ?? 0);
        });

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => (float)$totalPaid,
            'total_cost' => (float)$totalCost,
            'profit' => (float)($totalPaid - $totalCost),
        ];
    }
}
