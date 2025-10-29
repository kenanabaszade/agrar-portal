<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staj Proqramı Müraciəti Qəbul Edildi</title>
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
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .success-box {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        .info-box {
            background-color: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .program-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            width: 140px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            background: #fff3e0;
            color: #e65100;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Müraciətiniz Qəbul Edildi</h1>
        <p>Staj Proqramı Müraciəti</p>
    </div>
    
    <div class="content">
        <p>Salam <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>
        
        <div class="success-box">
            <h3 style="margin-top: 0; color: #2e7d32;">Müraciətiniz Uğurla Göndərildi!</h3>
            <p>Siz <strong>{{ $program->title }}</strong> staj proqramı üçün müraciət etmisiniz. Məlumatlarınız yaxın zamanda əməkdaşlarımız tərəfindən dəyərləndirilib, sizə geri bildiriş göndəriləcək.</p>
        </div>
        
        <div class="info-box">
            <div class="program-title">{{ $program->title }}</div>
            
            <div class="info-row">
                <span class="info-label">Müraciət Tarixi:</span>
                <span class="info-value">{{ $application->created_at->format('d.m.Y H:i') }}</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge">Gözləmədə</span>
                </span>
            </div>
            
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
                    <span class="info-value">{{ $program->start_date->format('d.m.Y') }}</span>
                </div>
            @endif
            
            @if($program->location)
                <div class="info-row">
                    <span class="info-label">Məkan:</span>
                    <span class="info-value">{{ $program->location }}</span>
                </div>
            @endif
        </div>
        
        <div class="info-box">
            <h3 style="margin-top: 0; color: #2c3e50;">Növbəti Addımlar</h3>
            <p>Müraciətiniz adminlər tərəfindən yoxlanılacaq və dəyərləndiriləcək. Dəyərləndirmə nəticələri haqqında sizə email vasitəsilə məlumat veriləcək.</p>
            <p>Zəhmət olmasa, email qutunuzu düzenli yoxlayın.</p>
        </div>
        
        <div class="footer">
            <p>Bu email Aqrar Portal sistemindən avtomatik göndərilmişdir.</p>
            <p>Əgər sualınız varsa, zəhmət olmasa bizimlə əlaqə saxlayın.</p>
        </div>
    </div>
</body>
</html>

