<?php

namespace App\Services;

use App\Models\PatrimonyAsset;
use App\Models\PatrimonyCategory;
use App\Models\PatrimonyMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PatrimonyService
{
    /**
     * Create a single asset with unique tombo generation.
     */
    public function createAsset(array $data): PatrimonyAsset
    {
        return DB::transaction(function () use ($data) {
            $category = PatrimonyCategory::where('id', $data['category_id'])->lockForUpdate()->firstOrFail();
            
            // Increment counter
            $category->increment('last_counter');
            
            // Generate tombo string (e.g., CAD-0001)
            $tombo = sprintf('%s-%04d', $category->prefix, $category->last_counter);
            
            $data['tombo'] = $tombo;
            $data['user_id'] = Auth::id();
            
            $asset = PatrimonyAsset::create($data);
            
            // Initial movement to the starting location
            PatrimonyMovement::create([
                'asset_id' => $asset->id,
                'from_location_id' => null,
                'to_location_id' => $data['location_id'],
                'movement_date' => now(),
                'user_id' => Auth::id(),
            ]);
            
            return $asset;
        });
    }

    /**
     * Create multiple identical assets in batch.
     */
    public function batchCreate(array $data, int $quantity): array
    {
        return DB::transaction(function () use ($data, $quantity) {
            $assets = [];
            for ($i = 0; $i < $quantity; $i++) {
                $assets[] = $this->createAsset($data);
            }
            return $assets;
        });
    }

    /**
     * Dispose multiple assets in batch.
     */
    public function batchDispose(array $ids, array $disposalData): void
    {
        DB::transaction(function () use ($ids, $disposalData) {
            PatrimonyAsset::whereIn('id', $ids)->update([
                'is_active' => false,
                'disposal_reason' => $disposalData['disposal_reason'],
                'disposal_date' => $disposalData['disposal_date'],
                'disposal_observations' => $disposalData['disposal_observations'] ?? null,
            ]);
        });
    }

    /**
     * Move an asset to a new location and record history.
     */
    public function moveAsset(PatrimonyAsset $asset, int $toLocationId, ?string $date = null): PatrimonyMovement
    {
        return DB::transaction(function () use ($asset, $toLocationId, $date) {
            $oldLocationId = $asset->location_id;
            
            if ($oldLocationId === $toLocationId) {
                throw new \Exception("O item já está nesta localização.");
            }
            
            $movement = PatrimonyMovement::create([
                'asset_id' => $asset->id,
                'from_location_id' => $oldLocationId,
                'to_location_id' => $toLocationId,
                'movement_date' => $date ? \Carbon\Carbon::parse($date) : now(),
                'user_id' => Auth::id(),
            ]);
            
            $asset->update(['location_id' => $toLocationId]);
            
            return $movement;
        });
    }
}
