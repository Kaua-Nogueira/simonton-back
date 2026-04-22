<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferência de Diaconia #{{ $entry->id }}</title>
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
            border-bottom: 2px solid #065f46;
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
            color: #065f46;
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
            background: #f0fdf4;
            padding: 10px;
            border: 1px solid #065f46;
            margin-bottom: 20px;
        }
        .title-box h2 {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            color: #065f46;
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
            width: 25%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #065f46;
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
        .section-title {
            margin-top: 25px;
            font-weight: bold;
            color: #065f46;
            margin-bottom: 10px;
            font-family: 'Cinzel', serif;
            border-bottom: 1px solid #065f46;
            padding-bottom: 5px;
        }
        .footer-summary {
            width: 100%;
            margin-top: 10px;
            border-top: 2px solid #065f46;
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
            width: 120px;
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
            color: #065f46;
            font-size: 16px;
        }
        .text-right { text-align: right; }
        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="watermark" alt="IPB Watermark">

    <div class="header">
        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="logo" alt="logo">
        <h1>{{ $churchName }}</h1>
        <h3>Relatório de Conferência de Diaconia</h3>
    </div>

    <div class="title-box">
        <h2>Lote de Conferência #{{ str_pad($entry->id, 5, '0', STR_PAD_LEFT) }}</h2>
    </div>

    <table class="info-grid">
        <tr>
            <td class="label">Data do Culto:</td>
            <td>{{ $entry->date->format('d/m/Y') }}</td>
            <td class="label">Data da Conferência:</td>
            <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Diácono Responsável:</td>
            <td>{{ $entry->user->name ?? 'N/A' }}</td>
            <td class="label">Status:</td>
            <td>
                @if($entry->status == 'confirmed')
                    <span class="badge badge-confirmed">Conciliado</span>
                @elseif($entry->status == 'pending')
                    <span class="badge badge-pending">Aguardando Auditoria</span>
                @else
                    <span class="badge">{{ $entry->status }}</span>
                @endif
            </td>
        </tr>
        @if($entry->confirmer)
        <tr>
            <td class="label">Confirmado por (Tesouraria):</td>
            <td>{{ $entry->confirmer->name }}</td>
            <td class="label">Data de Confirmação:</td>
            <td>{{ $entry->updated_at->format('d/m/Y H:i') }}</td>
        </tr>
        @endif
    </table>

    <div class="section-title">Composição do Lote (Envelopes e Entradas)</div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="30%">Contribuinte / Sociedade</th>
                <th width="15%">Tipo</th>
                <th width="15%">Meio</th>
                <th>Descrição</th>
                <th width="15%" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entry->splits as $split)
            <tr>
                <td>
                    @if($split->member)
                        {{ $split->member->name }}
                    @elseif($split->society)
                        {{ $split->society->name }}
                    @else
                        -
                    @endif
                </td>
                <td>
                    @switch($split->type)
                        @case('tithe') Dízimo @break
                        @case('offering') Oferta @break
                        @case('mission') Missões @break
                        @default {{ ucfirst($split->type) }}
                    @endswitch
                </td>
                <td>{{ $split->is_digital ? 'Digital' : 'Espécie' }}</td>
                <td>{{ $split->description ?? '-' }}</td>
                <td class="text-right">R$ {{ number_format($split->amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($entry->cash->count() > 0)
    <div class="section-title">Conferência de Espécie</div>
    <table class="items-table" style="width: 50%;">
        <thead>
            <tr>
                <th>Cédula/Moeda</th>
                <th class="text-right">Qtd</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entry->cash as $cash)
            <tr>
                <td>R$ {{ number_format($cash->denomination, 2, ',', '.') }}</td>
                <td class="text-right">{{ $cash->quantity }}</td>
                <td class="text-right">R$ {{ number_format($cash->amount, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer-summary">
        <div class="summary-row">
            <span class="summary-label">Total Digital:</span>
            <span class="summary-value">R$ {{ number_format($entry->splits->where('is_digital', true)->sum('amount'), 2, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Espécie:</span>
            <span class="summary-value">R$ {{ number_format($entry->splits->where('is_digital', false)->sum('amount'), 2, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label total-highlight">VALOR TOTAL:</span>
            <span class="summary-value total-highlight">R$ {{ number_format($entry->total_amount, 2, ',', '.') }}</span>
        </div>
    </div>

    @if($entry->notes)
    <div style="margin-top: 20px;">
        <div style="font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 5px;">Observações:</div>
        <div style="font-style: italic; color: #444;">{!! nl2br(e($entry->notes)) !!}</div>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-box" style="margin-right: 5%;">
            <div class="signature-line"></div>
            <strong>{{ $entry->user->name ?? 'Diácono Responsável' }}</strong><br>
            <span>Assinatura do Diácono</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>Tesouraria</strong><br>
            <span>Conferido por: ____________________</span>
        </div>
    </div>

    <div style="position: absolute; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999;">
        Documento gerado automaticamente pelo Simonton Hub em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
