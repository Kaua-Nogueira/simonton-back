<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balancete Mensal Geral - {{ $church['name'] }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #ddd; padding-bottom: 15px; margin-bottom: 25px; }
        .header h1 { font-size: 20px; margin: 0; text-transform: uppercase; color: #1a365d; }
        .header h2 { font-size: 16px; margin: 5px 0; color: #4a5568; }
        .header p { font-size: 10px; margin: 0; color: #718096; }
        
        .sub-header { display: table; width: 100%; margin-bottom: 20px; }
        .sub-header-col { display: table-cell; width: 50%; }
        .sub-header-col.right { text-align: right; }
        
        .section-title { background: #edf2f7; padding: 6px 12px; font-weight: bold; margin: 20px 0 10px; text-transform: uppercase; border-radius: 4px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { text-align: left; padding: 8px; border-bottom: 1px solid #cbd5e0; background: #f7fafc; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        
        .text-right { text-align: right; }
        .amount-pos { color: #2f855a; font-weight: bold; }
        .amount-neg { color: #c53030; font-weight: bold; }
        
        .summary-card { background: #f8fafc; border: 1.5px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-top: 30px; }
        .summary-row { display: table; width: 100%; margin-bottom: 6px; }
        .summary-label { display: table-cell; width: 65%; font-size: 12px; }
        .summary-value { display: table-cell; width: 35%; text-align: right; font-size: 12px; font-weight: bold; }
        .summary-total { border-top: 2px solid #2d3748; padding-top: 10px; margin-top: 10px; }
        .summary-total .summary-label { font-size: 15px; font-weight: 900; }
        .summary-total .summary-value { font-size: 15px; font-weight: 900; }

        .signatures { margin-top: 80px; text-align: center; }
        .signature-line { display: inline-block; width: 230px; border-top: 1.5px solid #000; margin: 0 25px; padding-top: 8px; text-align: center; }
        .signature-title { font-size: 10px; text-transform: uppercase; color: #718096; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $church['name'] }}</h1>
        <h2>Balancete Mensal Consolidado</h2>
        <p>CNPJ: {{ $church['cnpj'] }} | {{ $church['address'] }}</p>
    </div>

    <div class="sub-header">
        <div class="sub-header-col">
            <strong>Mês de Referência:</strong> {{ $periodo }}<br>
            <strong>Data de Emissão:</strong> {{ $dataEmissao }}
        </div>
        <div class="sub-header-col right">
            <strong>Saldo Anterior ao Período:</strong><br>
            <span style="font-size: 14px; font-weight: 900; color: #2d3748;">
                R$ {{ number_format($saldoAnterior, 2, ',', '.') }}
            </span>
        </div>
    </div>

    <!-- Demonstrativo de Receitas -->
    <div class="section-title">Demonstrativo de Receitas (Entradas)</div>
    <table>
        <thead>
            <tr>
                <th width="70%">Categoria / Descrição</th>
                <th width="30%" class="text-right">Valor Consolidado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resumoEntradas as $item)
                <tr>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-right amount-pos">R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            @if($resumoEntradas->isEmpty())
                <tr>
                    <td colspan="2" style="text-align: center; color: #a0aec0; padding: 20px;">Nenhuma receita no período.</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr style="background: #f0fff4;">
                <td style="font-weight: bold;">TOTAL DE ENTRADAS (+)</td>
                <td class="text-right amount-pos" style="font-size: 13px;">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Demonstrativo de Despesas -->
    <div class="section-title">Demonstrativo de Despesas (Saídas)</div>
    <table>
        <thead>
            <tr>
                <th width="70%">Categoria / Descrição</th>
                <th width="30%" class="text-right">Valor Consolidado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resumoSaidas as $item)
                <tr>
                    <td>{{ $item['category'] }}</td>
                    <td class="text-right amount-neg">R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                </tr>
            @endforeach
            @if($resumoSaidas->isEmpty())
                <tr>
                    <td colspan="2" style="text-align: center; color: #a0aec0; padding: 20px;">Nenhuma despesa no período.</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr style="background: #fff5f5;">
                <td style="font-weight: bold;">TOTAL DE SAÍDAS (-)</td>
                <td class="text-right amount-neg" style="font-size: 13px;">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Fechamento Consolidado -->
    <div class="summary-card">
        <div class="summary-row">
            <div class="summary-label">Saldo Acumulado Anterior</div>
            <div class="summary-value">R$ {{ number_format($saldoAnterior, 2, ',', '.') }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Soma de Entradas do mês (+)</div>
            <div class="summary-value amount-pos">R$ {{ number_format($totalEntradas, 2, ',', '.') }}</div>
        </div>
        <div class="summary-row">
            <div class="summary-label">Soma de Saídas do mês (-)</div>
            <div class="summary-value amount-neg">R$ {{ number_format($totalSaidas, 2, ',', '.') }}</div>
        </div>
        <div class="summary-row summary-total">
            <div class="summary-label">SALDO ATUAL EM CONTA (=)</div>
            <div class="summary-value">R$ {{ number_format($saldoAtual, 2, ',', '.') }}</div>
        </div>
    </div>

    <!-- Assinaturas -->
    <div class="signatures">
        <div class="signature-line">
            <strong>Tesoureiro(a) do Conselho</strong><br>
            <span class="signature-title">{{ $church['name'] }}</span>
        </div>
        <div class="signature-line">
            <strong>Presidente do Conselho</strong><br>
            <span class="signature-title">Igreja Presbiteriana do Brasil</span>
        </div>
    </div>

    <div class="footer">
        Este documento é uma prestação de contas oficial gerada pelo Sistema Simonton de Gestão Eclesiástica em {{ date('d/m/Y') }}.
    </div>
</body>
</html>
