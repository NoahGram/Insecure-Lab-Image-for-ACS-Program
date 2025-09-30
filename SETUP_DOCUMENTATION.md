# Ansible Control Node Setup Documentation

## Overview

This documentation describes the complete setup and operation of an Ansible control node running in Docker to manage a remote VPS (Virtual Private Server). The setup uses containerized Ansible to configure and manage Linux systems remotely.

## Architecture

```
┌─────────────────────┐    Docker    ┌──────────────────────┐    SSH:2222    ┌─────────────────┐
│   Windows Host      │ ◄─────────► │  Ansible Container   │ ◄────────────► │   Target VPS    │
│   (Control Machine) │             │  (Control Node)      │                │   (Ubuntu)      │
└─────────────────────┘             └──────────────────────┘                └─────────────────┘
```

### Components:
- **Windows Host**: Your local machine running Docker Desktop
- **Ansible Container**: Dockerized Ansible control node
- **Target VPS**: Remote Ubuntu server being managed

## Directory Structure

```
d:\ansible-control-node\
├── Dockerfile                    # Container definition
├── inventory.ini                 # Ansible inventory configuration
├── run_clean.ps1                # PowerShell script to run clean playbook
├── run_vulnerable.ps1           # PowerShell script to run vulnerability playbook
├── SETUP_DOCUMENTATION.md      # This documentation file
├── Keys/
│   ├── vps_key                  # SSH private key (passphrase-free)
│   └── vps_key.pub             # SSH public key
└── playbooks/
    ├── 01_clean_image_base.yml  # Clean base system setup
    ├── 02_introduce_vulnerabilities.yml # Vulnerability introduction
    ├── initial_setup.yml        # Initial system configuration
    └── test_ping.yml           # Connectivity test playbook
```

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

**Purpose**: Creates a clean, hardened base system configuration

**Tasks Performed:**
1. **System Updates**
   - Updates package cache
   - Performs distribution upgrade
   - Removes unnecessary packages

2. **Base Package Installation**
   - git, curl, apt-transport-https
   - software-properties-common
   - python3-apt (for Ansible compatibility)

3. **Web Server Setup**
   - Nginx web server
   - PHP-FPM for dynamic content

4. **Mail Relay Configuration**
   - Postfix mail server

5. **File Sharing Services**
   - Samba for SMB/CIFS file sharing

6. **Version Control**
   - Git server components
   - SQLite database support

7. **Security Hardening**
   - UFW firewall activation
   - Opens essential ports: 22 (SSH), 80 (HTTP), 443 (HTTPS)

### 02_introduce_vulnerabilities.yml

**Purpose**: Introduces controlled vulnerabilities for security testing and training

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

### Running the Clean Base Setup

```powershell
# Method 1: Direct Docker command
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/01_clean_image_base.yml -i /ansible/inventory.ini"

# Method 2: Using PowerShell script
.\run_clean.ps1
```

### Testing Connectivity

```powershell
# Test SSH connection
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ssh -o StrictHostKeyChecking=no -i /ansible/Keys/vps_key -p 2222 noah@host.docker.internal 'echo Connection successful'"

# Test Ansible ping
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible all -i /ansible/inventory.ini -m ping"
```

### Verbose Output for Debugging

```powershell
# Add -v flag for verbose output
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/01_clean_image_base.yml -i /ansible/inventory.ini -v"
```

## Troubleshooting Guide

### Common Issues and Solutions

#### 1. "Network is unreachable" Error
**Problem**: Cannot connect to host on port 22
**Solution**: Verify SSH service is running on port 2222, update inventory.ini

#### 2. "Permission denied (publickey,password)" Error
**Problem**: SSH key authentication failing
**Solutions**:
- Remove passphrase from SSH key
- Verify key permissions (600)
- Ensure public key is installed on target system

#### 3. "Missing sudo password" Error
**Problem**: Ansible needs sudo password for privileged operations
**Solution**: Add `ansible_become_password=ColdBrew` to inventory.ini

#### 4. "Could not find a profile matching 'ssh'" Error
**Problem**: UFW application profiles don't exist
**Solution**: Use port numbers instead of profile names in UFW tasks

### Verification Commands

```bash
# Check SSH connectivity
ssh -p 2222 noah@127.0.0.1

# Verify sudo access
echo 'ColdBrew' | sudo -S whoami

# Test UFW status
sudo ufw status verbose

# Check installed packages
dpkg -l | grep -E "(nginx|php|postfix|samba)"
```

## Security Best Practices

### Implemented Security Measures

1. **Custom SSH Port**: Using port 2222 instead of default 22
2. **Firewall Configuration**: UFW enabled with specific port allowances
3. **Key-based Authentication**: SSH keys preferred over passwords
4. **Privilege Escalation**: Controlled sudo access with password
5. **Host Key Verification**: Disabled for automation (consider security implications)

### Security Considerations

- **Host Key Checking Disabled**: Convenient for automation but reduces security
- **Passwords in Configuration**: Consider using Ansible Vault for sensitive data
- **Container Privileges**: Container runs with necessary privileges only

## Maintenance and Updates

### Regular Tasks

1. **System Updates**: Run clean playbook periodically to update packages
2. **Security Patches**: Monitor and apply security updates
3. **Key Rotation**: Periodically rotate SSH keys
4. **Backup Configuration**: Keep backups of working configurations

### Monitoring

- Check Ansible execution logs
- Monitor system resource usage
- Verify service availability
- Review firewall logs

## Scaling and Extension

### Adding New Targets

1. Add new hosts to `[vps_lab]` group in inventory.ini
2. Ensure SSH keys are distributed to new targets
3. Verify network connectivity and ports

### Creating New Playbooks

1. Follow existing playbook structure
2. Use appropriate privilege escalation
3. Include error handling and idempotency
4. Test thoroughly before production use

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

---

**Last Updated**: September 30, 2025
**Version**: 1.0
**Author**: Ansible Control Node Setup

## Appendix: Command Reference

### Essential Docker Commands
```bash
# Build container
docker build -t ansible-control-node .

# Run interactive shell
docker run -it --rm -v D:\ansible-control-node:/ansible ansible-control-node sh

# Check container processes
docker ps

# View container logs
docker logs <container-id>
```

### Essential Ansible Commands
```bash
# Test connectivity
ansible all -i inventory.ini -m ping

# Run playbook with verbose output
ansible-playbook playbook.yml -i inventory.ini -v

# Check syntax
ansible-playbook playbook.yml --syntax-check

# Dry run (check mode)
ansible-playbook playbook.yml -i inventory.ini --check
```