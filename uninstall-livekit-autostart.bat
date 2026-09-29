@echo off
chcp 65001 >nul
echo ===================================================================
echo   Go bo tu dong khoi dong LiveKit Server cung Windows
echo ===================================================================
if exist "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\livekit-autostart.vbs" (
    del /F /Q "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\livekit-autostart.vbs"
    echo [OK] Da go bo thanh cong!
) else (
    echo [INFO] LiveKit chua duoc cai dat tu dong khoi dong.
)
echo.
pause
