# Certificate PDF Generation System

Bu sistem imtahan bitdikdən sonra avtomatik olaraq PDF sertifikat yaradır və digital signature ilə doğrulama sistemi təmin edir.

## Xüsusiyyətlər

- ✅ İmtahan keçildikdə avtomatik PDF sertifikat yaradılması
- ✅ SHA256 digital signature ilə təhlükəsizlik
- ✅ QR kod ilə doğrulama
- ✅ Pixel-perfect HTML template-dən PDF yaradılması
- ✅ Public URL ilə sertifikat doğrulaması
- ✅ PDF download funksionallığı

## Quraşdırma

### Windows (Recommended)

1. **Python 3 Quraşdırın:**
   - Microsoft Store-dan quraşdırın (ən asan yol)
   - Və ya python.org-dan download edin

2. **Avtomatik Quraşdırma:**
   ```cmd
   setup_certificate_system.bat
   ```

3. **Test Edin:**
   ```cmd
   test_certificate_generator.bat
   ```

### Linux/macOS

### 1. Python Dependencies
```bash
pip3 install -r requirements.txt
```

### 2. Chrome/Chromium Quraşdırılması
PDF yaratmaq üçün Chrome lazımdır:

**Ubuntu/Debian:**
```bash
wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" | sudo tee /etc/apt/sources.list.d/google-chrome.list
sudo apt-get update
sudo apt-get install -y google-chrome-stable
```

**macOS:**
```bash
brew install --cask google-chrome
```

### 3. Database Migration
```bash
php artisan migrate
```

### 4. Avtomatik Quraşdırma
```bash
chmod +x setup_certificate_system.sh
./setup_certificate_system.sh
```

## İstifadə

### İmtahan Bitdikdən Sonra
İstifadəçi imtahanı keçdikdə sistem avtomatik olaraq:

1. Python script-i çağırır
2. HTML template-dən PDF yaradır
3. Digital signature yaradır
4. QR kod əlavə edir
5. Verilənlər bazasında sertifikat qeydiyyatı edir

### API Endpoints

**Sertifikat Doğrulaması (Public):**
```
GET /api/certificates/verify/{digital_signature}
```

**PDF Download (Public):**
```
GET /api/certificates/download/{digital_signature}
```

**PDF Yaradılması (Admin):**
```
POST /api/certificates/generate-pdf
{
    "user_id": 1,
    "exam_id": 1,
    "training_id": 1
}
```

## Fayl Strukturu

```
├── certificate_generator.py          # Python PDF generator
├── requirements.txt                   # Python dependencies
├── setup_certificate_system.sh       # Linux/macOS quraşdırma script-i
├── setup_certificate_system.bat      # Windows quraşdırma script-i
├── setup_certificate_system.ps1      # Windows PowerShell quraşdırma
├── test_certificate_generator.bat    # Windows test script-i
├── test_certificate_generator.ps1    # Windows PowerShell test
├── test_data.json                    # Test məlumatları
├── CERTIFICATE_SYSTEM_README.md      # Bu təlimat faylı
├── certificate/
│   ├── certificate.html             # HTML template
│   ├── image.png                    # Logo
│   ├── aqrar_logo_1.png
│   └── aqrar_logo_2.png
└── generated_certificates/          # Yaradılan PDF-lər
```

## Digital Signature

Hər sertifikat üçün unikal SHA256 hash yaradılır:
- User ID
- Exam ID  
- Exam Title
- User Name
- Timestamp

Bu hash URL-də istifadə olunur: `/certificates/verify/{hash}`

## QR Kod

QR kod verification URL-ini ehtiva edir və sertifikatın orijinallığını yoxlamaq üçün istifadə olunur.

## Təhlükəsizlik

- Digital signature ilə sertifikatın orijinallığı təmin edilir
- Public URL-lər authentication tələb etmir
- PDF faylları təhlükəsiz şəkildə saxlanılır

## Troubleshooting

### Windows

**Python Quraşdırılması:**
```cmd
# Microsoft Store-dan quraşdırın
# Və ya python.org-dan download edin
python --version
```

**Test Script:**
```powershell
powershell -ExecutionPolicy Bypass -File test_certificate_generator.ps1
```

**Manual Test:**
```cmd
python certificate_generator.py --file test_data.json
```

### Linux/macOS

### Python Script Xətası
```bash
# Chrome driver problemi
python3 certificate_generator.py '{"user":{"id":1,"first_name":"Test","last_name":"User","email":"test@example.com"},"exam":{"id":1,"title":"Test Exam","description":"Test Description"},"training":{"id":1,"title":"Test Training","description":"Test Training Description"}}'
```

### Chrome Quraşdırılması
Chrome quraşdırılmadıqda PDF yaradıla bilməz. Chrome-un quraşdırıldığını yoxlayın:
```bash
google-chrome --version
```

### Permissions
```bash
chmod +x certificate_generator.py
chmod +x setup_certificate_system.sh
```

## Logs

Sistem logları Laravel log fayllarında saxlanılır:
- `storage/logs/laravel.log`
- Python script xətaları da burada görünür
