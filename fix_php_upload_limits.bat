@echo off
echo ========================================
echo PHP Upload Limits Fix Script
echo ========================================
echo.

REM Get php.ini path
for /f "tokens=*" %%i in ('php --ini ^| findstr "Loaded Configuration File"') do set PHPINI=%%i
set PHPINI=%PHPINI:Loaded Configuration File:=%
set PHPINI=%PHPINI: =%

echo PHP.ini location: %PHPINI%
echo.

REM Check if php.ini exists
if not exist "%PHPINI%" (
    echo ERROR: php.ini file not found at: %PHPINI%
    echo Please check your PHP installation.
    pause
    exit /b 1
)

echo Creating backup of php.ini...
copy "%PHPINI%" "%PHPINI%.backup_%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%" >nul
echo Backup created successfully.
echo.

echo Current settings:
findstr /i "upload_max_filesize post_max_size" "%PHPINI%"
echo.

echo Updating php.ini...
echo.

REM Create temporary file
set TEMPFILE=%TEMP%\php_ini_temp_%RANDOM%.ini

REM Read php.ini and replace values
(
    for /f "usebackq delims=" %%a in ("%PHPINI%") do (
        set "line=%%a"
        setlocal enabledelayedexpansion
        set "line=!line!"
        
        REM Replace upload_max_filesize
        echo !line! | findstr /i /c:"upload_max_filesize" >nul
        if !errorlevel! equ 0 (
            REM Check if it's commented
            echo !line! | findstr /i /c:";" >nul
            if !errorlevel! equ 0 (
                REM Uncomment and set value
                echo upload_max_filesize = 25M
            ) else (
                REM Replace existing value
                echo upload_max_filesize = 25M
            )
        ) else (
            REM Replace post_max_size
            echo !line! | findstr /i /c:"post_max_size" >nul
            if !errorlevel! equ 0 (
                REM Check if it's commented
                echo !line! | findstr /i /c:";" >nul
                if !errorlevel! equ 0 (
                    REM Uncomment and set value
                    echo post_max_size = 30M
                ) else (
                    REM Replace existing value
                    echo post_max_size = 30M
                )
            ) else (
                REM Keep original line
                echo !line!
            )
        )
        endlocal
    )
) > "%TEMPFILE%"

REM This approach is complex, let's use PowerShell instead
del "%TEMPFILE%" 2>nul

echo.
echo Using PowerShell to update php.ini...
powershell -ExecutionPolicy Bypass -File "%~dp0fix_php_upload_limits.ps1"

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Failed to update php.ini.
    echo Please edit it manually:
    echo 1. Open: %PHPINI%
    echo 2. Find: upload_max_filesize
    echo 3. Set to: upload_max_filesize = 25M
    echo 4. Find: post_max_size
    echo 5. Set to: post_max_size = 30M
    echo 6. Save and restart PHP server
)

echo.
pause

