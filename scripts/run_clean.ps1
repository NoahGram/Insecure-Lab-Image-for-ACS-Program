# Clean Deployment Script
# Deploys clean base lab environment with all services  
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

# Regenerate inventory from .env so users only need to edit .env
Write-Host "Generating Ansible inventory from .env..." -ForegroundColor Gray
python generate_inventory.py
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to generate inventory.ini from .env" -ForegroundColor Red
    exit 1
}

Write-Host "Starting Clean Lab Deployment..." -ForegroundColor Cyan
Write-Host "Running Ansible Playbook: site_clean.yml" -ForegroundColor Yellow
Write-Host ""

# Build docker command using .env variables
$repoPath = $env:ANSIBLE_CONTROL_NODE_PATH -replace '\\','/'
$mountPoint = if ($env:DOCKER_MOUNT_POINT) { $env:DOCKER_MOUNT_POINT } else { '/ansible' }
$dockerImage = if ($env:DOCKER_IMAGE_NAME) { $env:DOCKER_IMAGE_NAME } else { 'ansible-control-node' }
$inventoryFile = if ($env:ANSIBLE_INVENTORY_FILE) { $env:ANSIBLE_INVENTORY_FILE } else { 'inventory.ini' }
$keyPath = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }

$shellCmd = "chmod 600 $mountPoint/$keyPath && ansible-playbook $mountPoint/playbooks/site_clean.yml -i $mountPoint/$inventoryFile"
$volumeMount = "${repoPath}:${mountPoint}"
$dockerCmd = "docker run --rm -v `"$volumeMount`" -e ANSIBLE_ROLES_PATH=$mountPoint/playbooks/roles $dockerImage sh -c `"$shellCmd`""

Write-Host "Executing deployment..." -ForegroundColor Yellow
Write-Host ""

Invoke-Expression $dockerCmd
