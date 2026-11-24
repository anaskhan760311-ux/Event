@echo off
cd /d "C:\wamp64\www\Events"
C:\wamp64\bin\mysql\mysql8.4.7\bin\mysql.exe -u root < setup_database.sql
pause
