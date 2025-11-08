# Ansible Guide - Lab Environment

Complete guide to understanding and using Ansible in this project, including role-based architecture and practical usage.

## 📋 Table of Contents
1. [What is Ansible?](#what-is-ansible)
2. [How Ansible Works](#how-ansible-works)
3. [Key Ansible Concepts](#key-ansible-concepts)
4. [Role-Based Architecture](#role-based-architecture)
5. [Our Project Roles](#our-project-roles)
6. [Variables and Configuration](#variables-and-configuration)
7. [Running Playbooks](#running-playbooks)
8. [Troubleshooting](#troubleshooting)

---

## 🤖 What is Ansible?

**Ansible** is an open-source automation tool that simplifies:
- **Configuration Management**: Setting up and configuring servers
- **Application Deployment**: Installing and configuring software
- **Task Automation**: Automating repetitive IT tasks
- **Orchestration**: Coordinating complex deployments across multiple systems

### Why Use Ansible?
- **Agentless**: No software to install on target machines (uses SSH)
- **Idempotent**: Safe to run multiple times - only makes necessary changes
- **Human-readable**: Uses YAML syntax that's easy to understand
- **Powerful**: Can manage everything from single servers to entire data centers

---

## ⚙️ How Ansible Works

```
┌─────────────────┐    SSH    ┌─────────────────┐
│  Control Node   │ ────────► │  Target Server  │
│   (Docker)      │           │   (Ubuntu VM)   │
│                 │           │                 │
│ • Playbooks     │           │ • Applications  │
│ • Inventory     │           │ • Services      │
│ • SSH Keys      │           │ • Configuration │
└─────────────────┘           └─────────────────┘
```

### The Process:
1. **Control Node** (our Docker container) connects to **Target Server** via SSH
2. **Playbook** defines what tasks to perform
3. **Inventory** tells Ansible which servers to target
4. **Tasks** execute in order on target server
5. **Results** are reported back to control node

---

## 🔑 Key Ansible Concepts

### Playbooks
YAML files that define automation tasks. Think of them as instruction manuals.

```yaml
---
- name: Install Apache
  hosts: all
  tasks:
    - name: Install apache2 package
      apt:
        name: apache2
        state: present
```

### Inventory
Defines which servers to manage. Our `inventory.ini` is auto-generated from `.env`:

```ini
[vps_lab]
vps_target ansible_host=host.docker.internal ansible_user=vagrant ansible_port=2222

[all:vars]
ansible_become=yes
ansible_private_key_file=/ansible/Keys/vps_key
```

### Modules
Pre-built functions for common tasks:
- `apt`: Manage packages on Debian/Ubuntu
- `systemd`: Manage services
- `copy`: Copy files
- `template`: Generate files from templates
- `mysql_db`: Manage MySQL databases

### Idempotency
Running the same playbook multiple times produces the same result. Ansible checks current state before making changes.

---

## 🏗️ Role-Based Architecture

### Why Roles?

**Problem with Monolithic Playbooks:**
- ❌ Single massive file (1000+ lines)
- ❌ Hard to maintain and debug
- ❌ Code duplication
- ❌ Difficult to test individual components

**Solution with Roles:**
- ✅ Modular, reusable components
- ✅ Single responsibility per role
- ✅ Easy to test and maintain
- ✅ Clear dependencies
- ✅ Team-friendly development

### Standard Role Structure

```
role_name/
├── tasks/                # Main functionality (required)
│   └── main.yml         # Primary task definitions
├── handlers/             # Event-driven tasks
│   └── main.yml         # Service restarts, notifications
├── templates/            # Jinja2 templates
│   ├── config.j2        # Dynamic configuration files
│   └── script.sh.j2     # Template scripts
├── files/               # Static files to copy
│   └── static_file.txt  # Files copied as-is
├── vars/                # Role-specific variables
│   └── main.yml         # Internal role variables
├── defaults/            # Default variable values
│   └── main.yml         # Can be overridden by users
└── meta/                # Role metadata and dependencies
    └── main.yml         # Dependencies, supported platforms
```

### Project Structure

```
ansible-control-node/
├── playbooks/
│   ├── roles/                    # All roles directory
│   │   ├── base_system/          # System foundation
│   │   ├── database/             # Database services
│   │   ├── web_stack/            # Web server infrastructure
│   │   ├── applications/         # Service applications
│   │   ├── system_services/      # Monitoring/services
│   │   └── vulnerabilities/      # Security testing
│   ├── site_clean.yml            # Clean deployment playbook
│   └── site_vulnerable.yml       # Vulnerable deployment playbook
├── inventory.ini                 # Target hosts (auto-generated)
├── ansible.cfg                   # Ansible configuration
└── .env                          # Your settings
```

### Role Execution Order

Roles are executed in logical dependency order:

```
1. base_system      → System preparation, core packages
2. database         → MariaDB (depends on base)
3. web_stack        → Apache, PHP (depends on base)
4. applications     → Web apps (depends on web + database)
5. system_services  → Monitoring (depends on base)
6. vulnerabilities  → Security testing (optional)
```

---

## 📦 Our Project Roles

### 1. **base_system** - System Foundation
**Purpose:** Prepares the base system with essential packages and configuration.

**What it does:**
- Updates package cache
- Installs core utilities (git, curl, vim, etc.)
- Configures system users
- Sets up SSH hardening
- Installs Python dependencies

**Key Files:**
- `tasks/main.yml` - Main package installation
- `vars/main.yml` - Package lists

**When to modify:** When adding new system-level packages or tools.

---

### 2. **database** - Database Services
**Purpose:** Installs and configures MariaDB database server.

**What it does:**
- Installs MariaDB server
- Configures root password
- Creates application databases
- Sets up database users
- Configures remote access

**Key Files:**
- `tasks/main.yml` - Database installation and setup
- `vars/main.yml` - Database names, users, passwords

**Variables:**
```yaml
mysql_root_password: "{{ lookup('env', 'MYSQL_ROOT_PASSWORD') | default('root_password') }}"
mysql_databases:
  - labsys_wiki
  - gitea
  - mantis
```

**When to modify:** When adding new databases or changing access permissions.

---

### 3. **web_stack** - Web Server Infrastructure
**Purpose:** Installs and configures Apache web server with PHP.

**What it does:**
- Installs Apache2 web server
- Installs PHP and required modules
- Configures Apache virtual hosts
- Sets up SSL certificates (if needed)
- Enables required Apache modules

**Key Files:**
- `tasks/main.yml` - Apache and PHP installation
- `templates/vhost.conf.j2` - Virtual host configuration
- `handlers/main.yml` - Apache service restart handlers

**When to modify:** When adding new PHP modules or changing Apache configuration.

---

### 4. **applications** - Service Applications
**Purpose:** Deploys web applications and services.

**What it does:**
- Installs Gitea (Git server)
- Deploys LabSys Wiki
- Configures application databases
- Sets up application users
- Creates application directories

**Key Files:**
- `tasks/main.yml` - Application deployment
- `templates/` - Application configuration files
- `files/` - Static application files

**When to modify:** When adding new applications or updating configurations.

---

### 5. **system_services** - System Monitoring
**Purpose:** Installs system monitoring and management tools.

**What it does:**
- Installs Cockpit web interface
- Configures service monitoring
- Sets up log management
- Enables system dashboards

**Key Files:**
- `tasks/main.yml` - Service installation
- `handlers/main.yml` - Service restart handlers

**When to modify:** When adding new monitoring tools.

---

### 6. **vulnerabilities** - Security Testing (Optional)
**Purpose:** Introduces intentional vulnerabilities for security training.

**What it does:**
- Creates vulnerable configurations
- Adds weak passwords
- Disables security features
- Sets up intentionally insecure services

**⚠️ WARNING:** Only use on isolated lab networks!

**When to modify:** When creating new security training scenarios.

---

## 🔧 Variables and Configuration

### Variable Hierarchy

Ansible loads variables from multiple sources (lowest to highest priority):

1. **Role defaults** (`roles/role_name/defaults/main.yml`)
2. **Inventory variables** (`inventory.ini`)
3. **Playbook variables** (`site_clean.yml`)
4. **Role variables** (`roles/role_name/vars/main.yml`)
5. **Extra variables** (command line `-e`)

### Our Configuration System

**`.env` file** → Controls everything:
```bash
VM1_USERNAME=vagrant
VM1_HOSTNAME=host.docker.internal
VM1_SSH_PORT=2222
VM_PLATFORM=vagrant
```

**`generate_inventory.py`** → Creates `inventory.ini` from `.env`

**Playbooks** → Load inventory and execute roles

### Using Variables in Roles

**In task files:**
```yaml
- name: Create database
  mysql_db:
    name: "{{ item }}"
    state: present
  loop: "{{ mysql_databases }}"
```

**In templates:**
```jinja
ServerName {{ ansible_host }}
DocumentRoot {{ web_root }}
```

**Ansible Facts:**
```yaml
# Auto-discovered system information
{{ ansible_distribution }}        # Ubuntu
{{ ansible_default_ipv4.address }} # VM IP address
{{ ansible_hostname }}             # Hostname
```

---

## 🚀 Running Playbooks

### Quick Commands

**Windows PowerShell:**
```powershell
# Deploy clean environment
.\scripts\run_clean.ps1

# Deploy vulnerable environment
.\scripts\run_vulnerable.ps1

# Run specific playbook
.\scripts\run_playbook.ps1 playbooks/site_clean.yml
```

**Linux/macOS:**
```bash
# Deploy clean environment
./scripts/run_clean.sh

# Run specific playbook
./scripts/run_playbook.sh playbooks/site_clean.yml
```

### What Happens When You Run

1. **Script loads `.env`** settings
2. **Generates `inventory.ini`** from `.env`
3. **Starts Docker container** with Ansible
4. **Connects to VM** via SSH
5. **Executes roles** in order
6. **Reports results** (ok, changed, failed)

### Understanding Output

```
TASK [base_system : Update apt cache] ************************************
ok: [vps_target]
```
- `ok` - Task completed, no changes needed (idempotent)
- `changed` - Task made modifications
- `failed` - Task encountered an error
- `skipped` - Task was skipped (conditional)

### Playbook Recap

```
PLAY RECAP ***************************************************************
vps_target : ok=87  changed=55  unreachable=0  failed=0  skipped=12
```
- **ok**: Total successful tasks
- **changed**: Tasks that made changes
- **failed**: Tasks that failed
- **skipped**: Tasks that were skipped

---

## 🐛 Troubleshooting

### Common Issues

#### 1. SSH Connection Failed
```
UNREACHABLE! => {"changed": false, "msg": "Failed to connect to the host"}
```

**Solutions:**
- Verify VM is running: `vagrant status` or check VirtualBox
- Test SSH manually: `ssh -i Keys/vps_key -p 2222 vagrant@host.docker.internal`
- Check `.env` settings (username, hostname, port)
- Regenerate inventory: `python generate_inventory.py`

#### 2. Permission Denied
```
fatal: [vps_target]: FAILED! => {"msg": "Missing sudo password"}
```

**Solutions:**
- Ensure `ansible_become=yes` in inventory
- Check `ansible_become_password` is set correctly
- Verify user has sudo privileges on target VM

#### 3. Module Not Found
```
fatal: [vps_target]: FAILED! => {"msg": "The module apt was not found"}
```

**Solutions:**
- Ensure Python3 is installed on target: `vagrant provision`
- Check if Ansible can detect Python: `ansible all -m ping -i inventory.ini`

#### 4. Port Already in Use
```
fatal: [vps_target]: FAILED! => {"msg": "Port 80 is already in use"}
```

**Solutions:**
- Stop conflicting service: `sudo systemctl stop apache2`
- Reset VM to clean state: `.\scripts\reset_vm.ps1`
- Check for processes: `sudo netstat -tulpn | grep :80`

#### 5. Idempotency Issues
Task shows `changed` every time even when nothing should change.

**Solutions:**
- Review task logic - may need to add `creates` or `when` conditions
- Check if task uses proper module (use `systemd` instead of `command`)
- Add state checks before making changes

### Debugging Techniques

**Verbose Output:**
```powershell
# Add -v to see more details (up to -vvvv for maximum verbosity)
docker run --rm -v "D:/ansible-control-node:/ansible" ansible-control-node sh -c "ansible-playbook /ansible/playbooks/site_clean.yml -i /ansible/inventory.ini -v"
```

**Test Single Role:**
Create a test playbook:
```yaml
---
- hosts: all
  become: yes
  roles:
    - base_system
```

**Check Ansible Facts:**
```bash
ansible all -m setup -i inventory.ini
```

**Dry Run (Check Mode):**
```bash
ansible-playbook playbooks/site_clean.yml -i inventory.ini --check
```

### Getting Help

1. Check Ansible documentation: https://docs.ansible.com/
2. Review role-specific README files in `playbooks/roles/*/`
3. Check project documentation in `documentation/`
4. Examine playbook output for specific error messages

---

## 📚 Additional Resources

### Ansible Documentation
- [Official Ansible Docs](https://docs.ansible.com/)
- [Ansible Module Index](https://docs.ansible.com/ansible/latest/collections/index_module.html)
- [Best Practices](https://docs.ansible.com/ansible/latest/user_guide/playbooks_best_practices.html)

### Project Documentation
- `documentation/Vagrant.md` - Vagrant setup and usage
- `documentation/Quick_Start.md` - Quick onboarding guide
- `documentation/SETUP_DOCUMENTATION.md` - Detailed setup guide

### Configuration Files
- `.env.example` - Configuration template
- `inventory.ini` - Generated from .env
- `ansible.cfg` - Ansible behavior settings

---

**Need Help?** Check `documentation/Quick_Start.md` for step-by-step onboarding!
