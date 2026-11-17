@echo off
title YukSehat Chatbot AI Server (Node.js)
echo ===================================================
echo   Menjalankan YukSehat Chatbot Server (Node.js)
echo ===================================================
cd /d "%~dp0"

where node >nul 2>nul
if %errorlevel% equ 0 (
    echo Menggunakan Node.js sistem...
    node server.js
) else if exist "%~dp0node.exe" (
    echo Menggunakan Node.js lokal...
    "%~dp0node.exe" server.js
) else (
    echo [ERROR] node.exe tidak ditemukan!
    pause
)
