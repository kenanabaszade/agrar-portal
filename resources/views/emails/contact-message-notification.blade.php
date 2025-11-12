<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Mesaj</title>
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
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .info-box {
            background-color: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .user-info {
            border-left-color: #27ae60;
        }
        .message-box {
            border-left-color: #e74c3c;
            white-space: pre-wrap;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Yeni Mesaj</h1>
    </div>
    
    <div class="content">
        <p>Salam,</p>
        
        <p><strong>{{ $contactMessage->name }}</strong> adlı istifadəçi sizə mesaj göndərmişdir.</p>
        
        <div class="info-box user-info">
            <h3>İstifadəçi Məlumatları</h3>
            <p><strong>Ad Soyad:</strong> {{ $contactMessage->name }}</p>
            <p><strong>E-mail:</strong> {{ $contactMessage->email }}</p>
            @if($contactMessage->phone)
            <p><strong>Telefon:</strong> {{ $contactMessage->phone }}</p>
            @endif
            @if($contactMessage->category)
            <p><strong>Kateqoriya:</strong> {{ $contactMessage->category }}</p>
            @endif
            <p><strong>Mesaj Tarixi:</strong> {{ $contactMessage->created_at->format('d.m.Y H:i') }}</p>
        </div>
        
        <div class="info-box">
            <h3>Mövzu</h3>
            <p><strong>{{ $contactMessage->subject }}</strong></p>
        </div>
        
        <div class="info-box message-box">
            <h3>Mesaj</h3>
            <p>{{ $contactMessage->message }}</p>
        </div>
        
        <p>Bu mesaja cavab vermək üçün istifadəçiyə e-mail göndərə bilərsiniz: <a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></p>
        
        <div class="footer">
            <p>Bu e-mail avtomatik olaraq göndərilmişdir. Cavab verməyin.</p>
            <p>&copy; {{ date('Y') }} Aqrar Portal. Bütün hüquqlar qorunur.</p>
        </div>
    </div>
</body>
</html>

