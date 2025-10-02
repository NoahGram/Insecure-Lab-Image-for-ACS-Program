# Vulnerable Lab Environment Setup Documentation

## Overview

This documentation describes the complete setup and operation of a vulnerable lab environment for penetration testing and security training. The system uses a containerized Ansible control node to deploy and manage vulnerable applications on a target Ubuntu system. The setup supports multiple vulnerability profiles for testing different attack scenarios.

## Architecture

```
┌─────────────────────┐    Docker    ┌──────────────────────┐    SSH:2222    ┌─────────────────┐
│   Windows Host      │ ◄─────────► │  Ansible Container   │ ◄────────────► │   Ubuntu VM     │
│   Docker + WSL2    │             │  (Control Node)      │                │   VirtualBox    │
└─────────────────────┘             └──────────────────────┘                └─────────────────┘
```

### Components:
- **Windows Host**: Your local machine running Docker Desktop with WSL2
- **Ansible Container**: Dockerized Ansible control node with vulnerability automation
- **Ubuntu VM**: VirtualBox VM running Ubuntu Server 24.04.3 LTS as the target system

## Prerequisites

### Required Software

#### 1. Docker Desktop with WSL2
- **Docker Desktop for Windows** (latest version)
- **WSL2 Backend** enabled
- **Windows Subsystem for Linux** installed

#### 2. Oracle VirtualBox
- **Oracle VirtualBox** (latest version)
- **Ubuntu Server 24.04.3 LTS** installed as VM

#### 3. VirtualBox VM Configuration
- **Operating System**: Ubuntu 24.04.3 LTS (Server)
- **Network Adapter**: NAT
- **Port Forwarding Rules**:
  - **SSH**: Protocol TCP, Host Port 2222, Guest Port 22
  - **Apache**: Protocol TCP, Host Port 8080, Guest Port 80
  - **Gitea**: Name="Gitea", Protocol=TCP, Host Port=3000, Guest Port=3000
  - **Cockpit**: Name="Cockpit", Protocol=TCP, Host Port=9090, Guest Port=9090
- **User Account**: `user` with sudo privileges
- **SSH Server**: Installed and running

## Directory Structure

```
d:\ansible-control-node\
├── Dockerfile                           # Container definition
├── inventory.ini                        # Ansible inventory configuration
├── vulnerability-profiles.yml           # Vulnerability scenario configurations
├── run_clean.ps1                       # PowerShell script to run clean playbook
├── run_vulnerable.ps1                  # PowerShell script to run vulnerability playbook
├── run_vulnerability_profile.ps1       # PowerShell script to run specific profiles
├── SETUP_DOCUMENTATION.md             # This documentation file
├── README_VULNERABILITIES.md           # Vulnerability profiles documentation
├── Keys/
│   ├── vps_key                         # SSH private key (passphrase-free)
│   └── vps_key.pub                    # SSH public key
└── playbooks/
    ├── 01_clean_image_base.yml         # Clean base system with vulnerable versions
    ├── 02_introduce_vulnerabilities.yml # Additional vulnerability introduction
    ├── initial_setup.yml               # Initial system configuration
    └── test_ping.yml                   # Connectivity test playbook
```

## Detailed Setup Instructions

### Step 1: Install Prerequisites

#### 1.1 Install Docker Desktop with WSL2
1. Download and install **Docker Desktop for Windows**
2. Enable **WSL2 integration** during setup
3. Install **Windows Subsystem for Linux** if not already installed:
   ```powershell
   wsl --install
   ```
4. Restart your computer
5. Verify Docker is running with WSL2 backend

#### 1.2 Install Oracle VirtualBox
1. Download and install **Oracle VirtualBox** (latest version)
2. Download **Ubuntu Server 24.04.3 LTS ISO** from official Ubuntu website (https://ubuntu.com/download/server#manual-install)

#### 1.3 Create Ubuntu Virtual Machine
1. Create new VM in VirtualBox:
   - **Name**: Ubuntu-VPS
   - **Type**: Linux
   - **Version**: Ubuntu (64-bit)
   - **Memory**: 2GB minimum (4GB recommended)
   - **Storage**: 20GB minimum (40GB recommended)

2. Configure VM Network:
   - Go to VM Settings → Network
   - **Adapter 1**: NAT
   - Click **Advanced** → **Port Forwarding**
   - Add rules:
     - **SSH**: Name="SSH", Protocol=TCP, Host Port=2222, Guest Port=22
     - **Apache**: Name="Apache", Protocol=TCP, Host Port=8080, Guest Port=80
     - **Gitea**: Name="Gitea", Protocol=TCP, Host Port=3000, Guest Port=3000
     - **Cockpit**: Name="Cockpit", Protocol=TCP, Host Port=9090, Guest Port=9090

3. Install Ubuntu Server 24.04.3 LTS:
   - Create user account: `name`
   - Set password (remember it for sudo)
   - Install OpenSSH server during installation

### Step 2: Configure Ubuntu VM

#### 2.1 Install SSH Server (if not installed during setup)
```bash
sudo apt update
sudo apt install openssh-server -y
sudo systemctl enable ssh
sudo systemctl start ssh
```

#### 2.2 VPS Firewall Configuration
1. Check Firewall & Ports Statuses
   ```bash
   sudo ufw status
   ```

2. Enable the Firewall (If Inactive)
   ```bash
   sudo ufw enable
   ```

3. Allow SSH
   ```bash
   sudo ufw allow ssh (Recommended)
   sudo ufw allow 22/tcp
   ```

4. SSH PORT OPEN
   ```bash
   The output should show a rule for either 22/tcp (if using a different network mode) or 2222/tcp (for your NAT connection) with the action set to ALLOW
   ```

#### 2.3 Pull Ansible Repo (Private) From Github https://github.com/NoahGram/Lab-Image-ACS-VPS
1. Make sure the root folder of the pulled repo is called: ansible-control-node. When you pull/clone, by default it's called 'Lab-Image-ACS-VPS'. (Will Fix, so that this step becomes obsolete)

   So Not:
   ```bash
   D:\Lab-Image-ACS-VPS\Keys\vps_key.pub
   ```
   But:
   ```bash
   D:\ansible-control-node\Keys\vps_key.pub
   ```

#### 2.4 Echo The Public Key Into the VPS for Ansible Connection:
1. In Windos PowerShell:
   ```powershell
   Get-Content D:\ansible-control-node\Keys\vps_key.pub | ssh -p 2222 user@127.0.0.1 "cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"
   ```

#### (SKIP!!!) 2.4 Configure SSH Key Authentication (SKIP!!!)
1. Generate SSH key pair on Windows:
   ```powershell
   ssh-keygen -t rsa -b 4096 -f .\Keys\vps_key
   ```
2. Copy public key to Ubuntu VM:
   ```powershell
   scp -P 2222 .\Keys\vps_key.pub user@localhost:~/.ssh/authorized_keys
   ```


#### 2.5 Set Sudo Password
Remember the password you set for the `user` during Ubuntu installation. This will be used as the sudo password in the Ansible configuration.

## Configuration Files

### 1. Inventory Configuration (`inventory.ini`)

```ini
# Target server configuration
[vps_lab]
vps_target ansible_host=host.docker.internal ansible_user=noah ansible_port=2222

[all:vars]       
ansible_become=yes                                    # Enable sudo privileges
ansible_become_password=ColdBrew                     # Sudo password
ansible_private_key_file=/ansible/Keys/vps_key      # SSH private key path
host_key_checking=False                              # Disable host key verification
ansible_ssh_common_args='-o StrictHostKeyChecking=no' # Skip SSH host verification
```

**Key Parameters:**
- `ansible_host=host.docker.internal`: Connects to Docker host from container
- `ansible_port=2222`: Custom SSH port (not standard 22)
- `ansible_user=noah`: SSH username on target system
- `ansible_become_password=ColdBrew`: Password for sudo operations

### 2. Docker Configuration (`Dockerfile`)

The Dockerfile creates a containerized Ansible environment with all necessary tools and dependencies.

## SSH Key Management

### SSH Key Setup Process

1. **Original Key**: Had a passphrase (`.`) which caused authentication issues in containerized environment
2. **Solution**: Removed passphrase using `ssh-keygen`:
   ```bash
   ssh-keygen -p -f /ansible/Keys/vps_key -N '' -P '.'
   ```
3. **Result**: Passphrase-free key that works seamlessly with Ansible automation

### Security Considerations

- SSH key is mounted read-only into container
- Key permissions are set to `600` (owner read/write only)
- Key is never transmitted outside the secure channel

## Playbook Overview

### 01_clean_image_base.yml

**Purpose**: Creates a complete vulnerable lab environment with specific software versions

**Software Stack Installed:**

#### 1. **Web Server Infrastructure**
- **Apache HTTP Server** (configurable version)
- **PHP** (configurable version with known vulnerabilities)
- **MariaDB/MySQL** (configurable version)

#### 2. **Web Applications**
- **Gitea** (Git server) - Version 1.17.3 (CVE-2022-4188)
- **DokuWiki** (Wiki) - Version 2020-07-29 (CVE-2020-25790)
- **MantisBT** (Bug Tracker) - Version 2.24.4 (CVE-2022-42790)

#### 3. **System Services**
- **Postfix** (Mail server)
- **Wazuh Agent** (Security monitoring) - Version 4.3.10
- **Cockpit** (System monitoring)

#### 4. **Security Features**
- UFW firewall with configured rules
- SSL/TLS certificate support
- Database security hardening

#### 5. **Version Management System** (Overkill/ Possible Idea)
The playbook uses a sophisticated version management system that allows switching between:
- **Latest secure versions** for hardened environments
- **Specific vulnerable versions** for penetration testing
- **Multiple vulnerability profiles** for different training scenarios

### 02_introduce_vulnerabilities.yml (Example)

**Purpose**: Introduces additional controlled vulnerabilities and weakens system security

**Vulnerabilities Added:**
- SQL injection opportunities
- File upload vulnerabilities
- Weak user accounts and passwords
- Insecure service configurations
- Directory traversal possibilities

## Network Configuration

### Port Mapping
- **SSH**: Port 2222 (custom port for security)
- **HTTP**: Port 80 (web traffic)
- **HTTPS**: Port 443 (secure web traffic)

### Docker Networking
- Uses `host.docker.internal` to access host machine from container
- Container runs with standard Docker networking (not host mode)
- SSH connectivity verified before playbook execution

## Usage Instructions

### Step 3: Deploy Vulnerable Lab Environment

#### 3.1 Test Connectivity First
```powershell
# Test SSH connection to Ubuntu VM
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ssh -o StrictHostKeyChecking=no -i /ansible/Keys/vps_key -p 2222 noah@host.docker.internal 'echo Connection successful'"

# Test Ansible connectivity
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible all -i /ansible/inventory.ini -m ping"
```

#### 3.2 Deploy Clean Base System
```powershell
# Method 1: Using PowerShell script (Recommended)
.\run_clean.ps1

# Method 2: Direct Docker command
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/01_clean_image_base.yml -i /ansible/inventory.ini"

# Method 3: With verbose output for debugging
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/01_clean_image_base.yml -i /ansible/inventory.ini -v"
```

#### 3.3 Deploy Specific Vulnerability Profile (Not Implemented/ Testing Something so IGNORE)
```powershell
# Deploy highly vulnerable environment
.\run_vulnerability_profile.ps1 -Profile "highly_vulnerable"

# Deploy moderately vulnerable environment
.\run_vulnerability_profile.ps1 -Profile "vulnerable"

# Deploy secure baseline
.\run_vulnerability_profile.ps1 -Profile "secure"
```

#### 3.4 Add Additional Vulnerabilities (Not Implemented/ Testing Something so IGNORE)
```powershell
# Introduce more vulnerabilities for advanced testing
.\run_vulnerable.ps1
```

### Step 4: Access Deployed Services

After successful deployment, access the services via your web browser:

#### Web Applications
- **Apache Default**: http://localhost:8080
- **Gitea** (Git Server): http://localhost:3000
- **DokuWiki** (Wiki): http://localhost:8080/dokuwiki
- **MantisBT** (Bug Tracker): http://localhost:8080/mantisbt
- **Cockpit** (System Monitor): https://localhost:9090

#### Service Access
- **SSH to Ubuntu VM**: `ssh -p 2222 user@localhost`
- **Database Access**: Available through web applications
- **Log Monitoring**: Via Cockpit interface

### Step 5: Verify Installation

#### 5.1 Check Service Status
```bash
# SSH into the Ubuntu VM
ssh -p 2222 user@localhost

# Check running services
sudo systemctl status apache2
sudo systemctl status mariadb
sudo systemctl status gitea
sudo systemctl status cockpit
sudo systemctl status wazuh-agent

# Check web application directories
ls -la /var/www/
```

#### 5.2 Verify Versions (Important for Vulnerability Testing)
```bash
# Check installed versions
/usr/local/bin/gitea --version        # Should show 1.17.3
apache2 -v                            # Check Apache version
mysql --version                       # Check MySQL/MariaDB version
php --version                         # Check PHP version
```

#### 5.3 Test Web Applications
- Browse to each web application URL
- Verify they load correctly
- Check for expected vulnerable behavior (if using vulnerable profiles)

## Vulnerability Profiles System (TESTING, SKIP!!!)

### Available Profiles

The system includes three pre-configured vulnerability profiles:

#### 1. **Secure Profile**
- Latest versions of all software
- Security hardening enabled
- Minimal attack surface
- **Use Case**: Baseline security testing

#### 2. **Vulnerable Profile**  
- Mix of current and older versions
- Some known vulnerabilities
- Moderate attack surface
- **Use Case**: Intermediate penetration testing

#### 3. **Highly Vulnerable Profile**
- Older versions with known CVEs
- Multiple vulnerability vectors
- Maximum attack surface
- **Use Case**: Advanced security training and exploit development

### Profile Management

```powershell
# List available profiles
Get-Content .\vulnerability-profiles.yml

# Deploy specific profile
.\run_vulnerability_profile.ps1 -Profile "vulnerable"

# Check current profile status
# (Profile information is displayed after deployment)
```

## Troubleshooting Guide

### Common Issues and Solutions

#### 1. **VirtualBox VM Network Issues**
**Problem**: Cannot connect to Ubuntu VM on port 2222
**Solutions**:
- Verify VirtualBox port forwarding: SSH (2222→22), Apache (8080→80)
- Check Ubuntu VM network adapter is set to NAT
- Ensure SSH service is running: `sudo systemctl status ssh`
- Test direct connection: `telnet localhost 2222`

#### 2. **Docker/WSL2 Issues**
**Problem**: Docker container cannot connect to host
**Solutions**:
- Ensure Docker Desktop is using WSL2 backend
- Verify `host.docker.internal` resolves correctly
- Restart Docker Desktop service
- Check Windows Defender Firewall rules

#### 3. **SSH Key Authentication Issues**
**Problem**: "Permission denied (publickey,password)" Error
**Solutions**:
- Remove passphrase from SSH key: `ssh-keygen -p -f .\Keys\vps_key -N '' -P 'old_passphrase'`
- Verify key permissions are 600
- Ensure public key is in Ubuntu VM: `~/.ssh/authorized_keys`
- Test manual SSH: `ssh -i .\Keys\vps_key -p 2222 user@localhost`

#### 4. **Sudo Password Issues**
**Problem**: "Missing sudo password" or "Incorrect sudo password"
**Solutions**:
- Update `ansible_become_password` in inventory.ini with correct Ubuntu user password
- Test sudo access: `ssh -p 2222 user@localhost 'echo "password" | sudo -S whoami'`

#### 5. **Version Downgrade Issues**
**Problem**: "Packages were downgraded and -y was used without --allow-downgrades"
**Solutions**:
- This is automatically handled by the updated playbooks
- Existing installations are removed before installing target versions
- Use `allow_downgrade: yes` parameter (already implemented)

#### 6. **Web Application Access Issues**
**Problem**: Cannot access web applications via browser
**Solutions**:
- Verify Apache is running: `sudo systemctl status apache2`
- Check port forwarding in VirtualBox (8080→80)
- Test local access: `curl http://localhost:8080`
- Check UFW firewall: `sudo ufw status`

#### 7. **MantisBT Directory Issues**
**Problem**: "cannot stat '/var/www/mantisbt-*': No such file or directory"
**Solutions**:
- This is automatically handled by improved directory detection
- Existing installations are cleaned up before new deployment
- Manual fix: Remove `/var/www/mantisbt*` directories before rerunning

#### 8. **Wazuh Agent Start Issues** (Still Working On This)
**Problem**: "Unable to start service wazuh-agent"
**Solutions**:
- Wazuh agent is intentionally left stopped (needs Wazuh manager)
- Service is enabled but not started
- For testing, install Wazuh manager or configure remote manager

### Verification Commands

```bash
# Check SSH connectivity to Ubuntu VM
ssh -p 2222 noah@localhost

# Verify sudo access with your Ubuntu password
echo 'your_ubuntu_password' | sudo -S whoami

# Check all deployed services
sudo systemctl status apache2 mariadb gitea cockpit postfix

# Test web server response
curl -I http://localhost

# Check installed vulnerable applications
ls -la /var/www/
ls -la /usr/local/bin/gitea

# Verify firewall status
sudo ufw status verbose

# Check database connections
mysql -u root -p -e "SHOW DATABASES;"
```


## Support and Resources

### Ansible Documentation
- [Ansible Official Documentation](https://docs.ansible.com/)
- [Ansible Best Practices](https://docs.ansible.com/ansible/latest/user_guide/playbooks_best_practices.html)

### Docker Resources
- [Docker Desktop for Windows](https://docs.docker.com/desktop/windows/)
- [Docker Networking](https://docs.docker.com/network/)

### SSH Configuration
- [OpenSSH Documentation](https://www.openssh.com/manual.html)
- [SSH Key Management](https://www.ssh.com/academy/ssh/keygen)

## Quick Reference

### Essential PowerShell Commands
```powershell
# Deploy clean baseline
.\run_clean.ps1

# Deploy vulnerability profile
.\run_vulnerability_profile.ps1 -Profile "highly_vulnerable"

# Add extra vulnerabilities
.\run_vulnerable.ps1

# Test SSH connectivity
ssh -p 2222 noah@localhost

# Test web server
curl http://localhost:8080
```

### Essential Docker Commands
```bash
# Build Ansible container
docker build -t ansible-control-node .

# Run interactive Ansible shell
docker run -it --rm -v D:\ansible-control-node:/ansible ansible-control-node sh

# Manual playbook execution
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/01_clean_image_base.yml -i /ansible/inventory.ini"
```

### Essential Ansible Commands (inside container)
```bash
# Test connectivity
ansible all -i inventory.ini -m ping

# Run playbook with verbose output
ansible-playbook playbook.yml -i inventory.ini -v

# Check syntax
ansible-playbook playbook.yml --syntax-check

# Dry run (check mode)
ansible-playbook playbook.yml -i inventory.ini --check

# Run specific tasks
ansible-playbook playbook.yml -i inventory.ini --start-at-task="Task Name"
```

### VirtualBox VM Commands
```bash
# SSH into Ubuntu VM
ssh -p 2222 noah@localhost

# Check running services
sudo systemctl status apache2 mariadb gitea cockpit

# Monitor system resources
htop

# Check network connectivity
netstat -tlnp | grep :80
netstat -tlnp | grep :3000

# View application logs
sudo tail -f /var/log/apache2/error.log
sudo journalctl -u gitea -f
```

## Appendix: File Locations and URLs

### Important File Paths (Ubuntu VM)
```
/var/www/dokuwiki/          # DokuWiki installation
/var/www/mantisbt/          # MantisBT installation  
/usr/local/bin/gitea        # Gitea binary
/etc/gitea/app.ini          # Gitea configuration
/var/lib/gitea/             # Gitea data directory
/var/ossec/                 # Wazuh agent directory
/var/log/apache2/           # Apache log files
```

### Web Application URLs
```
http://localhost:8080                 # Apache default page
http://localhost:8080/dokuwiki        # DokuWiki (username: admin)
http://localhost:8080/mantisbt        # MantisBT (setup required)
http://localhost:3000                 # Gitea (setup required)
https://localhost:9090                # Cockpit (system user credentials)
```

### Database Information
```
MySQL Root User: root
MySQL Root Password: CleanLabPassword123!

Gitea Database: gitea
Gitea DB User: gitea
Gitea DB Password: GiteaDBPassword123!

MantisBT Database: mantisbt  
MantisBT DB User: mantisbt
MantisBT DB Password: MantisBTDBPassword123!
```

---

**Last Updated**: October 2, 2025
**Version**: 2.0 - Vulnerable Lab Environment
**Author**: Penetration Testing Lab Setup