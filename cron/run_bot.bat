@echo off
cd /D "%~dp0.."
php cron\daily_community_bot.php
pause
