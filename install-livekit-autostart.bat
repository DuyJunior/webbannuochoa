@echo off
chcp 65001 >nul
echo ===================================================================
echo   Cai dat LiveKit Server tu dong khoi dong cung Windows
echo ===================================================================
copy /Y "%~dp0scratch\livekit\start-livekit-silent.vbs" "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\livekit-autostart.vbs"
if %ERRORLEVEL% EQU 0 (
    echo [OK] Da cai dat thanh cong!
    echo LiveKit Server se tu dong chay ngam moi khi ban bat may tinh.
) else (
    echo [LOI] Khong the ghi file vao thu muc Startup.
)
echo.
pause
