# PHP Upload Limits Fix Script (PowerShell)
# Run as Administrator

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ERROR: Administrator privileges required!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please run PowerShell as Administrator:" -ForegroundColor Yellow
    Write-Host "  1. Right-click on PowerShell" -ForegroundColor Yellow
    Write-Host "  2. Select 'Run as Administrator'" -ForegroundColor Yellow
    Write-Host "  3. Run this script again" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "OR edit php.ini manually:" -ForegroundColor Yellow
    Write-Host "  1. Open: C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini" -ForegroundColor Yellow
    Write-Host "  2. Find: upload_max_filesize = 2M" -ForegroundColor Yellow
    Write-Host "  3. Change to: upload_max_filesize = 25M" -ForegroundColor Yellow
    Write-Host "  4. Find: post_max_size = 8M" -ForegroundColor Yellow
    Write-Host "  5. Change to: post_max_size = 30M" -ForegroundColor Yellow
    Write-Host "  6. Save and restart PHP server" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PHP Upload Limits Fix Script" -ForegroundColor Cyan
Write-Host "Running as Administrator ✓" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get php.ini path
$phpIniPath = (php --ini | Select-String "Loaded Configuration File").ToString()
$phpIniPath = $phpIniPath -replace "Loaded Configuration File:\s+", "" -replace "^\s+|\s+$", ""

Write-Host "PHP.ini location: $phpIniPath" -ForegroundColor Yellow
Write-Host ""

# Check if php.ini exists
if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERROR: php.ini file not found at: $phpIniPath" -ForegroundColor Red
    Write-Host "Please check your PHP installation." -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Create backup
$backupPath = "$phpIniPath.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Write-Host "Creating backup of php.ini..." -ForegroundColor Yellow
try {
    Copy-Item $phpIniPath $backupPath -ErrorAction Stop
    Write-Host "Backup created: $backupPath" -ForegroundColor Green
} catch {
    Write-Host "WARNING: Could not create backup (access denied)" -ForegroundColor Yellow
    Write-Host "Continuing anyway..." -ForegroundColor Yellow
}
Write-Host ""

# Show current settings
Write-Host "Current settings:" -ForegroundColor Yellow
Get-Content $phpIniPath | Select-String -Pattern "upload_max_filesize|post_max_size" | ForEach-Object { Write-Host $_.Line }
Write-Host ""

# Read php.ini content
Write-Host "Updating php.ini..." -ForegroundColor Yellow
$content = Get-Content $phpIniPath -Raw

# Replace upload_max_filesize (handles both commented and uncommented)
$content = $content -replace "(?m)^\s*;?\s*upload_max_filesize\s*=.*", "upload_max_filesize = 25M"

# Replace post_max_size (handles both commented and uncommented)
$content = $content -replace "(?m)^\s*;?\s*post_max_size\s*=.*", "post_max_size = 30M"

# Save updated content
try {
    Set-Content -Path $phpIniPath -Value $content -NoNewline -ErrorAction Stop
    Write-Host ""
    Write-Host "SUCCESS! php.ini has been updated." -ForegroundColor Green
} catch {
    Write-Host ""
    Write-Host "ERROR: Could not write to php.ini (access denied)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please edit php.ini manually:" -ForegroundColor Yellow
    Write-Host "  1. Open: $phpIniPath" -ForegroundColor Yellow
    Write-Host "  2. Find: upload_max_filesize = 2M" -ForegroundColor Yellow
    Write-Host "  3. Change to: upload_max_filesize = 25M" -ForegroundColor Yellow
    Write-Host "  4. Find: post_max_size = 8M" -ForegroundColor Yellow
    Write-Host "  5. Change to: post_max_size = 30M" -ForegroundColor Yellow
    Write-Host "  6. Save and restart PHP server" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host ""
Write-Host "New settings:" -ForegroundColor Yellow
Get-Content $phpIniPath | Select-String -Pattern "upload_max_filesize|post_max_size" | ForEach-Object { Write-Host $_.Line }
Write-Host ""
Write-Host "IMPORTANT: Please restart your PHP server (php artisan serve) for changes to take effect." -ForegroundColor Red
Write-Host ""
Read-Host "Press Enter to exit"

