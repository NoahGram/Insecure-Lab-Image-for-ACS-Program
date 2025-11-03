# Ansible Control Node - Lab Environment

Automated deployment system for cybersecurity lab environments using Ansible with Docker containers and VirtualBox VMs.

## � Quick Start

### Step 1: Configure Your Environment

**Create your `.env` file:**
```powershell
# Windows
Copy-Item .env.example .env

# Linux/macOS/WSL  
cp .env.example .env
```

**Edit `.env` with YOUR settings:**
```bash
# Open .env and change these values:
ANSIBLE_CONTROL_NODE_PATH=D:/ansible-control-node  # YOUR repository path
VM1_HOSTNAME=host.docker.internal                   # YOUR VM hostname
VM1_USERNAME=user                                   # YOUR VM username
VM1_PASSWORD=YourPassword                           # YOUR VM password
VM1_SSH_PORT=ssh-port                               # YOUR SSH port
```

### Step 1.2: Generate the inventory.ini
```bash
python ./generate_inventory.py
```

**That's it!** All scripts now automatically use your settings.

### Step 2: Test Connection

```powershell
# Windows
PowerShell -ExecutionPolicy Bypass -File .\scripts\test_connection.ps1

# Linux/macOS/WSL
./scripts/test_connection.sh
```

### Step 3: Deploy

```powershell
# Windows - Clean deployment
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_clean.ps1

# Windows - Vulnerable deployment
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_vulnerable.ps1

# Or use generic runner for any playbook:
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml
```


## 📖 Documentation

- **[SINGLE_SOURCE_CONFIG.md](SINGLE_SOURCE_CONFIG.md)** - Complete setup guide
- **[scripts/README.md](scripts/README.md)** - Deployment scripts documentation
- **[.env.example](.env.example)** - Configuration template with all options



| Service | URL | Purpose |
|---------|-----|---------|
| **LabSys Wiki** | `http://localhost:8080/labsys-wiki/` | Main wiki portal with database |
| **Landing Page** | `http://localhost:8080/` | Auto-redirects to wiki |
| **Gitea** | `http://localhost:3000/` | Git repository server |
| **SSH Access** | `ssh -i Keys/vps_key -p 2222 noah@host.docker.internal` | VM shell access |


## 🏗️ Architecture

### Role-Based Deployment
- **Base System**: Package management, users, security baseline
- **Database**: MariaDB installation and database creation
- **Web Stack**: Apache, PHP, LabSys Wiki with proper styling
- **Applications**: Gitea, MantisBT, and other lab tools
- **System Services**: Final service configuration and startup

### Containerized Execution
- Ansible runs inside Docker container
- Consistent execution environment
- No local Ansible installation required
- Isolated dependency management

### Vulnerability Profiles
- **Secure**: Hardened baseline configuration
- **Vulnerable**: Training-level security issues
- **Highly Vulnerable**: Advanced penetration testing scenarios


## 📚 Documentation

- **Scripts**: See `scripts/README.md` for detailed script documentation
- **Playbooks**: Each role contains its own documentation
- **Vulnerability Profiles**: Documented in `vulnerability-profiles.yml`


## 🤝 Contributing

1. Test changes with fresh deployment
2. Update documentation for new features
3. Follow existing code structure and conventions
4. Ensure backward compatibility with existing deployments


## 📋 Troubleshooting

### Common Issues
- **Docker not running**: Start Docker Desktop
- **VM not accessible**: Check VM is running and SSH port forwarding
- **Permission errors**: Verify SSH key permissions and paths
- **Deployment failures**: Check logs and run fresh install test

### Getting Help
1. Check script logs and error messages
2. Verify VM connectivity: `ssh -i Keys/vps_key -p 2222 user@host.docker.internal`
3. Test basic Docker functionality: `docker --version`
4. Reset and retry with `.\scripts\fresh_install_test.ps1`
