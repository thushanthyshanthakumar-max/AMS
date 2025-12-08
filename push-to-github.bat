@echo off
echo ========================================
echo GitHub Push Helper for AMS
echo ========================================
echo.
echo This script will help you push your code to GitHub.
echo.
echo STEP 1: Create a Personal Access Token
echo ----------------------------------------
echo 1. Go to: https://github.com/settings/tokens
echo 2. Click "Generate new token (classic)"
echo 3. Name: AMS Deployment
echo 4. Check the "repo" checkbox
echo 5. Click "Generate token"
echo 6. Copy the token (starts with ghp_)
echo.
echo STEP 2: Enter Your Token
echo ----------------------------------------
set /p TOKEN="Paste your GitHub token here and press Enter: "
echo.
echo Pushing to GitHub...
echo.

git push https://%TOKEN%@github.com/thushanthyshanthakumar-max/ams.git main

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo SUCCESS! Your code is now on GitHub!
    echo ========================================
    echo.
    echo Next step: Deploy to Railway
    echo 1. Go to https://railway.app
    echo 2. Login with GitHub
    echo 3. Click "New Project" - "Deploy from GitHub repo"
    echo 4. Select your "ams" repository
    echo 5. Add MySQL database
    echo.
    echo See RAILWAY_DEPLOYMENT.md for detailed instructions.
    echo.
) else (
    echo.
    echo ========================================
    echo PUSH FAILED
    echo ========================================
    echo.
    echo Possible issues:
    echo - Token is incorrect or expired
    echo - Token doesn't have "repo" scope
    echo - Repository doesn't exist
    echo.
    echo Please check GITHUB_AUTHENTICATION.md for help.
    echo.
)

pause
