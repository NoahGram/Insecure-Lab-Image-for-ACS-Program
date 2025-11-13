# Setup Script for Ansible Control Node
# Run this first to configure your environment

Write-Host "Setting up Ansible Control Node Environment..." -ForegroundColor Cyan
Write-Host ""

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

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Environment Checks" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Check Docker
Write-Host "Checking Docker..." -ForegroundColor Yellow
try {
    $dockerVersion = docker --version
    Write-Host "[OK] Docker found: $dockerVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Docker not found or not running" -ForegroundColor Red
    Write-Host "  Please install Docker Desktop and ensure it is running" -ForegroundColor Yellow
    Write-Host "  Download: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
}

# Check Vagrant
Write-Host "Checking Vagrant..." -ForegroundColor Yellow
try {
    $vagrantVersion = vagrant --version
    Write-Host "[OK] Vagrant found: $vagrantVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Vagrant not found" -ForegroundColor Red
    Write-Host "  Please install Vagrant for VM automation" -ForegroundColor Yellow
    Write-Host "  Download: https://www.vagrantup.com/downloads" -ForegroundColor Yellow
}

# Check VirtualBox
Write-Host "Checking VirtualBox..." -ForegroundColor Yellow
$vboxPath = "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe"
if (Test-Path $vboxPath) {
    try {
        $vboxVersion = & $vboxPath --version
        Write-Host "[OK] VirtualBox found: $vboxVersion" -ForegroundColor Green
    } catch {
        Write-Host "[OK] VirtualBox found" -ForegroundColor Green
    }
} else {
    Write-Host "[ERROR] VirtualBox not found at expected location" -ForegroundColor Red
    Write-Host "  Please install VirtualBox" -ForegroundColor Yellow
    Write-Host "  Download: https://www.virtualbox.org/wiki/Downloads" -ForegroundColor Yellow
}

Write-Host "Checking VMware Workstation..." -ForegroundColor Yellow
$vmwarePath = "C:\Program Files (x86)\VMware\VMware Workstation\vmrun.exe"
if (Test-Path $vmwarePath){
    try {
        Write-Host "[OK] VMware Workstation found." -ForegroundColor Green
    } catch {
        Write-Host "[OK] VMware Workstation found." -ForegroundColor Green
    }
} else {
    Write-Host "[WARN] VMware Workstation not found at expected location" -ForegroundColor Yellow
    Write-Host "  If you plan to use VMware, please install VMware Workstation" -ForegroundColor Yellow
    Write-Host "  Download: https://www.vmware.com/products/desktop-hypervisor/workstation-and-fusion" -ForegroundColor Yellow
}

# Check SSH keys
Write-Host "Checking SSH keys..." -ForegroundColor Yellow
if (Test-Path "Keys\vps_key") {
    Write-Host "[OK] SSH private key found" -ForegroundColor Green
} else {
    Write-Host "[WARN] SSH private key not found in Keys\vps_key" -ForegroundColor Yellow
    Write-Host "  Keys will be installed automatically by Vagrant" -ForegroundColor Gray
}

# Check .env file
Write-Host "Checking .env configuration..." -ForegroundColor Yellow
if (Test-Path ".env") {
    Write-Host "[OK] .env file found" -ForegroundColor Green
} else {
    Write-Host "[ERROR] .env file not found" -ForegroundColor Red
    Write-Host "  Please create .env from .env.example:" -ForegroundColor Yellow
    Write-Host "  Copy-Item .env.example .env" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Building Ansible Control Node" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Check if Dockerfile exists
if (!(Test-Path "Dockerfile")) {
    Write-Host "[ERROR] Dockerfile not found!" -ForegroundColor Red
    Write-Host "  Cannot build Docker image without Dockerfile" -ForegroundColor Yellow
    exit 1
}

# Build Docker image
Write-Host "Building Docker image ansible-control-node..." -ForegroundColor Yellow
Write-Host "This may take a few minutes..." -ForegroundColor Gray
Write-Host ""

try {
    docker build -t ansible-control-node .
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "[SUCCESS] Docker image built successfully!" -ForegroundColor Green
    } else {
        Write-Host ""
        Write-Host "[ERROR] Docker build failed!" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host ""
    Write-Host "[ERROR] Docker build failed: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host " Setup Complete!" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Create/edit .env file: Copy-Item .env.example .env" -ForegroundColor White
Write-Host "2. Set VM_PLATFORM=vagrant in .env" -ForegroundColor White
Write-Host "3. Create VM: vagrant up" -ForegroundColor White
Write-Host "    This will automatically use VirtualBox as VM provider." -ForegroundColor White
Write-Host "    If you want to use another provider, specify it with --provider flag. Like 'vagrant up --provider vmware_desktop'" -ForegroundColor White
Write-Host "    Make sure to install Vmware Vagrant Plugin: vagrant plugin install vagrant-vmware-desktop" -ForegroundColor White
Write-Host "4. Create snapshot: vagrant snapshot save base" -ForegroundColor White
Write-Host "5. Deploy lab: .\scripts\run_clean.ps1" -ForegroundColor White
Write-Host ""
Write-Host "Available Commands:" -ForegroundColor Cyan
Write-Host "  .\scripts\run_clean.ps1                       - Deploy clean lab" -ForegroundColor White
Write-Host "  .\scripts\run_vulnerable.ps1                  - Deploy vulnerable lab" -ForegroundColor White
Write-Host "  .\scripts\reset_vm.ps1                        - Reset VM to snapshot" -ForegroundColor White
Write-Host "  .\scripts\test_connection.ps1                 - Test SSH connectivity" -ForegroundColor White
Write-Host "  .\scripts\fresh_install_test.ps1              - Reset and Deploy" -ForegroundColor White
Write-Host "  .\scripts\run_vulnerability_profile.ps1       - Deploy specific profile" -ForegroundColor White
Write-Host ""
Write-Host "Documentation:" -ForegroundColor Cyan
Write-Host "  documentation\Quick_Start.md                  - New user guide" -ForegroundColor White
Write-Host "  documentation\Vagrant.md                      - Vagrant guide" -ForegroundColor White
Write-Host "  documentation\Ansible_Guide.md                - Ansible and roles guide" -ForegroundColor White
Write-Host ""
