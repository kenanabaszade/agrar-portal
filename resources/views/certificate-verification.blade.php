<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Doğrulama - Aqrar Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            width: 100%;
            text-align: center;
        }

        .header {
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .title {
            color: #333;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
        }

        .certificate-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin: 30px 0;
            border-left: 5px solid #28a745;
        }

        .status {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .certificate-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .detail-item {
            text-align: left;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #333;
            font-size: 16px;
            font-weight: 600;
            word-break: break-all;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .pdf-section {
            margin: 30px 0;
            padding: 20px;
            background: #e3f2fd;
            border-radius: 15px;
        }

        .pdf-title {
            color: #1976d2;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .pdf-embed {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .download-btn {
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 20px 0;
            transition: transform 0.2s, background-color 0.2s;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            background: #218838;
        }

        .verification-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .verification-title {
            color: #856404;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .verification-text {
            color: #856404;
            font-size: 14px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .certificate-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ asset('images/logo.png') }}" alt="Aqrar Portal Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display: none; width: 80px; height: 80px; background: #28a745; border-radius: 50%; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">AP</div>
            </div>
            <h1 class="title">Sertifikat Doğrulama</h1>
            <p class="subtitle">Aqrar Portal - Rəsmi Sertifikat Doğrulama Sistemi</p>
        </div>

        <div class="certificate-info">
            <div class="status">✓ DOĞRULANDI</div>
            
            <div class="certificate-details">
                <div class="detail-item">
                    <div class="detail-label">Sertifikat Nömrəsi</div>
                    <div class="detail-value" id="certificate-number">{{ $certificate->certificate_number ?? 'N/A' }}</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Rəqəmsal İmza (SHA-256)</div>
                    <div class="detail-value" id="digital-signature">{{ $certificate->digital_signature ?? 'N/A' }}</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Buraxılış Tarixi</div>
                    <div class="detail-value" id="issue-date">{{ $certificate->issue_date ?? 'N/A' }}</div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value" id="status">{{ $certificate->status ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="pdf-section">
            <h3 class="pdf-title">Sertifikat PDF-i</h3>
            <iframe 
                src="{{ $certificate->pdf_url ?? '#' }}" 
                class="pdf-embed"
                title="Sertifikat PDF">
            </iframe>
            
            <a href="{{ $certificate->pdf_url ?? '#' }}" class="download-btn" download>
                📥 PDF-i Yüklə
            </a>
        </div>

        <div class="verification-info">
            <div class="verification-title">🔒 Təhlükəsizlik Məlumatı</div>
            <div class="verification-text">
                Bu sertifikat rəqəmsal imza ilə təhlükəsiz şəkildə doğrulanmışdır. 
                Sertifikatın həqiqiliyi SHA-256 alqoritmi ilə təsdiqlənir.
            </div>
        </div>

        <div class="footer">
            <p>© 2025 Aqrar Portal. Bütün hüquqlar qorunur.</p>
            <p>Doğrulama tarixi: {{ now()->format('d.m.Y H:i') }}</p>
        </div>
    </div>
</body>
</html>