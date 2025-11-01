<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İmtahan nəticəsi gözləyir</title>
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
            background-color: #ffc107;
            color: #212529;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .wait-icon {
            font-size: 48px;
            color: #ffc107;
            text-align: center;
            margin-bottom: 20px;
        }
        .exam-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .info-section {
            background-color: #d1ecf1;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
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
        <h1>İmtahan Nəticəsi Gözləyir</h1>
        <h2>Mütəxəssis dəyərləndirməsi</h2>
    </div>
    
    <div class="content">
        <div class="wait-icon">⏳</div>
        
        <p>Salam {{ $user->first_name }} {{ $user->last_name }},</p>
        
        <p><strong>{{ $exam->title }}</strong> imtahanınızı tamamladınız. İmtahanınızda açıq suallar olduğu üçün nəticəniz mütəxəssislər tərəfindən dəyərləndirilir.</p>
        
        <div class="exam-details">
            <h3>İmtahan Məlumatları:</h3>
            <ul>
                <li><strong>İmtahan:</strong> {{ $exam->title }}</li>
                <li><strong>Avtomatik bal:</strong> {{ $registration->auto_graded_score ?? $registration->score }}%</li>
                <li><strong>Keçid balı:</strong> {{ $exam->passing_score }}%</li>
                <li><strong>Tamamlama tarixi:</strong> {{ $registration->finished_at->format('d.m.Y H:i') }}</li>
            </ul>
        </div>
        
        <div class="info-section">
            <h3>📋 Nə baş verir?</h3>
            <p>İmtahanınızda açıq tipli suallar var idi. Bu sualların cavabları mütəxəssislər tərəfindən dəyərləndirilir və nəticəniz yaxın günlər ərzində sizə bildiriləcək.</p>
            
            <h3>⏰ Nə vaxt nəticə alacağam?</h3>
            <p>Adətən 1-3 iş günü ərzində nəticənizi alacaqsınız. Nəticə hazır olduqda sizə e-poçt vasitəsilə bildiriş göndəriləcək.</p>
        </div>
        
        <p>Gözlədiyiniz üçün təşəkkür edirik!</p>
        
        <div class="footer">
            <p>Bu e-poçt avtomatik olaraq göndərilmişdir. Cavab verməyinizə ehtiyac yoxdur.</p>
            <p>© {{ date('Y') }} Aqrar Portal</p>
        </div>
    </div>
</body>
</html>




