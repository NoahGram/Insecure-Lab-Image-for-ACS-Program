# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is an **Ansible Control Node** project for deploying vulnerable lab environments for cybersecurity education and penetration testing training. The system uses Docker containers to orchestrate Ansible deployments against target Ubuntu VMs running in VirtualBox with NAT port forwarding.

## Architecture

**Multi-tier containerized automation system:**
- **Windows Host**: Docker Desktop with WSL2 backend
- **Ansible Container**: Dockerized control node with Python 3.11 + Ansible
- **Target VM**: Ubuntu 24.04 LTS in VirtualBox with SSH on port 2222
- **Network**: Docker -> host.docker.internal -> VirtualBox NAT -> Ubuntu VM

**Key architectural decisions:**
- All commands execute via Docker containers for consistency across Windows environments
- SSH keys mounted read-only into containers for security
- Vulnerability profiles defined in YAML for different training scenarios
- PowerShell wrapper scripts handle Docker command complexity
- **Ansible Roles architecture** for modular, reusable automation components

## Common Development Commands

### Building and Testing
```powershell
# Build the Ansible control container
docker build -t ansible-control-node .

# Test SSH connectivity to target VM  
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ssh -o StrictHostKeyChecking=no -i /ansible/Keys/vps_key -p 2222 noah@host.docker.internal 'echo Connection successful'"

# Test Ansible connectivity
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible all -i /ansible/inventory.ini -m ping"
```

### Deployment Commands
```powershell
# Deploy clean base system (recommended method)
.\run_clean.ps1

# Deploy with specific vulnerability profile
.\run_vulnerability_profile.ps1 -Profile "vulnerable_profile"
.\run_vulnerability_profile.ps1 -Profile "highly_vulnerable_profile" -Verbose

# Deploy only vulnerabilities (assumes clean base exists)
.\run_vulnerable.ps1

# Manual role-based playbook execution
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/site_clean.yml -i /ansible/inventory.ini -v"

# Run specific role only
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook -i /ansible/inventory.ini --tags web_stack /ansible/playbooks/site_clean.yml"
```

### Direct Ansible Commands (inside container)
```bash
# Interactive container shell
docker run -it --rm -v D:\ansible-control-node:/ansible ansible-control-node sh

# Inside container:
chmod 600 /ansible/Keys/vps_key
ansible-playbook playbook.yml -i inventory.ini --syntax-check
ansible-playbook playbook.yml -i inventory.ini --check  # dry run
ansible all -i inventory.ini -m setup  # gather facts
```

### Role Development Commands
```bash
# Test individual roles
ansible-playbook -i inventory.ini --tags base_system playbooks/site_clean.yml
ansible-playbook -i inventory.ini --tags web_stack playbooks/site_clean.yml
ansible-playbook -i inventory.ini --tags database playbooks/site_clean.yml

# Skip specific roles
ansible-playbook -i inventory.ini --skip-tags vulnerabilities playbooks/site_vulnerable.yml

# List all available tags
ansible-playbook --list-tags playbooks/site_clean.yml
```

## Key Files and Directories

**Configuration:**
- `inventory.ini` - Ansible inventory with SSH connection details
- `vulnerability-profiles.yml` - Vulnerability scenario configurations with CVE mappings
- `Dockerfile` - Container definition (Python 3.11 + Ansible)

**Playbooks:**
- `site_clean.yml` - Role-based clean deployment playbook
- `site_vulnerable.yml` - Role-based vulnerable deployment playbook
- `01_clean_image_base.yml` - Legacy monolithic playbook (~1200 lines)
- `02_introduce_vulnerabilities.yml` - Legacy vulnerability playbook
- `fix_wazuh_*.yml` - Wazuh security monitoring fixes

**Ansible Roles:**
- `base_system` - System updates, base packages, version checking
- `web_stack` - Apache, PHP installation and configuration
- `database` - MariaDB installation, security hardening, app databases
- `applications` - Gitea, DokuWiki, MantisBT deployment
- `system_services` - Cockpit, Wazuh, Postfix, firewall configuration
- `vulnerabilities` - Controlled vulnerability introduction

**Automation Scripts:**
- `run_clean.ps1` - Deploy clean base system
- `run_vulnerability_profile.ps1` - Deploy specific vulnerability profiles  
- `run_vulnerable.ps1` - Add vulnerabilities to existing system

## Vulnerability Profile System

The project uses a sophisticated vulnerability management system:

**Profile Types:**
- `secure_profile` - Latest versions for hardened environments
- `vulnerable_profile` - Known CVEs (Apache 2.4.41, PHP 7.3, etc.)
- `highly_vulnerable_profile` - Legacy versions with multiple vulnerabilities

**Key vulnerabilities included:**
- CVE-2019-0211 (Apache privilege escalation)
- CVE-2022-4188 (Gitea repository manipulation) 
- CVE-2020-25790 (DokuWiki file upload)
- CVE-2022-42790 (MantisBT SQL injection)

## Roles-Based Architecture Patterns

**Role-based deployment structure:**
1. **base_system** - System updates, base packages, version checking
2. **web_stack** - Apache, PHP modules, landing page creation with templates
3. **database** - MariaDB installation, security hardening, application databases
4. **applications** - Gitea, DokuWiki, MantisBT with dedicated users/databases  
5. **system_services** - Cockpit, Wazuh, Postfix, firewall configuration
6. **vulnerabilities** - Controlled vulnerability introduction (when needed)

**Ansible best practices implemented:**
- **Role separation** for modularity and reusability
- **Variable inheritance** from defaults and profile-based overrides
- **Template-driven configuration** with Jinja2 templating
- **Handler-based service management** for efficient restarts
- **Idempotent operations** safe for multiple executions
- **Profile-based deployment** supporting multiple vulnerability scenarios

## Troubleshooting

**Common issues:**
- VirtualBox port forwarding (verify SSH 2222→22, Apache 8080→80)
- Docker WSL2 connectivity (ensure `host.docker.internal` resolves)
- SSH key permissions (container sets chmod 600 automatically)
- Service startup timing (playbooks include wait_for checks)

**Debug commands:**
```powershell
# Check VirtualBox VM connectivity
ssh -p 2222 noah@localhost

# Verify services inside VM
ssh -p 2222 noah@localhost 'sudo systemctl status apache2 mariadb gitea cockpit wazuh-agent'

# Check web access
curl http://localhost:8080
```

## Security Notes

- SSH private key (`Keys/vps_key`) is mounted read-only into containers
- Database passwords are defined in playbook variables
- Sudo password configured in inventory.ini (`ansible_become_password`)
- Wazuh runs in standalone educational mode
- All services configured for lab environment (not production-ready)

This codebase demonstrates enterprise-grade infrastructure automation patterns while maintaining educational accessibility for cybersecurity training scenarios.