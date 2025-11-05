# Run Ansible Playbook
# This script loads .env and runs an Ansible playbook using your configured settings
# Usage: .\run_playbook.ps1 [playbook_path]

param(
    [string]$Playbook = "playbooks/site_clean.yml"
)

# Load environment variables from .env
if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Please create .env from .env.example" -ForegroundColor Yellow
    exit 1
}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Ansible Playbook Runner" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Load .env variables
$envScript = python load_env.py powershell
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to load .env" -ForegroundColor Red
    exit 1
}
# Join array lines into single script and execute
$envScript = $envScript -join "`n"
Invoke-Expression $envScript

Write-Host "Configuration:" -ForegroundColor Yellow
Write-Host "  Repository: $env:ANSIBLE_CONTROL_NODE_PATH" -ForegroundColor Gray
Write-Host "  Playbook: $Playbook" -ForegroundColor Gray
Write-Host "  Target: $env:VM1_USERNAME@$env:VM1_HOSTNAME:$env:VM1_SSH_PORT" -ForegroundColor Gray
Write-Host ""

# Normalize path for Docker
$repoPath = $env:ANSIBLE_CONTROL_NODE_PATH -replace '\\','/'
# Regenerate inventory from .env so the inventory always matches .env edits
Write-Host "Generating Ansible inventory from .env..." -ForegroundColor Gray
python generate_inventory.py
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to generate inventory.ini from .env" -ForegroundColor Red
    exit 1
}
$mountPoint = if ($env:DOCKER_MOUNT_POINT) { $env:DOCKER_MOUNT_POINT } else { '/ansible' }
$inventoryFile = if ($env:ANSIBLE_INVENTORY_FILE) { $env:ANSIBLE_INVENTORY_FILE } else { 'inventory.ini' }
$keyPath = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }
$dockerImage = if ($env:DOCKER_IMAGE_NAME) { $env:DOCKER_IMAGE_NAME } else { 'ansible-control-node' }

# Build docker command
$dockerCmd = "docker run --rm -v `"$repoPath`:$mountPoint`" $dockerImage sh -c `"chmod 600 $mountPoint/$keyPath && ansible-playbook $mountPoint/$Playbook -i $mountPoint/$inventoryFile`""

Write-Host "Executing playbook..." -ForegroundColor Yellow
Write-Host $dockerCmd -ForegroundColor Gray
Write-Host ""

Invoke-Expression $dockerCmd

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Green
    Write-Host " Playbook completed successfully!" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Red
    Write-Host " Playbook execution failed!" -ForegroundColor Red
    Write-Host "============================================" -ForegroundColor Red
    exit $LASTEXITCODE
}
