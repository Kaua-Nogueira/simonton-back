<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChurchConfig;
use Illuminate\Http\Request;

class ChurchConfigController extends Controller
{
    /**
     * Retorna todas as configurações da igreja como um objeto plano.
     */
    public function index()
    {
        $configs = ChurchConfig::all()->pluck('value', 'key');
        
        // Padrões se não houver no banco (garante que o front não quebre)
        $defaults = [
            'org_name' => 'Igreja Presbiteriana Simonton',
            'org_initials' => 'IPB Simonton',
            'org_cnpj' => '00.000.000/0001-00',
            'org_address' => 'Rua Principal, 100 - Centro',
            'org_phone' => '(00) 0000-0000',
            'org_email' => 'contato@ipb.org.br',
        ];

        return response()->json(array_merge($defaults, $configs->toArray()));
    }

    /**
     * Retorna apenas informações públicas da igreja.
     */
    public function publicInfo()
    {
        $configs = ChurchConfig::whereIn('key', ['org_name', 'org_initials', 'org_address', 'org_phone'])->pluck('value', 'key');
        
        $defaults = [
            'org_name' => 'Igreja Presbiteriana Simonton',
            'org_initials' => 'IPB Simonton',
        ];

        return response()->json(array_merge($defaults, $configs->toArray()));
    }

    /**
     * Atualiza ou cria as configurações enviadas no request.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'configs' => 'required|array',
        ]);

        foreach ($validated['configs'] as $key => $value) {
            ChurchConfig::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Configurações atualizadas com sucesso!']);
    }

    /**
     * Helper estático simplificado para o backend (usado pelos PDFs)
     */
    public static function get($key, $default = null)
    {
        return ChurchConfig::where('key', $key)->value('value') ?? $default;
    }
}
