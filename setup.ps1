# Setup Script for Ansible Control Node
# Run this first to configure your environment

Write-Host "Setting up Ansible Control Node Environment..." -ForegroundColor Cyan

# Check PowerShell execution policy
$currentPolicy = Get-ExecutionPolicy
Write-Host "Current PowerShell execution policy: $currentPolicy" -ForegroundColor Yellow

if ($currentPolicy -eq "Restricted" -or $currentPolicy -eq "AllSigned") {
    Write-Host "PowerShell execution policy needs to be changed to run scripts." -ForegroundColor Red
    Write-Host "Run this command as Administrator and then re-run this setup:" -ForegroundColor Yellow
    Write-Host "Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Or run scripts directly with:" -ForegroundColor Yellow
    Write-Host "PowerShell -ExecutionPolicy Bypass -File .\scripts\run_clean.ps1" -ForegroundColor Cyan
    Write-Host ""
    Read-Host "Press Enter to continue with environment checks"
}

# Check Docker
Write-Host "Checking Docker..." -ForegroundColor Yellow
try {
    $dockerVersion = docker --version
    Write-Host "Docker found: $dockerVersion" -ForegroundColor Green
} catch {
    Write-Host "Docker not found or not running" -ForegroundColor Red
    Write-Host "Please install Docker Desktop and ensure it's running" -ForegroundColor Yellow
}

# Check VirtualBox
Write-Host "Checking VirtualBox..." -ForegroundColor Yellow
$vboxPath = "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe"
if (Test-Path $vboxPath) {
    Write-Host "VirtualBox found" -ForegroundColor Green
} else {
    Write-Host "VirtualBox not found at expected location" -ForegroundColor Red
    Write-Host "Please install VirtualBox or update the path in scripts" -ForegroundColor Yellow
}

# Check SSH keys
Write-Host "Checking SSH keys..." -ForegroundColor Yellow
if (Test-Path "Keys\vps_key") {
    Write-Host "SSH private key found" -ForegroundColor Green
} else {
    Write-Host "SSH private key not found in Keys\vps_key" -ForegroundColor Red
    Write-Host "Please ensure SSH keys are properly configured" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Setup Complete! Available commands:" -ForegroundColor Cyan
Write-Host "Clean deployment: .\scripts\run_clean.ps1" -ForegroundColor White
Write-Host "Vulnerable deployment: .\scripts\run_vulnerable.ps1" -ForegroundColor White  
Write-Host "Fresh install test: .\scripts\fresh_install_test.ps1" -ForegroundColor White
Write-Host "Profile deployment: .\scripts\run_vulnerability_profile.ps1" -ForegroundColor White
Write-Host ""
Write-Host "See scripts\README.md for detailed documentation" -ForegroundColor Yellow