<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ata {{ $meeting->id }} - {{ $meeting->date->format('d/m/Y') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Noto+Serif:wght@400;700&display=swap');

        body {
            font-family: 'Noto Serif', 'Times New Roman', Times, serif;
            line-height: 1.6;
            margin: 0;
            padding: 40px 60px;
            color: #1a1a1a;
            max-width: 800px;
            margin: 0 auto;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px double #004d40;
            padding-bottom: 20px;
        }
        .logo {
            width: 70px;
            height: auto;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 24px;
            color: #004d40;
            font-weight: 700;
        }
        .header h3 {
            margin: 5px 0 0;
            font-family: 'Cinzel', serif;
            font-size: 16px;
            color: #333;
            font-weight: 400;
        }
        .meta-info {
            text-align: center;
            margin-bottom: 30px;
            font-style: italic;
            font-size: 14px;
        }
        .section-title {
            text-transform: uppercase;
            font-weight: bold;
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #004d40;
        }
        .content {
            text-align: justify;
            font-size: 15px;
        }
        .content p {
            margin-bottom: 15px;
            text-indent: 30px;
        }
        .attendance-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
            font-size: 14px;
        }
        .attendance-list li {
            margin-bottom: 5px;
        }
        .resolution-item {
            margin-bottom: 15px;
            border-left: 3px solid #004d40;
            padding-left: 15px;
        }
        .resolution-title {
            font-weight: bold;
            display: block;
        }
        .footer {
            margin-top: 80px;
            display: flex;
            justify-content: center;
            gap: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            text-align: center;
            flex: 1;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
            margin-bottom: 10px;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.04;
            width: 400px;
            z-index: -1;
            pointer-events: none;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { padding: 0cm; margin: 2cm; }
            .header { border-bottom-color: #004d40 !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #004d40; color: white; border: none; border-radius: 5px;">
            🖨️ Imprimir Ata
        </button>
    </div>

    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="watermark" alt="IPB Watermark">

    <div class="header">
        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="logo" alt="logo">
        <h1>Igreja Presbiteriana de Vinhais</h1>
        <h3>Ata de Reunião {{ $meeting->type }} do Conselho</h3>
    </div>

    <div class="meta-info">
        Realizada em {{ $meeting->date->format('d/m/Y') }} às {{ $meeting->time }}, em {{ $meeting->location }}
    </div>

    <div class="content">
        <!-- Abertura -->
        <div class="section-title">Abertura</div>
        <p>
            Aos {{ \Carbon\Carbon::parse($meeting->date)->locale('pt_BR')->translatedFormat('d \d\e F \d\e Y') }},
            reuniu-se o Egrégio Conselho da Igreja Presbiteriana de Vinhais.
            @if($meeting->presidingOfficer)
                A presidência foi exercida pelo Rev. {{ $meeting->presidingOfficer->name }}.
            @endif
            @if($meeting->secretary)
                Secretariada pelo Presb. {{ $meeting->secretary->name }}.
            @endif
        </p>
        @if($meeting->opening_prayer)
        <p><strong>Oração Inicial:</strong> {{ $meeting->opening_prayer }}</p>
        @endif

        <!-- Quorum -->
        <div class="section-title">Verificação de Quórum</div>
        @php
            $present = $meeting->attendances->where('status', 'Presente');
            $absent = $meeting->attendances->where('status', '!=', 'Presente');
        @endphp
        <p>
            Verificada a presença dos membros, constatou-se haver número legal para o funcionamento do concílio.
            Estavam <strong>presentes</strong>: {{ $present->map(fn($a) => $a->member->name)->join(', ', ' e ') }}.
            @if($absent->count() > 0)
            <br>
            <strong>Ausentes</strong>: {{ $absent->map(fn($a) => $a->member->name . ' (' . $a->status . ')')->join(', ', ' e ') }}.
            @endif
        </p>

        <!-- Expediente -->
        @if($meeting->expedient)
        <div class="section-title">Expediente</div>
        <p>{!! nl2br(e($meeting->expedient)) !!}</p>
        @endif

        <!-- Relatórios -->
        @if($meeting->reports)
        <div class="section-title">Relatórios</div>
        <p>{!! nl2br(e($meeting->reports)) !!}</p>
        @endif

        <!-- Ordens do Dia (Resoluções) -->
        <div class="section-title">Ordens do Dia</div>
        @forelse($meeting->resolutions as $resolution)
            <div class="resolution-item">
                <span class="resolution-title">Resolução {{ $resolution->id }}/{{ \Carbon\Carbon::parse($meeting->date)->year }} - {{ $resolution->topic }}</span>
                {{ $resolution->content }}
                @if($resolution->responsible)
                <br><em>Responsável: {{ $resolution->responsible->name }}</em>
                @endif
                <br><em>Status: {{ $resolution->status }}</em>
            </div>
        @empty
            <p>Nada constou na ordem do dia.</p>
        @endforelse

        <!-- Encerramento -->
        <div class="section-title">Encerramento</div>
        <p>Nada mais havendo a tratar, a reunião foi encerrada com oração realizada por {{ $meeting->closing_prayer ?? 'um dos oficiais' }}.</p>
        <p>Eu, oficial abaixo assinado, lavrei a presente ata que, após lida e aprovada, vai assinada por mim e pelo Presidente.</p>
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>{{ $meeting->presidingOfficer->name ?? 'Moderador' }}</strong><br>
            <span class="role">Presidente</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>{{ $meeting->secretary->name ?? 'Secretário' }}</strong><br>
            <span class="role">Secretário do Conselho</span>
        </div>
    </div>
</body>
</html>
