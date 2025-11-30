@echo off
echo Opening php.ini file...
echo.
echo NOTE: You need to edit this file manually:
echo   1. Find: upload_max_filesize = 2M
echo   2. Change to: upload_max_filesize = 25M
echo   3. Find: post_max_size = 8M
echo   4. Change to: post_max_size = 30M
echo   5. Save (Ctrl+S)
echo   6. Restart PHP server
echo.
pause

REM Get php.ini path
for /f "tokens=*" %%i in ('php --ini ^| findstr "Loaded Configuration File"') do set PHPINI=%%i
set PHPINI=%PHPINI:Loaded Configuration File:=%
set PHPINI=%PHPINI: =%

REM Open with default editor (requires Administrator)
start notepad "%PHPINI%"

echo.
echo php.ini file opened in Notepad.
echo Please edit and save, then restart PHP server.
pause



