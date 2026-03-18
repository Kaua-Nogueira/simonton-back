<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaPagar extends Model
{
    protected $table = 'contas_pagar';

    protected $fillable = [
        'descricao',
        'valor',
        'data_vencimento',
        'category_id',
        'cost_center_id',
        'budget_item_id',
        'transaction_id',
        'status',
        'recorrente',
        'tipo_recorrencia',
        'dia_vencimento',
        'data_inicio',
        'data_fim',
        'serie_id',
        'gerado_automaticamente',
        'user_id',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'data_inicio'     => 'date',
        'data_fim'        => 'date',
        'recorrente'      => 'boolean',
        'gerado_automaticamente' => 'boolean',
        'valor'           => 'decimal:2',
    ];

    // --- Relationships ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // --- Scopes ---

    public function scopePendentes($query)
    {
        return $query->where('status', 'pendente');
    }

    public function scopeVencidas($query)
    {
        return $query->where('status', 'vencido');
    }

    public function scopePagas($query)
    {
        return $query->where('status', 'pago');
    }

    public function scopeParaMes($query, int $year, int $month)
    {
        return $query->whereYear('data_vencimento', $year)
                     ->whereMonth('data_vencimento', $month);
    }
}
