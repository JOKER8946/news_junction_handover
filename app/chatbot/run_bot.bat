@echo off
echo Starting automated chatbot - runs every 30 seconds
echo Press Ctrl+C to stop the bot
echo.

start "ComfyUI" /B python C:\Users\saiga\ComfyUI\main.py --cpu

:loop
echo %date% %time%: Running bot...
C:\xampp\php\php.exe C:\xampp\htdocs\chatbot1\index.php

if %errorlevel% equ 0 (
    echo %date% %time%: Bot completed successfully
) else (
    echo %date% %time%: Bot failed with exit code %errorlevel%
)

echo Waiting 30 seconds before next run...
timeout /t 30 /nobreak >nul
goto loop 