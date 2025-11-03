# Fresh Install Test Script
# Resets VM and deploys clean environment for testing
# Automatically loads settings from .env

# Change to project root directory
Set-Location -Path (Split-Path -Parent $PSScriptRoot)

# Load .env variables
if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Please create .env from .env.example" -ForegroundColor Yellow
    exit 1
}

$envScript = python load_env.py powershell
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to load .env" -ForegroundColor Red
    exit 1
}
$envScript = $envScript -join "`n"
Invoke-Expression $envScript

Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Fresh Install Test Procedure" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Build docker command variables
$repoPath = $env:ANSIBLE_CONTROL_NODE_PATH -replace '\\','/'
$mountPoint = if ($env:DOCKER_MOUNT_POINT) { $env:DOCKER_MOUNT_POINT } else { '/ansible' }
$dockerImage = if ($env:DOCKER_IMAGE_NAME) { $env:DOCKER_IMAGE_NAME } else { 'ansible-control-node' }
$inventoryFile = if ($env:ANSIBLE_INVENTORY_FILE) { $env:ANSIBLE_INVENTORY_FILE } else { 'inventory.ini' }
$keyPath = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }
$volumeMount = "${repoPath}:${mountPoint}"

# Step 1: Reset VM to base snapshot
Write-Host "Step 1: Resetting VM to base snapshot..." -ForegroundColor Yellow
Write-Host ""

# Call snapshot reset script
& "$PSScriptRoot\reset_vm_snapshot.ps1"

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ Reset failed" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "✓ VM reset to clean base state" -ForegroundColor Green
Write-Host ""

# Step 2: Deploy fresh environment
Write-Host "Step 2: Deploying fresh environment..." -ForegroundColor Yellow
Write-Host ""
    
    $deployCmd = "chmod 600 $mountPoint/$keyPath && ansible-playbook $mountPoint/playbooks/site_clean.yml -i $mountPoint/$inventoryFile"
    docker run --rm -v $volumeMount -e ANSIBLE_ROLES_PATH=$mountPoint/playbooks/roles $dockerImage sh -c $deployCmd
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "============================================" -ForegroundColor Green
        Write-Host " Fresh Deployment Complete!" -ForegroundColor Green
        Write-Host "============================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "Services available:" -ForegroundColor Cyan
        Write-Host "  - Landing page: http://$env:VM1_HOSTNAME/" -ForegroundColor Gray
        Write-Host "  - Gitea: http://$env:VM1_HOSTNAME:3000" -ForegroundColor Gray
        Write-Host "  - Cockpit: https://$env:VM1_HOSTNAME:9090" -ForegroundColor Gray
    } else {
        Write-Host ""
        Write-Host "❌ Deployment failed" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host ""
    Write-Host "❌ Reset failed" -ForegroundColor Red
    exit 1
}