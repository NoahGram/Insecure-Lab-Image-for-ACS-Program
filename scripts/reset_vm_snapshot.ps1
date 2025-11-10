# Automated VM Reset via Snapshot Restore
Set-Location -Path (Split-Path -Parent $PSScriptRoot)

if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    exit 1
}

$envScript = python load_env.py powershell
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to load .env" -ForegroundColor Red
    exit 1
}
Invoke-Expression ($envScript -join "`n")

Write-Host "============================================" -ForegroundColor Cyan
Write-Host " VM Snapshot Reset" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Platform: $env:VM_PLATFORM | VM: $env:VM_NAME | Snapshot: $env:VM_SNAPSHOT_BASE" -ForegroundColor Yellow
Write-Host ""

# Vagrant
if ($env:VM_PLATFORM -eq "vagrant") {
    Write-Host "Vagrant Platform Detected" -ForegroundColor Green
    
    # Check if vagrant is installed
    $vagrantCmd = Get-Command vagrant -ErrorAction SilentlyContinue
    if (-not $vagrantCmd) {
        Write-Host "ERROR: Vagrant not installed or not in PATH" -ForegroundColor Red
        Write-Host "Install from: https://www.vagrantup.com/downloads" -ForegroundColor Yellow
        exit 1
    }
    
    # Set working directory if specified
    if ($env:VAGRANT_CWD) {
        Set-Location $env:VAGRANT_CWD
    }
    
    Write-Host "Restoring Vagrant snapshot: $env:VM_SNAPSHOT_BASE" -ForegroundColor Yellow
    # Ensure VM is halted before restoring to avoid VirtualBox storage mismatches
    try {
        Write-Host "Halting VM (if running) before restore..." -ForegroundColor Gray
        & vagrant halt 2>$null
    } catch {
        Write-Host "Warning: vagrant halt returned an error or VM already halted. Proceeding to restore..." -ForegroundColor Yellow
    }

    & vagrant snapshot restore $env:VM_SNAPSHOT_BASE --no-provision
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to restore Vagrant snapshot" -ForegroundColor Red
        exit 1
    }

    # Start VM after restore
    Write-Host "Starting VM after snapshot restore..." -ForegroundColor Gray
    & vagrant up --no-provision
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to start VM after snapshot restore" -ForegroundColor Red
        exit 1
    }
}
# VirtualBox
elseif ($env:VM_PLATFORM -eq "virtualbox") {
    $vbox = "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe"
    if (-not (Test-Path $vbox)) {
        Write-Host "ERROR: VBoxManage.exe not found" -ForegroundColor Red
        exit 1
    }
    
    Write-Host "Stopping VM..." -ForegroundColor Yellow
    & $vbox controlvm "$env:VM_NAME" poweroff 2>$null
    Start-Sleep -Seconds 3
    
    Write-Host "Restoring snapshot..." -ForegroundColor Yellow
    & $vbox snapshot "$env:VM_NAME" restore "$env:VM_SNAPSHOT_BASE"
    if ($LASTEXITCODE -ne 0) { exit 1 }
    
    Write-Host "Starting VM..." -ForegroundColor Yellow
    & $vbox startvm "$env:VM_NAME" --type headless
    if ($LASTEXITCODE -ne 0) { exit 1 }
}

Write-Host ""
Write-Host "Waiting for VM to boot (30 seconds)..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

Write-Host "Testing SSH connection..." -ForegroundColor Yellow
$attempts = 0
$maxAttempts = 12
$connected = $false

while (($attempts -lt $maxAttempts) -and (-not $connected)) {
    $attempts++
    Write-Host "  Attempt $attempts/$maxAttempts..." -ForegroundColor Gray
    
    $null = & "$PSScriptRoot\test_connection.ps1" 2>&1
    if ($LASTEXITCODE -eq 0) {
        $connected = $true
        Write-Host " SSH Connected!" -ForegroundColor Green
        break
    }
    Start-Sleep -Seconds 5
}

if (-not $connected) {
    Write-Host "ERROR: SSH connection failed" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host " VM RESET COMPLETE" -ForegroundColor Green
Write-Host "Ready for deployment!" -ForegroundColor Cyan
