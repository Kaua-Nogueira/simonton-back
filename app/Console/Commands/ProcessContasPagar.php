<?php

namespace App\Console\Commands;

use App\Services\ContaPagarService;
use Illuminate\Console\Command;

class ProcessContasPagar extends Command
{
    protected $signature = 'contas-pagar:process';
    protected $description = 'Marks overdue contas as vencido and generates next monthly instances for recurring series';

    public function handle(ContaPagarService $service): int
    {
        $vencidas = $service->checkAndMarkVencidas();
        $this->info("Vencidas marcadas: {$vencidas}");

        $geradas = $service->generateNextMonthlyInstances();
        $this->info("Instâncias do próximo mês geradas: {$geradas}");

        return Command::SUCCESS;
    }
}
