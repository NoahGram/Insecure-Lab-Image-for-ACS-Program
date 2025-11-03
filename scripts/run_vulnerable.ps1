# Vulnerable Deployment Script
# Deploys lab environment with vulnerabilities for security testing
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

Write-Host "⚠️ Starting Vulnerable Lab Deployment..." -ForegroundColor Red
Write-Host "📋 Running Ansible Playbook: site_vulnerable.yml (Role-Based)" -ForegroundColor Yellow

# Build docker command using .env variables
$repoPath = $env:ANSIBLE_CONTROL_NODE_PATH -replace '\\','/'
$mountPoint = if ($env:DOCKER_MOUNT_POINT) { $env:DOCKER_MOUNT_POINT } else { '/ansible' }
$dockerImage = if ($env:DOCKER_IMAGE_NAME) { $env:DOCKER_IMAGE_NAME } else { 'ansible-control-node' }
$inventoryFile = if ($env:ANSIBLE_INVENTORY_FILE) { $env:ANSIBLE_INVENTORY_FILE } else { 'inventory.ini' }
$keyPath = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }

# Build the shell command - use string concatenation to avoid PowerShell parsing issues
$shellCommand = 'chmod 600 ' + $mountPoint + '/' + $keyPath + ' && ansible-playbook ' + $mountPoint + '/playbooks/site_vulnerable.yml -i ' + $mountPoint + '/' + $inventoryFile
$dockerCmd = "docker run --rm -v `"$repoPath`:$mountPoint`" $dockerImage sh -c `"$shellCommand`""

Write-Host "Executing deployment..." -ForegroundColor Yellow
Write-Host $dockerCmd -ForegroundColor Gray
Write-Host ""

Invoke-Expression $dockerCmd
