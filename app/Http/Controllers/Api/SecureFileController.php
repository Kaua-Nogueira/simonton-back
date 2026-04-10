<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseReconciliation;
use App\Models\ExpenseReconciliationItem;
use App\Models\Society;
use App\Models\SocietyFinancialMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SecureFileController extends Controller
{
    public function reconciliationAttachment(Request $request, ExpenseReconciliation $reconciliation, ExpenseReconciliationItem $item)
    {
        if ($item->reconciliation_id !== $reconciliation->id) {
            return response()->json(['message' => 'Anexo invalido para esta prestacao.'], 403);
        }

        $society = $reconciliation->transaction?->society;
        if ($society && !$request->user()->can('view', $society)) {
            return response()->json(['message' => 'Acesso negado ao anexo.'], 403);
        }

        if (!$item->attachment_path || !Storage::disk('local')->exists($item->attachment_path)) {
            return response()->json(['message' => 'Arquivo nao encontrado.'], 404);
        }

        return response()->file(Storage::disk('local')->path($item->attachment_path), [
            'Content-Disposition' => 'inline; filename="'.basename($item->attachment_path).'"',
        ]);
    }

    public function societyMovementAttachment(Request $request, Society $society, SocietyFinancialMovement $movement)
    {
        if ((int) $movement->society_id !== (int) $society->id) {
            return response()->json(['message' => 'Movimentacao nao pertence a sociedade informada.'], 403);
        }

        if (!$request->user()->can('view', $society)) {
            return response()->json(['message' => 'Acesso negado ao anexo.'], 403);
        }

        if (!$movement->attachment_path || !Storage::disk('local')->exists($movement->attachment_path)) {
            return response()->json(['message' => 'Arquivo nao encontrado.'], 404);
        }

        return response()->file(Storage::disk('local')->path($movement->attachment_path), [
            'Content-Disposition' => 'inline; filename="'.basename($movement->attachment_path).'"',
        ]);
    }
}
