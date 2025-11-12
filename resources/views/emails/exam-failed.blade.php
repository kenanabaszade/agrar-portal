<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan nəticəsi</title>
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
            background-color: #dc3545;
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
        .info-icon {
            font-size: 48px;
            color: #dc3545;
            text-align: center;
            margin-bottom: 20px;
        }
        .exam-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .encouragement {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #0056b3;
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
        <h1>İmtahan Nəticəsi</h1>
        <h2>İmtahanı keçə bilmədiniz</h2>
    </div>
    
    <div class="content">
        <div class="info-icon">📝</div>
        
        <p>Salam {{ $user->first_name }} {{ $user->last_name }},</p>
        
        <p>Təəssüf ki, <strong>{{ $exam->title }}</strong> imtahanını keçə bilmədiniz.</p>
        
        <div class="exam-details">
            <h3>İmtahan Məlumatları:</h3>
            <ul>
                <li><strong>İmtahan:</strong> {{ $exam->title }}</li>
                <li><strong>Bal:</strong> {{ $registration->score }}%</li>
                <li><strong>Keçid balı:</strong> {{ $exam->passing_score }}%</li>
                <li><strong>Tamamlama tarixi:</strong> {{ $registration->finished_at->format('d.m.Y H:i') }}</li>
            </ul>
        </div>
        
        <div class="encouragement">
            <h3>💪 Umidinizi itirməyin!</h3>
            <p>İmtahanı keçə bilməmək normal haldır. Əsas məqsəd öyrənməkdir. Aşağıdakı addımları atın:</p>
            <ul>
                <li>Zəif olduğunuz mövzuları yenidən oxuyun</li>
                <li>Əlavə məşq edin</li>
                <li>Mümkün olduqda yenidən cəhd edin</li>
            </ul>
        </div>
        
        <p>Uğurlarınızın davamını arzulayırıq!</p>
        
        <div class="footer">
            <p>Bu e-poçt avtomatik olaraq göndərilmişdir. Cavab verməyinizə ehtiyac yoxdur.</p>
            <p>© {{ date('Y') }} Aqrar Portal</p>
        </div>
    </div>
</body>
</html>






