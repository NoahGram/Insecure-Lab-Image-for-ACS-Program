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

# Regenerate inventory from .env so users only need to edit .env
Write-Host "Generating Ansible inventory from .env..." -ForegroundColor Gray
python generate_inventory.py
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to generate inventory.ini from .env" -ForegroundColor Red
    exit 1
}

Write-Host "⚠️ Starting Vulnerable Lab Deployment..." -ForegroundColor Red
Write-Host "Running Ansible Playbook: site_vulnerable.yml" -ForegroundColor Yellow
Write-Host ""

# Build docker command using .env variables
$containerName = "ansible-container"
$repoPath = $env:ANSIBLE_CONTROL_NODE_PATH -replace '\\','/'
$mountPoint = if ($env:DOCKER_MOUNT_POINT) { $env:DOCKER_MOUNT_POINT } else { '/ansible' }
$dockerImage = if ($env:DOCKER_IMAGE_NAME) { $env:DOCKER_IMAGE_NAME } else { 'ansible-control-node' }
$inventoryFile = if ($env:ANSIBLE_INVENTORY_FILE) { $env:ANSIBLE_INVENTORY_FILE } else { 'inventory.ini' }
$keyPath = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }

# Determine container state and whether we need to stop it when finished
$containerExists = docker ps -a --format "{{.Names}}" |
    Where-Object { $_ -eq $containerName }
$containerRunning = docker ps --format "{{.Names}}" |
    Where-Object { $_ -eq $containerName }
$startedContainer = $false

if (-not $containerExists) {
    Write-Host "Ansible container not found. Creating ansible-container..." -ForegroundColor Yellow
    $volumeMount = "${repoPath}:${mountPoint}"
    $dockerCmd = "docker run -d --name $containerName -v `"$volumeMount`" -e ANSIBLE_ROLES_PATH=`"$mountPoint/playbooks/roles`" $dockerImage sh -c `"sleep infinity`""
    Invoke-Expression $dockerCmd
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to create ansible-container" -ForegroundColor Red
        exit 1
    }
    # The container was created (and started) by us
    $startedContainer = $true
    $containerRunning = $true
} elseif (-not $containerRunning) {
    Write-Host "Starting ansible-container..." -ForegroundColor Yellow
    docker start $containerName | Out-Null
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to start ansible-container" -ForegroundColor Red
        exit 1
    }
    # We started the container for this run
    $startedContainer = $true
}

$shellCmd = "cd $mountPoint && chmod 600 $keyPath && ansible-playbook playbooks/site_vulnerable.yml -i $inventoryFile -e vulnerability_profile=vulnerable_profile"
$volumeMount = "${repoPath}:${mountPoint}"
$dockerCmd = "docker exec $containerName sh -c `"$shellCmd`""

Write-Host "Executing deployment..." -ForegroundColor Yellow
Write-Host ""

try {
    Invoke-Expression $dockerCmd
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Deployment playbook failed" -ForegroundColor Red
        # Do not exit here; let finally stop the container if needed, then exit with non-zero
        $global:ScriptExitCode = 1
    } else {
        $global:ScriptExitCode = 0
    }
} finally {
    if ($startedContainer) {
        Write-Host "Stopping ansible-container..." -ForegroundColor Yellow
        docker stop $containerName | Out-Null
    }
}

if ($global:ScriptExitCode -ne 0) {
    exit $global:ScriptExitCode
}
