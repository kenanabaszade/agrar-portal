<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Təlim Xatırlatması</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .header {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header h2 {
            margin: 10px 0 0 0;
            font-size: 20px;
            font-weight: normal;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .training-info {
            background: #fff8f5;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ff6b35;
        }
        .training-info h3 {
            color: #ff6b35;
            margin-top: 0;
        }
        .reminder-badge {
            background: #ff6b35;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .button:hover {
            background: linear-gradient(135deg, #e55a2b, #e0841a);
            transform: translateY(-2px);
        }
        .meet-button {
            background: linear-gradient(135deg, #4285f4, #1a73e8);
        }
        .meet-button:hover {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
        }
        .info-list {
            list-style: none;
            padding: 0;
        }
        .info-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-list li:last-child {
            border-bottom: none;
        }
        .highlight {
            background: #fff3cd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .time-info {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⏰ Aqrar Portal</h1>
        <h2>Təlim Xatırlatması</h2>
    </div>
    
    <div class="content">
        <div class="reminder-badge">🔔 3 SAAT QALDI</div>
        
        <p><strong>Hörmətli {{ $user->first_name }} {{ $user->last_name }},</strong></p>
        
        <p>Sizi yaxınlaşan təlim haqqında xatırlatmaq istəyirik:</p>
        
        <div class="training-info">
            <h3>📚 {{ $training->title }}</h3>
            <p><strong>📝 Təsvir:</strong> {{ $training->description }}</p>
            <p><strong>👨‍🏫 Təlimçi:</strong> {{ $training->trainer->first_name ?? 'Təyin edilməyib' }} {{ $training->trainer->last_name ?? '' }}</p>
            <p><strong>📅 Tarix:</strong> {{ \Carbon\Carbon::parse($training->start_date)->format('d.m.Y') }}</p>
            <p><strong>⏰ Başlama vaxtı:</strong> {{ $training->start_time ? \Carbon\Carbon::parse($training->start_time)->format('H:i') : 'Təyin edilməyib' }}</p>
            <p><strong>⏰ Bitmə vaxtı:</strong> {{ $training->end_time ? \Carbon\Carbon::parse($training->end_time)->format('H:i') : 'Təyin edilməyib' }}</p>
            <p><strong>🌐 Platforma:</strong> Online (Google Meet)</p>
            @if($training->has_certificate)
                <p><strong>🏆 Sertifikat:</strong> Bəli, təlim sonunda sertifikat veriləcək</p>
            @endif
            @if($training->is_recurring)
                <p><strong>🔄 Təkrarlanan:</strong> Bəli, {{ ucfirst($training->recurrence_frequency) }} təkrarlanır</p>
            @endif
        </div>
        
        <div class="time-info">
            <p><strong>⏰ Təlim başlama vaxtı:</strong> {{ \Carbon\Carbon::parse($training->start_date . ' ' . $training->start_time)->format('d.m.Y H:i') }}</p>
            <p><strong>⏰ Qalan vaxt:</strong> 3 saat</p>
        </div>
        
        @if($training->google_meet_link)
        <div class="highlight">
            <p><strong>🎥 Google Meet Linki:</strong></p>
            <p>Bu təlim Google Meet platformasında keçiriləcək. Aşağıdakı linkə klik edərək təlimə qoşula bilərsiniz.</p>
        </div>
        @endif
        
        <p><strong>Qeydiyyat üçün seçimlər:</strong></p>
        <p>1. <strong>Birbaşa qeydiyyat:</strong> Aşağıdakı düyməyə klik edərək qeydiyyatdan keçin</p>
        <p>2. <strong>Vebsayt vasitəsilə:</strong> <a href="{{ config('app.url') }}/trainings">Aqrar Portal - Təlimlər</a> bölməsindən qeydiyyatdan keçin</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/trainings/{{ $training->id }}/register" class="button">📝 Qeydiyyatdan Keç</a>
            @if($training->google_meet_link)
                <a href="{{ $training->google_meet_link }}" class="button meet-button">🎥 Google Meet</a>
            @endif
        </div>
        
        <p><strong>Əhəmiyyətli məlumatlar:</strong></p>
        <ul class="info-list">
            <li>📱 Təlim online formatda keçiriləcək</li>
            @if($training->google_meet_link)
                <li>🎥 Google Meet platforması istifadə olunacaq</li>
                <li>💻 Kompüter, planşet və ya telefon ilə iştirak edə bilərsiniz</li>
            @endif
            <li>📚 Təlim materialları təlim başlamazdan əvvəl paylaşılacaq</li>
            @if($training->has_certificate)
                <li>🏆 Təlimi tamamladıqdan sonra sertifikat alacaqsınız</li>
            @endif
            <li>❓ Suallarınız varsa, dəstək komandamızla əlaqə saxlayın</li>
        </ul>
        
        <div class="highlight">
            <p><strong>💡 Son hazırlıq məsləhətləri:</strong></p>
            <ul>
                <li>İnternet bağlantınızın sabit olduğundan əmin olun</li>
                <li>Mikrofon və kamera işləyir olduğunu yoxlayın</li>
                <li>Təlim vaxtından 5-10 dəqiqə əvvəl qoşulun</li>
                <li>Telefon və ya digər məlumatları hazırlayın</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>Bu e-poçt Aqrar Portal tərəfindən avtomatik göndərilmişdir.</p>
        <p>Əgər bu e-poçtu səhvən almısınızsa, onu silə bilərsiniz.</p>
        <p>© {{ date('Y') }} Aqrar Portal. Bütün hüquqlar qorunur.</p>
    </div>
</body>
</html>





