<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCampaign;
use App\Models\ProductCampaignItem;
use App\Models\ProductCampaignOrder;
use Illuminate\Support\Facades\DB;

class ProductCampaignController extends Controller
{
    public function index()
    {
        $campaigns = ProductCampaign::with(['event', 'category', 'items'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($campaign) {
                // Accessing the attribute to include it in JSON
                $campaign->summary_data = $campaign->summary;
                return $campaign;
            });

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_id' => 'nullable|exists:ecclesiastical_events,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:draft,active,closed',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.sale_price' => 'required|numeric|min:0',
            'items.*.stock_quantity' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $campaign = ProductCampaign::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'event_id' => $validated['event_id'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'status' => $validated['status'],
                'start_at' => $validated['start_at'] ?? null,
                'end_at' => $validated['end_at'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $itemData) {
                $campaign->items()->create($itemData);
            }

            return response()->json($campaign->load('items'), 201);
        });
    }

    public function show($id)
    {
        $campaign = ProductCampaign::with(['items', 'event', 'category'])->findOrFail($id);
        $campaign->summary_data = $campaign->summary;
        return response()->json($campaign);
    }

    public function update(Request $request, $id)
    {
        $campaign = ProductCampaign::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_id' => 'nullable|exists:ecclesiastical_events,id',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:draft,active,closed',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date',
        ]);

        $campaign->update($validated + ['updated_by' => $request->user()->id]);

        return response()->json($campaign);
    }

    public function destroy($id)
    {
        $campaign = ProductCampaign::findOrFail($id);
        $campaign->delete();
        return response()->json(['message' => 'Campanha removida com sucesso.']);
    }

    public function orders($id)
    {
        $orders = ProductCampaignOrder::where('campaign_id', $id)
            ->with(['item', 'member', 'registrar'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function storeOrder(Request $request, $id)
    {
        $campaign = ProductCampaign::findOrFail($id);

        $validated = $request->validate([
            'item_id' => 'required|exists:product_campaign_items,id',
            'member_id' => 'nullable|exists:members,id',
            'external_name' => 'nullable|required_without:member_id|string|max:255',
            'external_contact' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'payment_status' => 'required|in:pending,paid,cancelled',
            'delivery_status' => 'required|in:pending,delivered',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $item = ProductCampaignItem::findOrFail($validated['item_id']);
        
        $order = ProductCampaignOrder::create($validated + [
            'campaign_id' => $id,
            'total_amount' => $item->sale_price * $validated['quantity'],
            'registered_by' => $request->user()->id,
            'paid_at' => $validated['payment_status'] === 'paid' ? now() : null,
            'delivered_at' => $validated['delivery_status'] === 'delivered' ? now() : null,
        ]);

        return response()->json($order->load('item', 'member'), 201);
    }

    public function updateOrder(Request $request, $campaignId, $orderId)
    {
        $order = ProductCampaignOrder::where('campaign_id', $campaignId)->findOrFail($orderId);

        $validated = $request->validate([
            'payment_status' => 'sometimes|required|in:pending,paid,cancelled',
            'delivery_status' => 'sometimes|required|in:pending,delivered',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['payment_status']) && $validated['payment_status'] === 'paid' && $order->payment_status !== 'paid') {
            $order->paid_at = now();
        }

        if (isset($validated['delivery_status']) && $validated['delivery_status'] === 'delivered' && $order->delivery_status !== 'delivered') {
            $order->delivered_at = now();
        }

        $order->update($validated);

        return response()->json($order->load('item', 'member'));
    }
}
