# Ansible Control Node - Lab Environment


Automated deployment system for cybersecurity lab environments using Ansible with Docker containers and Vagrant-managed VMs.

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
