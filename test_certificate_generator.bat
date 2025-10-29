@echo off
echo Testing Certificate Generator...

REM Test with proper JSON format for Windows
python certificate_generator.py "{\"user\":{\"id\":1,\"first_name\":\"Test\",\"last_name\":\"User\",\"email\":\"test@example.com\"},\"exam\":{\"id\":1,\"title\":\"Test Exam\",\"description\":\"Test Description\"},\"training\":{\"id\":1,\"title\":\"Test Training\",\"description\":\"Test Training Description\"}}"

if %errorlevel% neq 0 (
    echo.
    echo Python is not installed or not in PATH.
    echo Please install Python 3 from Microsoft Store or python.org
    echo.
    pause
    exit /b 1
)

echo.
echo Test completed!
pause

