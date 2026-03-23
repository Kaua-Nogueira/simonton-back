<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balancete Mensal - {{ $society->name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; margin: 0; text-transform: uppercase; color: #1a365d; }
        .header h2 { font-size: 14px; margin: 5px 0; color: #4a5568; }
        .header p { font-size: 10px; margin: 0; color: #718096; }
        
        .sub-header { display: table; width: 100%; margin-bottom: 15px; }
        .sub-header-col { display: table-cell; width: 50%; }
        .sub-header-col.right { text-align: right; }
        
        .section-title { background: #f7fafc; padding: 5px 10px; border-left: 4px solid #3182ce; font-weight: bold; margin: 15px 0 10px; text-transform: uppercase; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #edf2f7; text-align: left; padding: 6px 8px; border-bottom: 1px solid #cbd5e0; }
        td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
        
        .text-right { text-align: right; }
        .amount-pos { color: #2f855a; font-weight: bold; }
        .amount-neg { color: #c53030; font-weight: bold; }
        
        .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .summary-row { display: table; width: 100%; margin-bottom: 5px; }
        .summary-label { display: table-cell; width: 70%; font-size: 12px; }
        .summary-value { display: table-cell; width: 30%; text-align: right; font-size: 12px; font-weight: bold; }
        .summary-total { border-top: 2px solid #cbd5e0; padding-top: 8px; margin-top: 8px; }
        .summary-total .summary-label { font-size: 14px; font-weight: 800; }
        .summary-total .summary-value { font-size: 14px; font-weight: 800; }

        .signatures { margin-top: 60px; text-align: center; }
        .signature-line { display: inline-block; width: 220px; border-top: 1px solid #000; margin: 0 20px; padding-top: 5px; text-align: center; }
        .signature-title { font-size: 9px; text-transform: uppercase; color: #718096; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $church['name'] }}</h1>
        <h2>Relatório Financeiro: {{ $society->name }}</h2>
        <p>CNPJ: {{ $church['cnpj'] }} | {{ $church['address'] }}</p>
    </div>

    <div class="sub-header">
        <div class="sub-header-col">
            <strong>PERÍODO:</strong> {{ $periodo }}<br>
            <strong>EMISSÃO:</strong> {{ $dataEmissao }}
        </div>
        <div class="sub-header-col right">
            <strong>STATUS:</strong> CONSOLIDADO<br>
            <strong>SALDO ANTERIOR:</strong> R$ {{ number_format($saldoAnterior, 2, ',', '.') }}
        </div>
    </div>

    <div class="section-title">Movimentações do Período</div>
    <table>
        <thead>
            <tr>
                <th width="15%">Data</th>
                <th width="45%">Descrição / Categoria</th>
                <th width="20%">Tipo</th>
                <th width="20%" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimentacoes as $mov)
                <tr>
                    <td>{{ $mov->date->format('d/m/Y') }}</td>
                    <td>
                        {{ $mov->description }}<br>
                        <small style="color: #a0aec0;">{{ $mov->category }}</small>
                    </td>
                    <td style="text-transform: capitalize;">{{ $mov->type == 'income' ? 'Entrada' : 'Saída' }}</td>
                    <td class="text-right {{ $mov->type == 'income' ? 'amount-pos' : 'amount-neg' }}">
                        {{ $mov->type == 'income' ? '+' : '-' }} R$ {{ number_format($mov->amount, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
            @if($movimentacoes->isEmpty())
                <tr>
                    <td colspan="4" style="text-align: center; font-style: italic; color: #a0aec0; padding: 20px;">
                        Nenhuma movimentação registrada no período.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="summary-card">
        <div class="section-title" style="margin-top: 0; background: none; border-left: none; padding-left: 0;">Fechamento Mensal</div>
        <div class="summary-row">
            <div class="summary-label">Saldo Anterior</div>
            <div class="summary-value">R$ {{ number_format($saldoAnterior, 2, ',', '.') }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total de Entradas no Mês (+)</div>
            <div class="summary-value amount-pos">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Total de Saídas no Mês (-)</div>
            <div class="summary-value amount-neg">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
        </div>
        <div class="summary-row summary-total">
            <div class="summary-label">SALDO ATUAL EM CAIXA (=)</div>
            <div class="summary-value">R$ {{ number_format($saldoAtual, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="signatures">
        <div class="signature-line">
            <strong>Assinatura do Tesoureiro</strong><br>
            <span class="signature-title">{{ $society->name }}</span>
        </div>
        <div class="signature-line">
            <strong>Visto do Conselheiro</strong><br>
            <span class="signature-title">Igreja Presbiteriana Simonton</span>
        </div>
    </div>

    <div class="footer">
        Relatório gerado automaticamente pelo Sistema Simonton de Gestão Eclesiástica - {{ date('Y') }}
    </div>
</body>
</html>
