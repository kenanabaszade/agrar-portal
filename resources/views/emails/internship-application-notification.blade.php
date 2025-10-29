<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Staj Proqramı Müraciəti</title>
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
        .program-info {
            border-left-color: #e74c3c;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Yeni Staj Proqramı Müraciəti</h1>
    </div>
    
    <div class="content">
        <p>Salam,</p>
        
        <p><strong>{{ $user->first_name }} {{ $user->last_name }}</strong> adlı istifadəçi <strong>{{ $program->title }}</strong> staj proqramına müraciət etmişdir.</p>
        
        <div class="info-box user-info">
            <h3>İstifadəçi Məlumatları</h3>
            <p><strong>Ad Soyad:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
            <p><strong>E-mail:</strong> {{ $user->email }}</p>
            <p><strong>Telefon:</strong> {{ $user->phone ?? 'Təyin edilməyib' }}</p>
            <p><strong>Müraciət Tarixi:</strong> {{ $application->created_at->format('d.m.Y H:i') }}</p>
        </div>
        
        <div class="info-box program-info">
            <h3>Staj Proqramı Məlumatları</h3>
            <p><strong>Proqram Adı:</strong> {{ $program->title }}</p>
            <p><strong>Kateqoriya:</strong> {{ $program->category }}</p>
            <p><strong>Müddət:</strong> {{ $program->duration_weeks }} həftə</p>
            <p><strong>Başlama Tarixi:</strong> {{ $program->start_date->format('d.m.Y') }}</p>
            <p><strong>Məkan:</strong> {{ $program->location }}</p>
            <p><strong>Məktəbçi:</strong> {{ $program->instructor_name }}</p>
        </div>
        
        @if($application->cover_letter)
        <div class="info-box">
            <h3>Motivasiya Məktubu</h3>
            <p>{{ $application->cover_letter }}</p>
        </div>
        @endif
        
        <div class="info-box">
            <h3>CV Faylı</h3>
            <p><strong>Fayl Adı:</strong> {{ $application->cv_file_name }}</p>
            <p><strong>Fayl Ölçüsü:</strong> {{ $application->formatted_file_size }}</p>
            <p><strong>Fayl Tipi:</strong> {{ $application->cv_mime_type }}</p>
        </div>
        
        <p>CV faylı bu e-mailə əlavə edilmişdir. Müraciəti nəzərdən keçirmək üçün admin paneldən daxil olun.</p>
        
        <div class="footer">
            <p>Bu e-mail avtomatik olaraq göndərilmişdir. Cavab verməyin.</p>
            <p>&copy; {{ date('Y') }} Aqrar Portal. Bütün hüquqlar qorunur.</p>
        </div>
    </div>
</body>
</html>
