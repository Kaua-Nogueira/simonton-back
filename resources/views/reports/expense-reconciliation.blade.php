<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prestação de Contas #{{ $reconciliation->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Noto+Serif:wght@400;700&display=swap');

        body {
            font-family: 'Noto Serif', 'Times New Roman', Times, serif;
            line-height: 1.5;
            margin: 0;
            padding: 30px 40px;
            color: #1a1a1a;
            font-size: 13px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #004d40;
            padding-bottom: 15px;
        }
        .logo {
            width: 60px;
            height: auto;
            margin-bottom: 5px;
        }
        .header h1 {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 20px;
            color: #004d40;
        }
        .header h3 {
            margin: 5px 0 0;
            font-family: 'Cinzel', serif;
            font-size: 14px;
            color: #333;
            font-weight: 400;
        }
        .title-box {
            text-align: center;
            background: #f4f7f6;
            padding: 10px;
            border: 1px solid #004d40;
            margin-bottom: 20px;
        }
        .title-box h2 {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            color: #004d40;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 5px;
            border: 1px solid #eee;
        }
        .label {
            font-weight: bold;
            color: #555;
            width: 30%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #004d40;
            color: white;
            text-align: left;
            padding: 8px;
            font-family: 'Cinzel', serif;
            font-size: 11px;
        }
        .items-table td {
            border: 1px solid #eee;
            padding: 8px;
        }
        .footer-summary {
            width: 100%;
            margin-top: 10px;
            border-top: 2px solid #004d40;
            padding-top: 10px;
        }
        .summary-row {
            text-align: right;
            padding: 5px 0;
        }
        .summary-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .summary-value {
            display: inline-block;
            width: 100px;
            font-weight: bold;
            font-size: 14px;
        }
        .signatures {
            margin-top: 60px;
            width: 100%;
        }
        .signature-box {
            text-align: center;
            width: 45%;
            display: inline-block;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto 5px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.03;
            width: 300px;
            z-index: -1;
        }
        .total-highlight {
            color: #004d40;
            font-size: 16px;
        }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="watermark" alt="IPB Watermark">

    <div class="header">
        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="logo" alt="logo">
        <h1>Igreja Presbiteriana de Vinhais</h1>
        <h3>Sistema de Gestão Financeira Simonton</h3>
    </div>

    <div class="title-box">
        <h2>Demonstrativo de Prestação de Contas</h2>
    </div>

    <table class="info-grid">
        <tr>
            <td class="label">Nº Prestação:</td>
            <td>#{{ str_pad($reconciliation->id, 5, '0', STR_PAD_LEFT) }}</td>
            <td class="label">Data de Emissão:</td>
            <td>{{ now()->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Responsável:</td>
            <td>{{ $reconciliation->responsibleMember->name ?? 'Não Informado' }}</td>
            <td class="label">Status:</td>
            <td>{{ $reconciliation->status == 'closed' ? 'Finalizada' : 'Aberta' }}</td>
        </tr>
        <tr>
            <td class="label">Referência (Saída):</td>
            <td colspan="3">{{ $reconciliation->transaction->description }} ({{ $reconciliation->transaction->date->format('d/m/Y') }})</td>
        </tr>
    </table>

    <div style="margin-top: 20px; font-weight: bold; color: #004d40; margin-bottom: 10px; font-family: 'Cinzel', serif;">
        Relação de Comprovantes (Notas Fiscais / Recibos)
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="12%">Data</th>
                <th>Descrição / Favorecido</th>
                <th width="15%">Doc. Nº</th>
                <th width="20%">Categoria</th>
                <th width="15%" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reconciliation->items as $item)
            <tr>
                <td>{{ $item->date->format('d/m/Y') }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ $item->document_number ?? '-' }}</td>
                <td>{{ $item->category->name ?? '-' }}</td>
                <td class="text-right">R$ {{ number_format($item->amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            @if($reconciliation->items->count() == 0)
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px; color: #999;">Nenhum comprovante cadastrado até o momento.</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="footer-summary">
        <div class="summary-row">
            <span class="summary-label">Total Adiantado:</span>
            <span class="summary-value">R$ {{ number_format($reconciliation->total_advanced, 2, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Comprovado:</span>
            <span class="summary-value" style="color: #c62828;">R$ {{ number_format($reconciliation->total_reconciled, 2, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label total-highlight">Diferença / Saldo:</span>
            <span class="summary-value total-highlight">R$ {{ number_format($reconciliation->total_advanced - $reconciliation->total_reconciled, 2, ',', '.') }}</span>
        </div>
    </div>

    @if($reconciliation->notes)
    <div style="margin-top: 30px;">
        <div style="font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 5px;">Observações:</div>
        <div style="font-style: italic; color: #444;">{!! nl2br(e($reconciliation->notes)) !!}</div>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-box" style="margin-right: 5%;">
            <div class="signature-line"></div>
            <strong>{{ $reconciliation->responsibleMember->name ?? 'Responsável' }}</strong><br>
            <span>Assinatura do Recebedor</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>Tesouraria / Secretaria</strong><br>
            <span>Conferido em ____/____/_______</span>
        </div>
    </div>

    <div style="position: absolute; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999;">
        Documento gerado automaticamente pelo Simonton Hub em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
