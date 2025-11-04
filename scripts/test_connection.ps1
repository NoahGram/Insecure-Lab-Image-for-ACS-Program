# Test SSH Connection to VM
# This script loads .env and tests the SSH connection automatically

# Load environment variables from .env
if (-not (Test-Path ".env")) {
    Write-Host "ERROR: .env file not found!" -ForegroundColor Red
    Write-Host "Please create .env from .env.example" -ForegroundColor Yellow
    exit 1
}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Testing VM Connection" -ForegroundColor Cyan
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

Write-Host "Target Configuration (raw):" -ForegroundColor Yellow
Write-Host "  VM_PLATFORM: $env:VM_PLATFORM" -ForegroundColor Gray
Write-Host "  Host (raw): $env:VM1_HOSTNAME" -ForegroundColor Gray
Write-Host "  User (raw): $env:VM1_USERNAME" -ForegroundColor Gray
Write-Host "  Port (raw): $env:VM1_SSH_PORT" -ForegroundColor Gray
Write-Host ""

# Derive effective SSH connection parameters depending on platform
$sshUser = $env:VM1_USERNAME
$sshHost = $env:VM1_HOSTNAME
$sshPort = $env:VM1_SSH_PORT
$sshKey = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }

if ($env:VM_PLATFORM -and $env:VM_PLATFORM -eq 'vagrant') {
    # Vagrant VMs use the 'vagrant' user and host port forwarded to the host (use host.docker.internal)
    $sshUser = 'vagrant'
    # Inside Docker, use host.docker.internal to reach the host's forwarded ports
    if ($env:VM1_HOSTNAME) {
        # prefer the configured host if it's host.docker.internal already
        $sshHost = $env:VM1_HOSTNAME
    } else {
        $sshHost = 'host.docker.internal'
    }
    # default Vagrant forwarded SSH port is 2222 if not set
    if (-not $sshPort) { $sshPort = '2222' }
    # Use configured key path if present; provisioner installs public key into vagrant/root
    $sshKey = if ($env:VM1_SSH_KEY_PATH) { $env:VM1_SSH_KEY_PATH } else { 'Keys/vps_key' }
}

Write-Host "Effective SSH connection:" -ForegroundColor Yellow
Write-Host "  Host: $sshHost" -ForegroundColor Gray
Write-Host "  User: $sshUser" -ForegroundColor Gray
Write-Host "  Port: $sshPort" -ForegroundColor Gray
Write-Host "  Key: $sshKey" -ForegroundColor Gray
Write-Host ""

# Normalize path for Docker
$repoPath = $env:ANSIBLE_CONTROL_NODE_PATH -replace '\\','/'
$mountPoint = if ($env:DOCKER_MOUNT_POINT) { $env:DOCKER_MOUNT_POINT } else { '/ansible' }
$dockerImage = if ($env:DOCKER_IMAGE_NAME) { $env:DOCKER_IMAGE_NAME } else { 'ansible-control-node' }

# Use the derived effective SSH values when building the docker ssh command
$dockerCmd = "docker run --rm -v `"$repoPath`:$mountPoint`" $dockerImage sh -c `"chmod 600 $mountPoint/$sshKey && ssh -o StrictHostKeyChecking=no -i $mountPoint/$sshKey -p $sshPort $sshUser@$sshHost 'echo Connection successful'`""

Write-Host "Executing test connection..." -ForegroundColor Yellow
Write-Host $dockerCmd -ForegroundColor Gray
Write-Host ""

Invoke-Expression $dockerCmd

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Green
    Write-Host " SUCCESS! VM is reachable" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Red
    Write-Host " FAILED! Could not connect to VM" -ForegroundColor Red
    Write-Host "============================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Troubleshooting:" -ForegroundColor Yellow
    Write-Host "  1. Check VM is running" -ForegroundColor Gray
    Write-Host "  2. Verify .env settings are correct" -ForegroundColor Gray
    Write-Host "  3. Check SSH key exists at: $env:VM1_SSH_KEY_PATH" -ForegroundColor Gray
    Write-Host "  4. Verify port forwarding for port $env:VM1_SSH_PORT" -ForegroundColor Gray
}
