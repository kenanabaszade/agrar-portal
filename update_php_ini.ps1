# PowerShell script to update php.ini upload limits
# Run this script as Administrator

$phpIniPath = "C:\Program Files\php-8.3.26-nts-Win32-vs16-x64\php.ini"

if (-not (Test-Path $phpIniPath)) {
    Write-Host "php.ini file not found at: $phpIniPath" -ForegroundColor Red
    Write-Host "Please update the path in this script." -ForegroundColor Yellow
    exit 1
}

Write-Host "Reading php.ini file..." -ForegroundColor Cyan
$content = Get-Content $phpIniPath -Raw

# Backup original file
$backupPath = $phpIniPath + ".backup." + (Get-Date -Format "yyyyMMdd_HHmmss")
Copy-Item $phpIniPath $backupPath
Write-Host "Backup created: $backupPath" -ForegroundColor Green

# Update post_max_size
$content = $content -replace '(?m)^(;?\s*post_max_size\s*=\s*)([0-9]+[KMGT]?)(.*)$', '$1110M$3'
if ($content -notmatch 'post_max_size\s*=\s*110M') {
    # If not found, add it
    $content = $content -replace '(?m)^(;?\s*post_max_size\s*=)', 'post_max_size = 110M'
}

# Update upload_max_filesize
$content = $content -replace '(?m)^(;?\s*upload_max_filesize\s*=\s*)([0-9]+[KMGT]?)(.*)$', '$1105M$3'
if ($content -notmatch 'upload_max_filesize\s*=\s*105M') {
    # If not found, add it
    $content = $content -replace '(?m)^(;?\s*upload_max_filesize\s*=)', 'upload_max_filesize = 105M'
}

# Update memory_limit
$content = $content -replace '(?m)^(;?\s*memory_limit\s*=\s*)([0-9]+[KMGT]?)(.*)$', '$1512M$3'

# Update max_execution_time
$content = $content -replace '(?m)^(;?\s*max_execution_time\s*=\s*)([0-9]+)(.*)$', '$1600$3'

# Update max_input_time
$content = $content -replace '(?m)^(;?\s*max_input_time\s*=\s*)([0-9]+)(.*)$', '$1600$3'

# Write updated content
Set-Content -Path $phpIniPath -Value $content -Force

Write-Host "`nphp.ini updated successfully!" -ForegroundColor Green
Write-Host "Changes made:" -ForegroundColor Cyan
Write-Host "  - post_max_size = 110M" -ForegroundColor Yellow
Write-Host "  - upload_max_filesize = 105M" -ForegroundColor Yellow
Write-Host "  - memory_limit = 512M" -ForegroundColor Yellow
Write-Host "  - max_execution_time = 600" -ForegroundColor Yellow
Write-Host "  - max_input_time = 600" -ForegroundColor Yellow
Write-Host "`nPlease restart your PHP server (Apache/Laravel server) for changes to take effect." -ForegroundColor Magenta

