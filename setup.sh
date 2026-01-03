#!/bin/bash
# Setup Script for Ansible Control Node (Linux)
# Run this first to configure your environment

set -e

CYAN='\033[0;36m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
RED='\033[0;31m'
GRAY='\033[1;30m'
NC='\033[0m' # No Color

function print_section() {
  echo -e "${CYAN}\n============================================"
  echo -e " $1"
  echo -e "============================================${NC}\n"
}

echo -e "${CYAN}Setting up Ansible Control Node Environment...${NC}\n"

print_section "Environment Checks"

# Check Docker
printf "${YELLOW}Checking Docker...${NC}\n"
if command -v docker &>/dev/null; then
  dockerVersion=$(docker --version)
  echo -e "${GREEN}[OK] Docker found: $dockerVersion${NC}"
else
  echo -e "${RED}[ERROR] Docker not found or not running${NC}"
  echo -e "${YELLOW}  Please install Docker and ensure it is running${NC}"
  echo -e "${YELLOW}  Download: https://docs.docker.com/get-docker/${NC}"
fi

# Check Vagrant
printf "${YELLOW}Checking Vagrant...${NC}\n"
if command -v vagrant &>/dev/null; then
  vagrantVersion=$(vagrant --version)
  echo -e "${GREEN}[OK] Vagrant found: $vagrantVersion${NC}"
else
  echo -e "${RED}[ERROR] Vagrant not found${NC}"
  echo -e "${YELLOW}  Please install Vagrant for VM automation${NC}"
  echo -e "${YELLOW}  Download: https://www.vagrantup.com/downloads${NC}"
fi

# Check VirtualBox
printf "${YELLOW}Checking VirtualBox...${NC}\n"
if command -v VBoxManage &>/dev/null; then
  vboxVersion=$(VBoxManage --version)
  echo -e "${GREEN}[OK] VirtualBox found: $vboxVersion${NC}"
else
  echo -e "${RED}[ERROR] VirtualBox not found${NC}"
  echo -e "${YELLOW}  Please install VirtualBox${NC}"
  echo -e "${YELLOW}  Download: https://www.virtualbox.org/wiki/Downloads${NC}"
fi

# Check VMware (Workstation/Fusion)
printf "${YELLOW}Checking VMware...${NC}\n"
if command -v vmrun &>/dev/null; then
  echo -e "${GREEN}[OK] VMware Workstation/Fusion found.${NC}"
else
  echo -e "${YELLOW}[WARN] VMware Workstation/Fusion not found${NC}"
  echo -e "${YELLOW}  If you plan to use VMware, please install VMware Workstation or Fusion${NC}"
  echo -e "${YELLOW}  Download: https://www.vmware.com/products/desktop-hypervisor/workstation-and-fusion${NC}"
fi

# Check SSH keys
printf "${YELLOW}Checking SSH keys...${NC}\n"
if [ -f "Keys/vps_key" ]; then
  echo -e "${GREEN}[OK] SSH private key found${NC}"
else
  echo -e "${YELLOW}[WARN] SSH private key not found in Keys/vps_key${NC}"
  echo -e "${GRAY}  Keys will be installed automatically by Vagrant${NC}"
fi

# Check .env file
printf "${YELLOW}Checking .env configuration...${NC}\n"
if [ -f ".env" ]; then
  echo -e "${GREEN}[OK] .env file found${NC}"
else
  echo -e "${RED}[ERROR] .env file not found${NC}"
  echo -e "${YELLOW}  Please create .env from .env.example:${NC}"
  echo -e "${CYAN}  cp .env.example .env${NC}"
fi

print_section "Building Ansible Control Node"

# Check if Dockerfile exists
if [ ! -f "Dockerfile" ]; then
  echo -e "${RED}[ERROR] Dockerfile not found!${NC}"
  echo -e "${YELLOW}  Cannot build Docker image without Dockerfile${NC}"
  exit 1
fi

# Build Docker image
printf "${YELLOW}Building Docker image ansible-control-node...${NC}\n"
printf "${GRAY}This may take a few minutes...${NC}\n"
if docker build -t ansible-control-node .; then
  echo -e "\n${GREEN}[SUCCESS] Docker image built successfully!${NC}"
else
  echo -e "\n${RED}[ERROR] Docker build failed!${NC}"
  exit 1
fi

print_section "Setup Complete!"

echo -e "${CYAN}Next Steps:${NC}"
echo -e "1. Create/edit .env file: ${CYAN}cp .env.example .env${NC}"
echo -e "2. Set VM_PLATFORM=vagrant in .env"
echo -e "3. Create VM: ${CYAN}vagrant up${NC}"
echo -e "    This will automatically use VirtualBox as VM provider."
echo -e "    If you want to use another provider, specify it with --provider flag. Like 'vagrant up --provider vmware_desktop'"
echo -e "    Make sure to install Vmware Vagrant Plugin: Read more in the documentation/VMware_Vagrant_Install.md"
echo -e "4. Create snapshot: ${CYAN}vagrant snapshot save base${NC}"
echo -e "5. Deploy lab: ${CYAN}./scripts/run_clean.sh${NC}"
echo -e ""
echo -e "${CYAN}Available Commands:${NC}"
echo -e "  ./scripts/run_clean.sh                       - Deploy clean lab"
echo -e "  ./scripts/run_vulnerable.ps1                 - Deploy vulnerable lab (PowerShell only)"
echo -e "  ./scripts/reset_vm.ps1                       - Reset VM to snapshot (PowerShell only)"
echo -e "  ./scripts/test_connection.sh                 - Test SSH connectivity"
echo -e "  ./scripts/fresh_install_test.ps1             - Reset and Deploy (PowerShell only)"
echo -e "  ./scripts/run_vulnerability_profile.ps1      - Deploy specific profile (PowerShell only)"
echo -e ""
echo -e "${CYAN}Documentation:${NC}"
echo -e "  documentation/Quick_Start.md                  - New user guide"
echo -e "  documentation/Vagrant.md                      - Vagrant guide"
echo -e "  documentation/Ansible_Guide.md                - Ansible and roles guide"
echo -e ""