# Ansible Control Node - Lab Environment

Automated deployment system for cybersecurity lab environments using Ansible with Docker containers and Vagrant-managed VMs.

## 🚀 Quick Start

**New to this project?** Follow the **[Quick Start Guide](documentation/Quick_Start.md)** for step-by-step onboarding (10 minutes).

### TL;DR - Get Running Fast

```powershell
# 1. Clone repository
git clone https://github.com/NoahGram/ansible-control-node.git
cd ansible-control-node

# 2. Configure
Copy-Item .env.example .env
# Edit .env: Set ANSIBLE_CONTROL_NODE_PATH and VM_PLATFORM=vagrant

# 3. Build Ansible container
.\setup.ps1

# 4. Create VM
vagrant up
vagrant snapshot save base

# 5. Deploy
.\scripts\run_clean.ps1
```

**Access your lab:**
- Wiki: http://localhost:8080/labsys-wiki/
- Gitea: http://localhost:3000/
- Cockpit: https://localhost:9090/

---

## 📖 Documentation

### 📚 Essential Guides
- **[Quick Start Guide](documentation/Quick_Start.md)** - **START HERE!** Complete onboarding for new users
- **[Vagrant Guide](documentation/Vagrant.md)** - VM automation, snapshots, and management
- **[Ansible Guide](documentation/Ansible_Guide.md)** - Understanding roles, playbooks, and configuration

### 🔧 Reference
- **[SETUP_DOCUMENTATION.md](documentation/SETUP_DOCUMENTATION.md)** - Detailed technical setup
- **[.env.example](.env.example)** - Configuration template with all options
- **[scripts/README.md](scripts/README.md)** - Deployment scripts documentation

---

## 🎯 What Makes This Easy

**No more manual editing!** Edit `.env` once, and:
- ✅ All scripts automatically use YOUR repository path
- ✅ All scripts automatically use YOUR VM credentials  
- ✅ No more `C:\Users\{username}` placeholders
- ✅ No more manual command editing
- ✅ Works on any drive (C:, D:, etc.)
- ✅ Team-friendly (each person has their own `.env`)
- ✅ Vagrant automates VM creation (5 min vs 30 min manual)
- ✅ Instant resets via snapshots (~30 seconds)

---

## 🎮 Common Commands

```powershell
# VM Management
vagrant up                       # Start/create VM
vagrant halt                     # Stop VM
vagrant snapshot save base       # Create snapshot
vagrant snapshot restore base    # Restore snapshot

# Deployment
.\scripts\run_clean.ps1          # Deploy clean lab
.\scripts\run_vulnerable.ps1     # Deploy vulnerable lab
.\scripts\reset_vm.ps1           # Reset VM to snapshot
.\scripts\test_connection.ps1    # Test SSH connection

# Full Workflow
.\scripts\fresh_install_test.ps1  # Reset + Deploy in one command
```

---

## 🌐 Deployed Services

| Service | URL | Purpose |
|---------|-----|---------|
| **LabSys Wiki** | http://localhost:8080/labsys-wiki/ | Main wiki portal with database |
| **Landing Page** | http://localhost:8080/ | Auto-redirects to wiki |
| **Gitea** | http://localhost:3000/ | Git repository server |
| **Cockpit** | https://localhost:9090/ | System management interface |

**SSH Access:**
```bash
ssh -i Keys/vps_key -p 2222 vagrant@host.docker.internal
# Or simply: vagrant ssh
```

---
---

## 🏗️ Architecture

### Role-Based Deployment
- **Base System**: Package management, users, security baseline
- **Database**: MariaDB installation and database creation
- **Web Stack**: Apache, PHP, LabSys Wiki with proper styling
- **Applications**: Gitea, MantisBT, and other lab tools
- **System Services**: Final service configuration and startup

**Learn more:** [Ansible Guide](documentation/Ansible_Guide.md)

### Automation Stack
- **Vagrant**: Automated VM provisioning and snapshot management
- **Docker**: Ansible control node in isolated container
- **Ansible**: Configuration management with idempotent roles
- **.env**: Single source of truth for all configuration

**Learn more:** [Vagrant Guide](documentation/Vagrant.md)

### Vulnerability Profiles
- **Clean**: Secure baseline configuration with hardening
- **Vulnerable**: Training-level security issues for learning
- **Highly Vulnerable**: Advanced penetration testing scenarios

⚠️ **WARNING**: Only use vulnerable profiles on isolated lab networks!

---

## 🤝 Contributing

1. Test changes with fresh deployment: `.\scripts\fresh_install_test.ps1`
2. Update documentation for new features
3. Follow existing code structure and conventions
4. Ensure backward compatibility with existing deployments

---

## � Troubleshooting

### Quick Fixes

**VM won't start:**
```powershell
vagrant destroy -f
vagrant up
```

**SSH connection failed:**
```powershell
vagrant status          # Check VM is running
vagrant ssh             # Test direct SSH
python generate_inventory.py  # Regenerate inventory
```

**Deployment fails:**
```powershell
.\scripts\reset_vm.ps1   # Reset to clean state
.\scripts\run_clean.ps1  # Try again
```

**Docker issues:**
```powershell
docker ps               # Verify Docker is running
.\setup.ps1             # Rebuild Ansible image
```

### Detailed Help

- **[Vagrant Guide](documentation/Vagrant.md)** - VM and snapshot troubleshooting
- **[Ansible Guide](documentation/Ansible_Guide.md)** - Playbook and role debugging
- **[Quick Start](documentation/Quick_Start.md)** - Common setup issues

---

## 📋 Project Structure

```
ansible-control-node/
├── documentation/           # All documentation
│   ├── Quick_Start.md      # New user onboarding
│   ├── Vagrant.md          # Vagrant guide
│   ├── Ansible_Guide.md    # Ansible & roles guide
│   └── SETUP_DOCUMENTATION.md  # Detailed technical setup
├── playbooks/              # Ansible playbooks and roles
│   ├── roles/              # Modular role-based architecture
│   ├── site_clean.yml      # Clean deployment
│   └── site_vulnerable.yml # Vulnerable deployment
├── scripts/                # Deployment automation scripts
│   ├── run_clean.ps1       # Deploy clean lab
│   ├── run_vulnerable.ps1  # Deploy vulnerable lab
│   ├── reset_vm.ps1        # Reset VM to snapshot
│   └── test_connection.ps1 # Test SSH connectivity
├── Keys/                   # SSH keys (gitignored)
├── .env                    # Your configuration (gitignored)
├── .env.example            # Configuration template
├── Vagrantfile             # VM definition
├── Dockerfile              # Ansible control node image
└── inventory.ini           # Auto-generated from .env
```

---

## 🎓 Learning Path

### Beginner (Start Here!)
1. ✅ Follow **[Quick Start Guide](documentation/Quick_Start.md)**
2. ✅ Deploy clean lab: `.\scripts\run_clean.ps1`
3. ✅ Access services and explore
4. ✅ Reset VM: `.\scripts\reset_vm.ps1`

### Intermediate
1. 📖 Read **[Vagrant Guide](documentation/Vagrant.md)**
2. 📖 Read **[Ansible Guide](documentation/Ansible_Guide.md)**
3. 🔧 Modify `.env` settings and redeploy
4. 🔧 Explore role files in `playbooks/roles/`

### Advanced
1. 🎯 Create custom Ansible roles
2. 🎯 Modify Vagrantfile for custom VM config
3. 🎯 Develop new vulnerability profiles
4. 🎯 Contribute improvements to the project

---

**Ready to start?** Go to **[Quick Start Guide](documentation/Quick_Start.md)** 🚀