<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $action === 'created' ? 'Yeni Staj Proqramı' : 'Staj Proqramı Yeniləndi' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .program-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .program-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .status-open {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-closed {
            background: #ffebee;
            color: #c62828;
        }
        .status-full {
            background: #fff3e0;
            color: #e65100;
        }
        .program-info {
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            margin: 8px 0;
        }
        .info-label {
            font-weight: bold;
            width: 140px;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .description {
            background: #f8f9fa;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
            color: #666;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            font-size: 16px;
        }
        .cta-button:hover {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $action === 'created' ? '🎉 Yeni Staj Proqramı Açıldı!' : '📝 Staj Proqramı Yeniləndi!' }}</h1>
        <p>{{ $action === 'created' ? 'Yeni bir staj proqramı əlavə edildi' : 'Mövcud staj proqramı yeniləndi' }}</p>
    </div>

    <div class="content">
        <p>Salam <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

        <div class="program-card">
            <div class="program-title">{{ $program->title }}</div>
            
            <div class="status-badge status-{{ $program->registration_status }}">
                @if($program->registration_status === 'open')
                    Qeydiyyata Açıq
                @elseif($program->registration_status === 'closed')
                    Qeydiyyat Bitib
                @else
                    Yerlər Dolub
                @endif
            </div>

            @if($program->description)
                <div class="description">
                    <p style="margin: 0;">{{ Str::limit($program->description, 200) }}</p>
                </div>
            @endif

            <div class="program-info">
                @if($program->category)
                    <div class="info-row">
                        <span class="info-label">Kateqoriya:</span>
                        <span class="info-value">{{ $program->category }}</span>
                    </div>
                @endif
                
                @if($program->duration_weeks)
                    <div class="info-row">
                        <span class="info-label">Müddət:</span>
                        <span class="info-value">{{ $program->duration_weeks }} həftə</span>
                    </div>
                @endif

                @if($program->start_date)
                    <div class="info-row">
                        <span class="info-label">Başlama tarixi:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($program->start_date)->format('d.m.Y') }}</span>
                    </div>
                @endif

                @if($program->end_date)
                    <div class="info-row">
                        <span class="info-label">Bitmə tarixi:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($program->end_date)->format('d.m.Y') }}</span>
                    </div>
                @endif

                @if($program->last_register_date)
                    <div class="info-row">
                        <span class="info-label">Son qeydiyyat tarixi:</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($program->last_register_date)->format('d.m.Y') }}</span>
                    </div>
                @endif

                @if($program->location)
                    <div class="info-row">
                        <span class="info-label">Məkan:</span>
                        <span class="info-value">{{ $program->location }}</span>
                    </div>
                @endif

                @if($program->max_capacity)
                    <div class="info-row">
                        <span class="info-label">Maksimum yer:</span>
                        <span class="info-value">{{ $program->max_capacity }}</span>
                    </div>
                @endif
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="http://localhost:5174/internship-program/{{ $program->id }}" class="cta-button">
                    {{ $action === 'created' ? 'Staj Proqramına Qeydiyyatdan Keç' : 'Yenilənmiş Proqrama Bax və Qeydiyyatdan Keç' }}
                </a>
            </div>
        </div>

        <div class="footer">
            <p>Bu email Aqrar Portal sistemindən avtomatik göndərilmişdir.</p>
            <p>Əgər bu emaili siz istəməmisinizsə, zəhmət olmasa bizə məlumat verin.</p>
        </div>
    </div>
</body>
</html>

