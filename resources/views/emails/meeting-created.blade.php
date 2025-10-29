<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Webinar Bildirişi</title>
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
            background-color: #2c5aa0;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .meeting-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2c5aa0;
        }
        .button {
            display: inline-block;
            background-color: #2c5aa0;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌾 Aqrar Portal</h1>
        <h2>Yeni Webinar Bildirişi</h2>
    </div>
    
    <div class="content">
        <p><strong>Hörmətli {{ $attendee['name'] }},</strong></p>
        
        <p>Sizi yeni bir webinar haqqında məlumatlandırmaq istəyirik:</p>
        
        <div class="meeting-info">
            <h3>📅 {{ $meeting->title }}</h3>
            <p><strong>📝 Təsvir:</strong> {{ $meeting->description }}</p>
            <p><strong>🕐 Tarix:</strong> {{ \Carbon\Carbon::parse($meeting->start_time)->format('d.m.Y') }}</p>
            <p><strong>⏰ Saat:</strong> {{ \Carbon\Carbon::parse($meeting->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($meeting->end_time)->format('H:i') }}</p>
            <p><strong>🌐 Platforma:</strong> Google Meet</p>
            @if($meeting->has_certificate)
                <p><strong>🏆 Sertifikat:</strong> Bəli, webinar sonunda sertifikat veriləcək</p>
            @endif
            @if($meeting->is_permanent)
                <p><strong>🔄 Daimi:</strong> Bu webinar daimi olaraq keçiriləcək</p>
            @endif
        </div>
        
        <p><strong>Qeydiyyat üçün seçimlər:</strong></p>
        <p>1. <strong>Birbaşa qeydiyyat:</strong> Aşağıdakı düyməyə klik edərək qeydiyyatdan keçin</p>
        <p>2. <strong>Vebsayt vasitəsilə:</strong> <a href="{{ config('app.url') }}/webinars">Aqrar Portal - Webinarlar</a> bölməsindən qeydiyyatdan keçin</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/webinars/{{ $meeting->id }}/register" class="button">📝 Qeydiyyatdan Keç</a>
            <a href="{{ $meeting->google_meet_link }}" class="button">🎥 Google Meet</a>
        </div>
        
        <p><strong>Əhəmiyyətli məlumatlar:</strong></p>
        <ul>
            <li>Webinar Google Meet platformasında keçiriləcək</li>
            <li>Qeydiyyat məcburidir</li>
            <li>Webinar başlamazdan 15 dəqiqə əvvəl link aktiv olacaq</li>
            @if($meeting->has_certificate)
                <li>Sertifikat almaq üçün webinarın ən azı 80%-ində iştirak etməlisiniz</li>
            @endif
        </ul>
    </div>
    
    <div class="footer">
        <p>Bu email Aqrar Portal tərəfindən avtomatik göndərilmişdir.</p>
        <p>Suallarınız üçün: <a href="mailto:support@aqrar.az">support@aqrar.az</a></p>
        <p>© {{ date('Y') }} Aqrar Portal. Bütün hüquqlar qorunur.</p>
    </div>
</body>
</html>


