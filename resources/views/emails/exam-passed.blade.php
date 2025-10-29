<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Təbriklər! İmtahanı keçdiniz</title>
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
            background-color: #28a745;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .success-icon {
            font-size: 48px;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
        .exam-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .certificate-section {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #218838;
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
        <h1>Təbriklər!</h1>
        <h2>İmtahanı uğurla keçdiniz</h2>
    </div>
    
    <div class="content">
        <div class="success-icon">🎉</div>
        
        <p>Salam {{ $user->first_name }} {{ $user->last_name }},</p>
        
        <p>Təbriklər! <strong>{{ $exam->title }}</strong> imtahanını uğurla keçdiniz.</p>
        
        <div class="exam-details">
            <h3>İmtahan Məlumatları:</h3>
            <ul>
                <li><strong>İmtahan:</strong> {{ $exam->title }}</li>
                <li><strong>Bal:</strong> {{ $registration->score }}%</li>
                <li><strong>Keçid balı:</strong> {{ $exam->passing_score }}%</li>
                <li><strong>Tamamlama tarixi:</strong> {{ $registration->finished_at->format('d.m.Y H:i') }}</li>
            </ul>
        </div>
        
        @if($certificate)
        <div class="certificate-section">
            <h3>📜 Sertifikatınız hazırdır!</h3>
            <p>Sizin üçün rəqəmsal sertifikat yaradıldı. Aşağıdakı linklərdən istifadə edərək sertifikatınızı yükləyə və yoxlaya bilərsiniz.</p>
            
            <a href="{{ $certificate->pdf_url }}" class="btn">📥 Sertifikatı Yüklə</a>
            <a href="{{ $certificate->verification_url }}" class="btn">🔍 Sertifikatı Yoxla</a>
            
            <p><strong>Sertifikat nömrəsi:</strong> {{ $certificate->certificate_number }}</p>
        </div>
        @endif
        
        <p>Yenidən təşəkkür edirik və uğurlarınızın davamını arzulayırıq!</p>
        
        <div class="footer">
            <p>Bu e-poçt avtomatik olaraq göndərilmişdir. Cavab verməyinizə ehtiyac yoxdur.</p>
            <p>© {{ date('Y') }} Aqrar Portal</p>
        </div>
    </div>
</body>
</html>

