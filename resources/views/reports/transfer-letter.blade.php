<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>transferencia-{{ \Illuminate\Support\Str::slug($member->name) }}-{{ \Illuminate\Support\Str::slug($member->destination_church ?? 'ipv') }}</title>
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
            margin-bottom: 60px;
            border-bottom: 3px double #004d40; /* IPB Green */
            padding-bottom: 30px;
        }
        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .header h1 {
            margin: 0;
            font-family: 'Cinzel', serif;
            font-size: 26px;
            color: #004d40;
            letter-spacing: 1px;
            font-weight: 700;
        }
        .header h2 {
            margin: 10px 0 0;
            font-family: 'Cinzel', serif;
            font-size: 20px;
            color: #333;
            font-weight: 400;
        }
        .date {
            text-align: right;
            margin-bottom: 40px;
            font-style: italic;
        }
        .content {
            margin-bottom: 60px;
            font-size: 16px;
            text-align: justify;
        }
        .content p {
            margin-bottom: 24px;
            text-indent: 40px;
        }
        .recipient {
            font-weight: bold;
            margin-bottom: 30px;
            display: block;
        }
        .footer {
            margin-top: 100px;
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
        .role {
            font-size: 14px;
            color: #555;
            text-transform: uppercase;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            width: 500px;
            pointer-events: none;
            z-index: -1;
            /* Force watermark to print */
             -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        @media print {
            @page {
                margin: 2cm;
                size: A4;
            }
            body { 
                padding: 0; 
                margin: 0; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .header { border-bottom-color: #004d40 !important; }
            .header h1 { color: #004d40 !important; }
            .watermark { opacity: 0.05 !important; display: block !important; }
            
            /* Ensure background images print if user settings allow, but we force color adjust */
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 40px; background: #f0fdf4; padding: 20px; border-radius: 8px; border: 1px solid #dcfce7;">
        <button onclick="window.print()" style="padding: 12px 24px; cursor: pointer; font-size: 16px; background-color: #004d40; color: white; border: none; border-radius: 6px; font-family: sans-serif; font-weight: bold;">
            🖨️ Imprimir Carta
        </button>
    </div>

    <!-- IPB Logo Watermark -->
    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="watermark" alt="IPB Watermark">

    <div class="header">
        <img src="https://upload.wikimedia.org/wikipedia/commons/e/e0/Igreja_Presbiteriana_do_Brasil.svg" class="logo" alt="IPB Logo">
        <h1>Igreja Presbiteriana do Brasil</h1>
        <h2>IPV - Igreja Presbiteriana de Vinhais</h2>
    </div>

    <div class="date">
        São Luís - MA, {{ date('d') }} de {{ \Carbon\Carbon::now()->locale('pt_BR')->translatedFormat('F') }} de {{ date('Y') }}
    </div>

    <div class="content">
        <div class="recipient">
            Ao Conselho da<br>
            {{ $member->destination_church ?? '________________________________________' }}
        </div>
        
        <p>Graça e Paz.</p>

        <p>
            Pela presente, comunicamos que o(a) irmão(ã) <strong>{{ strtoupper($member->name) }}</strong>,
            membro comungante desta Igreja, arrolado(a) sob o nº <strong>{{ $member->roll_number ?? '____' }}</strong>,
            solicitou sua transferência para essa estimada comunidade.
        </p>

        <p>
            Atestamos que o(a) referido(a) irmão(ã) encontra-se em plena comunhão com esta igreja,
            não pesando sobre ele(a) qualquer processo de disciplina eclesiástica que impeça sua recepção.
        </p>

        <p>
            Desta forma, recomendamos o(a) irmão(ã) aos vossos cuidados cristãos e pastorais,
            rogando que o(a) recebais no Senhor, conforme o vínculo de amor que nos une em Cristo Jesus.
        </p>

        <p>
            Sem mais para o momento, subscrevemo-nos em Cristo.
        </p>
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>Rev. Pastor da Igreja</strong><br>
            <span class="role">Presidente do Conselho</span>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>Secretário do Conselho</strong><br>
            <span class="role">Presbítero</span>
        </div>
    </div>
</body>
</html>
