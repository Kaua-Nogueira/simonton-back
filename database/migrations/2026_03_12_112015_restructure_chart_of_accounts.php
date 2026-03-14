<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Limpar o cache do Laravel e desabilitar constraints caso necessário
        // Criação da estrutura de árvore
        
        DB::transaction(function () {
            // Verifica se a raiz de Receitas já existe
            $receitasRoot = Category::where('name', 'Receitas')->whereNull('parent_id')->where('type', 'income')->first();
            if (!$receitasRoot) {
                $receitasRoot = Category::create([
                    'name' => 'Receitas',
                    'type' => 'income',
                    'description' => 'Grupo principal de Receitas',
                    'is_active' => true,
                    // O trigger de boot() na model irá gerar o code "1" automaticamente
                ]);
            }

            // Verifica se a raiz de Despesas já existe
            $despesasRoot = Category::where('name', 'Despesas')->whereNull('parent_id')->where('type', 'expense')->first();
            if (!$despesasRoot) {
                $despesasRoot = Category::create([
                    'name' => 'Despesas',
                    'type' => 'expense',
                    'description' => 'Grupo principal de Despesas e Custos',
                    'is_active' => true,
                    // "2"
                ]);
            }

            // Mover todas as receitas (que não sejam a própria raiz) para debaixo da raiz
            $incomes = Category::where('type', 'income')
                ->where('id', '!=', $receitasRoot->id)
                ->whereNull('parent_id')
                ->get();
            
            foreach ($incomes as $income) {
                $income->parent_id = $receitasRoot->id;
                // Força recriar o código no padrão hierárquico
                $income->code = Category::generateNextCode($receitasRoot->id, 'income');
                $income->save();
            }

            // Mover todas as despesas (que não sejam a própria raiz) para debaixo da raiz
            $expenses = Category::where('type', 'expense')
                ->where('id', '!=', $despesasRoot->id)
                ->whereNull('parent_id')
                ->get();

            foreach ($expenses as $expense) {
                $expense->parent_id = $despesasRoot->id;
                $expense->code = Category::generateNextCode($despesasRoot->id, 'expense');
                $expense->save();
            }

            // Atualiza possíveis subcategorias para usarem a estrutura 1.x.y (Opcional, se houver níveis profundos)
            $allSub = Category::whereNotNull('parent_id')->orderBy('parent_id')->get();
            foreach ($allSub as $sub) {
                if (!$sub->code || strpos($sub->code, '.') === false) {
                    $sub->code = Category::generateNextCode($sub->parent_id, $sub->type);
                    $sub->save();
                }
            }
        });
    }

    public function down(): void
    {
        // Reversão não recomendada pois altera UUIDs hierárquicos, 
        // mas as categorias filhas voltariam a ser independentes.
        // A regra é: don't rollback migrations. 
    }
};
