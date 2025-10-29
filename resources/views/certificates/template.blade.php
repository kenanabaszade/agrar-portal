<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            width: 297mm;
            height: 210mm;
        }
        
        .certificate {
            background: white;
            width: 100%;
            height: 100%;
            padding: 60px 80px;
            border: 20px solid #2c3e50;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .certificate::before {
            content: '';
            position: absolute;
            top: 30px;
            left: 30px;
            right: 30px;
            bottom: 30px;
            border: 2px solid #3498db;
            border-radius: 5px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }
        
        .header h1 {
            font-size: 32px;
            color: #2c3e50;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: bold;
        }
        
        .header h2 {
            font-size: 18px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .header h3 {
            font-size: 16px;
            color: #95a5a6;
            font-weight: normal;
        }
        
        .certificate-title {
            text-align: center;
            margin: 40px 0;
            position: relative;
            z-index: 1;
        }
        
        .certificate-title h2 {
            font-size: 48px;
            color: #3498db;
            text-transform: uppercase;
            letter-spacing: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .certificate-text {
            font-size: 18px;
            color: #34495e;
            margin-bottom: 15px;
        }
        
        .content {
            text-align: center;
            margin: 40px 0;
            position: relative;
            z-index: 1;
        }
        
        .recipient-name {
            font-size: 36px;
            color: #2c3e50;
            font-weight: bold;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            display: inline-block;
        }
        
        .training-name {
            font-size: 24px;
            color: #2980b9;
            margin: 30px 0;
            font-weight: bold;
            font-style: italic;
        }
        
        .description {
            font-size: 16px;
            color: #7f8c8d;
            line-height: 1.8;
            margin: 20px auto;
            max-width: 800px;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 60px;
            position: relative;
            z-index: 1;
        }
        
        .footer-left,
        .footer-right {
            flex: 1;
            text-align: center;
        }
        
        .footer-center {
            flex: 1;
            text-align: center;
        }
        
        .qr-code {
            width: 100px;
            height: 100px;
            margin: 0 auto 10px;
        }
        
        .certificate-number {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        .date {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .signature-line {
            border-top: 2px solid #2c3e50;
            width: 200px;
            margin: 20px auto 10px;
        }
        
        .signature-name {
            font-size: 14px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .signature-title {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(52, 152, 219, 0.05);
            font-weight: bold;
            z-index: 0;
            pointer-events: none;
        }
        
        .decorative-element {
            position: absolute;
            width: 100px;
            height: 100px;
            border: 5px solid #3498db;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .decorative-element.top-left {
            top: -50px;
            left: -50px;
        }
        
        .decorative-element.top-right {
            top: -50px;
            right: -50px;
        }
        
        .decorative-element.bottom-left {
            bottom: -50px;
            left: -50px;
        }
        
        .decorative-element.bottom-right {
            bottom: -50px;
            right: -50px;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="decorative-element top-left"></div>
        <div class="decorative-element top-right"></div>
        <div class="decorative-element bottom-left"></div>
        <div class="decorative-element bottom-right"></div>
        
        <div class="watermark">AXA</div>
        
        <div class="header">
            <div class="logo-container">
                @if($certificate->issuer_logo_url)
                    <img src="{{ $certificate->issuer_logo_url }}" alt="Logo" class="logo">
                @endif
            </div>
            <h2>{{ $certificate->issuer_name }}</h2>
            <h3>Azərbaycan Respublikasının Kənd Təsərrüfatı Nazirliyi yanında</h3>
        </div>
        
        <div class="certificate-title">
            <h2>SERTİFİKAT</h2>
            <p class="certificate-text">Bu sertifikat təsdiq edir ki,</p>
        </div>
        
        <div class="content">
            <div class="recipient-name">{{ $user->first_name }} {{ $user->last_name }}</div>
            
            <p class="description">
                aşağıdakı təlim proqramını uğurla tamamlamışdır:
            </p>
            
            <div class="training-name">{{ $training->title }}</div>
            
            @if($training->description)
                <p class="description">
                    {{ Str::limit($training->description, 150) }}
                </p>
            @endif
            
            @if($exam)
                <p class="description" style="margin-top: 20px; color: #27ae60; font-weight: bold;">
                    İmtahan nəticəsi: {{ $examScore }}%
                    @if($examScore >= 90)
                        - Əla
                    @elseif($examScore >= 80)
                        - Yaxşı
                    @elseif($examScore >= 70)
                        - Kafi
                    @else
                        - Keçid
                    @endif
                </p>
            @endif
        </div>
        
        <div class="footer">
            <div class="footer-left">
                <div class="date">Verilmə tarixi:</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ \Carbon\Carbon::parse($certificate->issue_date)->format('d.m.Y') }}</div>
            </div>
            
            <div class="footer-center">
                @if($qrCode)
                    <div class="qr-code">
                        {!! $qrCode !!}
                    </div>
                @endif
                <div class="certificate-number">
                    Sertifikat № {{ $certificate->certificate_number }}
                </div>
                @if($certificate->expiry_date)
                    <div class="certificate-number" style="margin-top: 5px;">
                        Etibarlıdır: {{ \Carbon\Carbon::parse($certificate->expiry_date)->format('d.m.Y') }}-dək
                    </div>
                @endif
            </div>
            
            <div class="footer-right">
                <div class="date">Rəqəmsal imza:</div>
                <div class="signature-line"></div>
                <div class="signature-name">{{ $certificate->issuer_name }}</div>
                <div class="signature-title">Aqrar Xidmətlər Agentliyi</div>
            </div>
        </div>
    </div>
</body>
</html>



