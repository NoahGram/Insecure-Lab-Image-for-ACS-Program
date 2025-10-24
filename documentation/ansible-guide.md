# Ansible Guide for HBO-ICT Lab Environment

## 📋 Table of Contents
1. [What is Ansible?](#what-is-ansible)
2. [How Ansible Works](#how-ansible-works)
3. [Key Ansible Concepts](#key-ansible-concepts)
4. [Ansible Roles Explained](#ansible-roles-explained)
5. [Variables and Configuration](#variables-and-configuration)
6. [Tags and Selective Execution](#tags-and-selective-execution)
7. [Project Structure Overview](#project-structure-overview)
8. [How We Use Ansible in This Project](#how-we-use-ansible-in-this-project)
9. [Running the Playbooks](#running-the-playbooks)
10. [Understanding Our Setup](#understanding-our-setup)
11. [Troubleshooting Common Issues](#troubleshooting-common-issues)

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
4. **Modules** execute specific actions (install packages, start services, etc.)
5. Ansible ensures the desired state is achieved

---

## 🏗️ Key Ansible Concepts

### 📖 **Playbook**
A YAML file containing a series of tasks to execute. Think of it as a "recipe" for setting up a server.

```yaml
---
- name: Configure Web Server
  hosts: lab_servers
  become: yes
  roles:
    - role: base_system
      tags: base_system
    - role: web_stack
      tags: web_stack
```

### 📝 **Task**
A single action to perform (install package, copy file, start service, etc.)

```yaml
- name: Install Apache web server
  apt:
    name: apache2
    state: present
  notify: restart apache2
```

### 🎭 **Module**
Pre-built functions that perform specific operations:
- `apt`: Package management for Debian/Ubuntu
- `yum`: Package management for RedHat/CentOS
- `systemd`: Service management
- `copy`: File operations
- `template`: File templating with variables
- `mysql_user`: Database user management
- `get_url`: Download files from URLs

### 🎯 **Roles**
Reusable automation components that organize tasks, variables, files, and templates into a structured format.

```
roles/
├── base_system/
│   ├── tasks/main.yml      # Main tasks for this role
│   ├── vars/main.yml       # Role-specific variables
│   ├── files/              # Static files to copy
│   ├── templates/          # Template files with variables
│   └── handlers/main.yml   # Service restart handlers
```

### 📊 **Inventory**
A file listing target servers and their connection details:

```ini
[lab_servers]
ubuntu-vm ansible_host=host.docker.internal ansible_port=2222
```

### �️ **Tags**
Labels that allow selective execution of specific parts of a playbook:

```yaml
- name: Install database
  include_role:
    name: database
  tags: database
```

### 🔄 **Handler**
Special tasks that only run when triggered (usually for restarting services):

```yaml
handlers:
  - name: restart apache2
    systemd:
      name: apache2
      state: restarted
```

### 📋 **Variables**
Dynamic values that can be customized per environment:

```yaml
vars:
  apache_version: "2.4"
  php_version: "8.3"
  mysql_root_password: "{{ vault_mysql_password }}"
```

---

## 🎭 Ansible Roles Explained

### **What are Roles?**
Roles are the building blocks of modern Ansible automation. They provide:
- **Reusability**: Use the same role across multiple playbooks
- **Organization**: Keep related tasks, variables, and files together
- **Modularity**: Update individual components without affecting others
- **Sharing**: Easily share roles with the community via Ansible Galaxy

### **Role Structure:**
```
roles/
└── role_name/
    ├── tasks/
    │   └── main.yml          # Main task list
    ├── handlers/
    │   └── main.yml          # Service handlers
    ├── templates/
    │   └── config.j2         # Template files
    ├── files/
    │   └── static_file.txt   # Static files
    ├── vars/
    │   └── main.yml          # Role variables
    ├── defaults/
    │   └── main.yml          # Default variables
    └── meta/
        └── main.yml          # Role metadata and dependencies
```

### **How Roles Work:**
1. **tasks/main.yml** contains the main automation logic
2. **vars/main.yml** defines role-specific variables
3. **templates/** contains Jinja2 templates for dynamic configuration
4. **files/** contains static files to copy to target systems
5. **handlers/main.yml** defines service restart and notification logic

---

## 🔧 Variables and Configuration

### **Variable Precedence (Highest to Lowest):**
1. **Extra vars** (`-e "var=value"` command line)
2. **Task vars** (defined in tasks)
3. **Block vars** (defined in blocks)
4. **Role vars** (roles/role_name/vars/main.yml)
5. **Play vars** (defined in playbook)
6. **Host vars** (host_vars/hostname.yml)
7. **Group vars** (group_vars/groupname.yml)
8. **Role defaults** (roles/role_name/defaults/main.yml)

### **Variable Examples:**
```yaml
# Simple variables
apache_port: 80
mysql_root_password: "SecurePassword123"

# Lists
packages_to_install:
  - apache2
  - php8.3
  - mysql-server

# Dictionaries
database_config:
  name: "app_database"
  user: "app_user"
  password: "{{ vault_db_password }}"
  privileges: "ALL"

# Conditional variables
is_production: false
debug_mode: "{{ not is_production }}"
```

### **Using Variables in Templates:**
```jinja2
# apache.conf.j2 template
<VirtualHost *:{{ apache_port }}>
    ServerName {{ ansible_fqdn }}
    DocumentRoot /var/www/html
    
    {% if debug_mode %}
    LogLevel debug
    {% else %}
    LogLevel warn
    {% endif %}
</VirtualHost>
```

---

## 🏷️ Tags and Selective Execution

### **What are Tags?**
Tags allow you to run specific parts of a playbook without executing everything:

```yaml
- name: Configure database
  include_role:
    name: database
  tags: 
    - database
    - db

- name: Configure web server
  include_role:
    name: web_stack
  tags:
    - web
    - apache
```

### **Using Tags:**
```bash
# Run only database tasks
ansible-playbook site_clean.yml --tags database

# Run multiple tags
ansible-playbook site_clean.yml --tags "database,web"

# Skip specific tags
ansible-playbook site_clean.yml --skip-tags vulnerabilities

# List all available tags
ansible-playbook site_clean.yml --list-tags
```

### **Special Tags:**
- `always`: Always runs regardless of tag selection
- `never`: Never runs unless explicitly requested
- `tagged`: Run all tagged tasks
- `untagged`: Run all untagged tasks

---

## 📁 Project Structure Overview

```
ansible-control-node/
├── 📁 documentation/              # Project documentation
│   ├── ansible-guide.md          # This comprehensive Ansible guide
│   ├── ANSIBLE_ROLES_GUIDE.md    # Role-specific documentation
│   └── SCHOOL_DOCUMENTATIE.md    # Dutch educational documentation
├── 📁 playbooks/                 # Ansible orchestration playbooks
│   ├── site_clean.yml            # Main clean deployment orchestrator
│   ├── site_vulnerable.yml       # Vulnerable deployment orchestrator
│   ├── 02_introduce_vulnerabilities.yml  # Vulnerability injection
│   └── roles/                    # Ansible roles directory
│       ├── base_system/          # System foundation and hardening
│       ├── web_stack/            # Apache, Nginx, PHP configuration
│       ├── database/             # MySQL/MariaDB setup
│       ├── applications/         # Web applications (DVWA, etc.)
│       ├── system_services/      # SSH, FTP, monitoring services
│       └── vulnerabilities/      # Security vulnerability injection
├── 📁 scripts/                   # PowerShell deployment automation
│   ├── launch.ps1               # Main launcher with prerequisites
│   ├── run_clean.ps1            # Clean deployment script
│   ├── run_vulnerable.ps1       # Vulnerable deployment script
│   └── run_vulnerability_profile.ps1  # Profile-based deployment
├── 📁 Keys/                      # SSH authentication
│   ├── vps_key                  # Private SSH key
│   └── vps_key.pub              # Public SSH key
├── 🐳 Dockerfile                # Ansible container configuration
├── inventory.ini                # Target server inventory
├── vulnerability-profiles.yml   # Vulnerability profile configurations
├── ansible.cfg                  # Ansible configuration settings
└── launch.ps1                   # Root-level quick launcher
```

### **Key Configuration Files:**

#### **ansible.cfg**
```ini
[defaults]
host_key_checking = False
timeout = 30
pipelining = True

[ssh_connection]
ssh_args = -o ControlMaster=auto -o ControlPersist=60s
control_path = ~/.ssh/ansible-%%h-%%p-%%r
```

#### **vulnerability-profiles.yml**
```yaml
profiles:
  secure_profile:
    name: "Secure Learning Environment"
    description: "Latest stable versions for safe learning"
    vulnerabilities: []
    
  vulnerable_profile:
    name: "Penetration Testing Environment"
    description: "Known CVEs for security testing"
    vulnerabilities:
      - outdated_packages
      - weak_passwords
      - insecure_services
```

---

## 🎯 How We Use Ansible in This Project

### **Our Architecture:**
```
Windows Host (PowerShell Scripts)
    ↓
Docker Container (Ansible Control Node)
    ↓ SSH (port 2222)
VirtualBox Ubuntu VM (Target Server)
```

### **Role-Based Educational Lab Setup:**
Our project uses a modular role-based architecture to create comprehensive environments:

#### **Base System Role** (`base_system`)
- System updates and security hardening
- Essential package installation
- User management and sudo configuration
- SSH security configuration
- Firewall setup (UFW)

#### **Web Stack Role** (`web_stack`)
- Apache HTTP Server installation and configuration
- PHP 8.3 with essential modules
- SSL/TLS certificate management
- Virtual host configuration
- Security headers and modules

#### **Database Role** (`database`)
- MariaDB/MySQL server installation
- Database and user creation
- Security configuration
- Backup and monitoring setup
- Performance tuning

#### **Applications Role** (`applications`)
- DVWA (Damn Vulnerable Web Application)
- Gitea Git server for version control
- DokuWiki for documentation
- MantisBT for bug tracking
- Custom web applications

#### **System Services Role** (`system_services`)
- SSH server hardening
- FTP services (vsftpd)
- System monitoring (Cockpit)
- Log management
- Mail services (Postfix)

#### **Vulnerabilities Role** (`vulnerabilities`)
- Intentional security misconfigurations
- Outdated package versions
- Weak authentication mechanisms
- Insecure service configurations
- Known CVE implementations

### **Deployment Scenarios:**

#### **1. Clean/Secure Environment** (`site_clean.yml`)
- Production-ready configurations
- Security best practices implemented
- Latest stable software versions
- Comprehensive logging and monitoring
- Hardened system settings

#### **2. Vulnerable Environment** (`site_vulnerable.yml`)
- Educational security testing environment
- Known vulnerabilities for penetration testing
- Misconfigured services for learning
- Weak authentication for exploitation practice
- Intentional security gaps

#### **3. Profile-Based Deployment**
- Customizable vulnerability levels
- Specific CVE implementations
- Targeted learning scenarios
- Gradual complexity increase

---

## 🚀 Running the Playbooks

### **Method 1: PowerShell Script Launcher (Recommended)**
Our project includes an intelligent launcher system with prerequisite checking:

```powershell
# Quick deployment from root directory
.\launch.ps1 clean                    # Deploy secure environment
.\launch.ps1 vulnerable              # Deploy vulnerable environment
.\launch.ps1 profile secure_profile  # Deploy with specific profile
.\launch.ps1 help                    # Show comprehensive help

# Verbose mode for debugging
.\launch.ps1 clean -Verbose
.\launch.ps1 profile vulnerable_profile -Verbose
```

### **Method 2: Direct Script Execution**
```powershell
# Navigate to scripts directory
cd scripts

# Run specific deployment scripts
.\run_clean.ps1                      # Secure deployment
.\run_vulnerable.ps1                 # Vulnerable deployment
.\run_vulnerability_profile.ps1 -Profile secure_profile
```

### **Method 3: Docker Commands with Role Tags**
```powershell
# Deploy specific roles only
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest `
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --tags base_system

# Deploy multiple roles
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest `
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --tags "web_stack,database"

# Skip vulnerability injection
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest `
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --skip-tags vulnerabilities

# Check what would be changed (dry run)
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest `
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --check

# List all available tags
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest `
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --list-tags
```

### **Method 4: Interactive Container for Advanced Users**
```powershell
# Enter container for manual control
docker run --rm -it -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest /bin/bash

# Inside container - full Ansible command access:
ansible-playbook -i inventory.ini playbooks/site_clean.yml
ansible-inventory -i inventory.ini --list
ansible ubuntu-vm -i inventory.ini -m setup
ansible-vault encrypt vars/secrets.yml
```

### **Advanced Deployment Options:**

#### **Limit Execution to Specific Hosts:**
```bash
# Only run on specific hosts
ansible-playbook -i inventory.ini playbooks/site_clean.yml --limit ubuntu-vm
```

#### **Start at Specific Task:**
```bash
# Resume from specific task
ansible-playbook -i inventory.ini playbooks/site_clean.yml --start-at-task "Install Apache"
```

#### **Step-by-Step Execution:**
```bash
# Confirm each task before execution
ansible-playbook -i inventory.ini playbooks/site_clean.yml --step
```

#### **Extra Variables:**
```bash
# Override variables at runtime
ansible-playbook -i inventory.ini playbooks/site_clean.yml -e "php_version=8.2"
```

---

## 🔧 Understanding Our Setup

### **inventory.ini Configuration:**
```ini
[lab_servers]
ubuntu-vm ansible_host=host.docker.internal 
ansible_port=2222 
ansible_user=student 
ansible_ssh_private_key_file=/root/.ssh/vps_key 
ansible_become_password=ColdBrew
```

**Breakdown:**
- `ubuntu-vm`: Hostname we use in playbooks
- `ansible_host=host.docker.internal`: Docker's way to reach the Windows host
- `ansible_port=2222`: VirtualBox port forwarding from guest SSH (22) to host (2222)
- `ansible_user=student`: Username on the Ubuntu VM
- `ansible_ssh_private_key_file`: Path to SSH private key
- `ansible_become_password`: Sudo password for privilege escalation

### **Key Features of Our Role-Based Playbooks:**

#### **Site-wide Orchestration (site_clean.yml):**
```yaml
---
- name: Deploy Complete Lab Environment
  hosts: lab_servers
  become: yes
  vars_files:
    - vulnerability-profiles.yml
  
  roles:
    - role: base_system
      tags: base_system
    - role: web_stack
      tags: web_stack
    - role: database
      tags: database
    - role: applications
      tags: applications
    - role: system_services
      tags: system_services
```

#### **Variable Management Across Roles:**
```yaml
# Global variables in playbook
vars:
  deployment_environment: "{{ deployment_env | default('development') }}"
  enable_security_hardening: true
  
# Role-specific variables
web_stack_vars:
  apache_version: "2.4"
  php_version: "8.3"
  ssl_enabled: "{{ enable_security_hardening }}"

database_vars:
  mysql_version: "10.11"
  mysql_root_password: "{{ vault_mysql_password }}"
  enable_ssl: "{{ enable_security_hardening }}"
```

#### **Conditional Role Execution:**
```yaml
- name: Deploy vulnerabilities (only in test environment)
  include_role:
    name: vulnerabilities
  when: 
    - deployment_environment == "testing"
    - enable_vulnerabilities | default(false)
  tags: 
    - vulnerabilities
    - never  # Never run unless explicitly requested
```

#### **Role Dependencies and Ordering:**
```yaml
# In roles/applications/meta/main.yml
dependencies:
  - role: base_system
  - role: web_stack
  - role: database
```

#### **Error Handling and Recovery:**
```yaml
- name: Ensure critical services are running
  systemd:
    name: "{{ item }}"
    state: started
    enabled: yes
  loop:
    - apache2
    - mysql
    - ssh
  register: service_status
  failed_when: false
  
- name: Report service failures
  debug:
    msg: "Failed to start {{ item.item }}"
  when: item.failed
  loop: "{{ service_status.results }}"
```

## 🔍 Troubleshooting Common Issues

### **Connection and Authentication Issues:**

#### **Test Basic Connectivity:**
```bash
# Test ping to all hosts
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible all -i inventory.ini -m ping

# Test specific host with verbose output
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m ping -vvv

# Check SSH connectivity manually
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ssh -i Keys/vps_key -p 2222 student@host.docker.internal
```

#### **SSH Key and Permission Issues:**
```bash
# Check SSH key permissions (inside container)
ls -la /ansible/Keys/vps_key

# Fix permissions if needed
chmod 600 /ansible/Keys/vps_key
chmod 644 /ansible/Keys/vps_key.pub

# Test SSH key authentication
ssh-keygen -y -f /ansible/Keys/vps_key
```

### **Role and Tag Issues:**

#### **List Available Tags:**
```bash
# See all tags in playbook
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --list-tags

# Check which tasks have specific tags
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --tags web_stack --list-tasks
```

#### **Debug Role Execution:**
```bash
# Run specific role with maximum verbosity
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --tags base_system -vvv

# Check role variables
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --tags base_system --check -vv
```

### **Service and Application Issues:**

#### **Check Service Status:**
```bash
# Check all services status
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m shell -a "systemctl status apache2 mysql ssh"

# Check specific service logs
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m shell -a "journalctl -u apache2 -n 20"

# Check listening ports
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m shell -a "netstat -tlnp"
```

#### **Validate Web Services:**
```bash
# Test web server response
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m uri -a "url=http://localhost/"

# Check PHP functionality
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m shell -a "php -v"
```

### **Variable and Configuration Issues:**

#### **Debug Variables:**
```bash
# Show all facts about target system
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible ubuntu-vm -i inventory.ini -m setup

# Show specific variables
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml -e "debug_vars=true" --check
```

#### **Validate Inventory:**
```bash
# Check inventory parsing
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-inventory -i inventory.ini --list

# Check host variables
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-inventory -i inventory.ini --host ubuntu-vm
```

### **Docker and Container Issues:**

#### **Container Debugging:**
```powershell
# Rebuild container with no cache
docker build --no-cache -t ansible-control-node .

# Check container logs
docker logs <container-id>

# Inspect container configuration
docker inspect ansible-control-node

# Check if ports are accessible from host
Test-NetConnection -ComputerName localhost -Port 2222
```

#### **Volume Mount Issues:**
```powershell
# Verify current directory is correct
Get-Location

# Check if files are accessible in container
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest ls -la

# Test file permissions in container
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ls -la Keys/
```

### **Network and Port Forwarding Issues:**

#### **VirtualBox Configuration:**
1. Ensure VirtualBox port forwarding is configured:
   - Guest IP: 10.0.2.15 (or your VM's IP)
   - Guest Port: 22
   - Host IP: 127.0.0.1
   - Host Port: 2222

2. Test from Windows host:
   ```cmd
   telnet localhost 2222
   ```

3. Check VM network configuration:
   ```bash
   # Inside VM
   ip addr show
   systemctl status ssh
   ```

### **Performance and Timeout Issues:**

#### **Increase Timeouts:**
```bash
# Add timeout parameters
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --timeout=300

# Use ansible.cfg for persistent settings
echo "timeout = 300" >> ansible.cfg
```

#### **Parallel Execution:**
```bash
# Limit parallel processes
docker run --rm -v "${PWD}:/ansible" -w /ansible ansible/ansible:latest \
  ansible-playbook -i inventory.ini playbooks/site_clean.yml --forks=1
```

### **Common Error Solutions:**

| Error | Solution |
|-------|----------|
| "Permission denied (publickey)" | Check SSH key permissions and path |
| "Host key verification failed" | Set `host_key_checking = False` in ansible.cfg |
| "No such file or directory" | Verify file paths and volume mounts |
| "Connection timed out" | Check network connectivity and port forwarding |
| "sudo: a password is required" | Verify `ansible_become_password` in inventory |
| "Module not found" | Ensure all required Ansible modules are available |
| "Port 2222 already in use" | Stop conflicting services or change port |

---

## 📚 Further Learning

### **Official Ansible Resources:**
- [Ansible Documentation](https://docs.ansible.com/) - Complete official documentation
- [Ansible Galaxy](https://galaxy.ansible.com/) - Community roles and collections
- [Ansible Best Practices](https://docs.ansible.com/ansible/latest/user_guide/playbooks_best_practices.html) - Official best practices guide
- [Ansible Vault](https://docs.ansible.com/ansible/latest/user_guide/vault.html) - Secrets management
- [Ansible Collections](https://docs.ansible.com/ansible/latest/user_guide/collections_using.html) - Modern content organization

### **Essential Modules to Master:**

#### **System Management:**
- `apt` / `yum` / `dnf`: Package management across distributions
- `systemd` / `service`: Service management and control
- `user` / `group`: User and group management
- `file` / `copy` / `template`: File operations and templating
- `mount`: Filesystem mounting and management

#### **Network and Security:**
- `ufw` / `firewalld`: Firewall management
- `openssh_keypair`: SSH key generation
- `authorized_key`: SSH key deployment
- `ssl_certificate`: SSL/TLS certificate management

#### **Database and Applications:**
- `mysql_db` / `mysql_user`: MySQL database management
- `postgresql_db` / `postgresql_user`: PostgreSQL management
- `git`: Git repository operations
- `get_url` / `unarchive`: File download and extraction

#### **Advanced Modules:**
- `docker_container` / `docker_image`: Container management
- `cron`: Scheduled task management
- `lvg` / `lvol`: LVM volume management
- `timezone`: System timezone configuration

### **Advanced Ansible Concepts:**

#### **Ansible Vault for Secrets Management:**
```bash
# Encrypt sensitive files
ansible-vault encrypt vars/secrets.yml

# Edit encrypted files
ansible-vault edit vars/secrets.yml

# Run playbook with vault password
ansible-playbook site.yml --ask-vault-pass

# Use password file
ansible-playbook site.yml --vault-password-file ~/.vault_pass
```

#### **Dynamic Inventory:**
```python
# custom_inventory.py
#!/usr/bin/env python3
import json

inventory = {
    'lab_servers': {
        'hosts': ['ubuntu-vm'],
        'vars': {
            'ansible_user': 'student',
            'ansible_become': True
        }
    },
    '_meta': {
        'hostvars': {
            'ubuntu-vm': {
                'ansible_host': 'host.docker.internal',
                'ansible_port': 2222
            }
        }
    }
}

print(json.dumps(inventory))
```

#### **Custom Modules and Plugins:**
```python
# library/custom_module.py
from ansible.module_utils.basic import AnsibleModule

def main():
    module = AnsibleModule(
        argument_spec=dict(
            name=dict(type='str', required=True),
            state=dict(type='str', default='present', choices=['present', 'absent'])
        )
    )
    
    # Module logic here
    module.exit_json(changed=True, msg="Custom action completed")

if __name__ == '__main__':
    main()
```

#### **Jinja2 Templating Advanced Usage:**
```jinja2
{# Complex template with loops and conditions #}
{% for user in users %}
{% if user.state == 'present' %}
{{ user.name }}:{{ user.uid }}:{{ user.gid }}:{{ user.comment }}:{{ user.home }}:{{ user.shell }}
{% endif %}
{% endfor %}

{# Filters and tests #}
{{ ansible_hostname | upper }}
{{ packages | selectattr('state', 'equalto', 'present') | list }}
{% if ansible_distribution is match('Ubuntu') %}
# Ubuntu-specific configuration
{% endif %}
```

### **Testing and Quality Assurance:**

#### **Ansible Lint:**
```bash
# Install ansible-lint
pip install ansible-lint

# Lint playbooks
ansible-lint playbooks/site_clean.yml

# Lint all YAML files
ansible-lint .
```

#### **Molecule Testing Framework:**
```bash
# Install molecule
pip install molecule[docker]

# Initialize new role with tests
molecule init role my_role

# Test role
molecule test
```

#### **Playbook Testing Strategies:**
```yaml
- name: Verify web server is responding
  uri:
    url: "http://{{ ansible_default_ipv4.address }}"
    status_code: 200
  register: web_test
  
- name: Assert web server test passed
  assert:
    that:
      - web_test.status == 200
    fail_msg: "Web server is not responding correctly"
```

### **Production Deployment Considerations:**

#### **Role Versioning and Dependencies:**
```yaml
# requirements.yml
roles:
  - name: geerlingguy.apache
    version: "3.2.0"
  - src: https://github.com/example/custom-role.git
    version: "v1.0.0"
    name: custom-role

collections:
  - name: community.general
    version: ">=4.0.0"
```

#### **Environment-Specific Variables:**
```
group_vars/
├── all/
│   ├── common.yml
│   └── vault.yml
├── production/
│   ├── main.yml
│   └── secrets.yml
└── development/
    └── main.yml
```

#### **CI/CD Integration:**
```yaml
# .github/workflows/ansible.yml
name: Ansible CI
on: [push, pull_request]
jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Lint Ansible Playbooks
        uses: ansible/ansible-lint-action@v6
  
  test:
    runs-on: ubuntu-latest
    steps:
      - name: Test with Molecule
        run: molecule test
```

### **Ansible Ecosystem Tools:**

- **AWX/Ansible Tower**: Web-based management interface
- **Ansible Runner**: Python library for running Ansible
- **Ansible Builder**: Create custom execution environments
- **Ansible Navigator**: Enhanced command-line experience
- **Semaphore**: Open-source alternative to Ansible Tower

---

## 🎯 Summary

This comprehensive guide demonstrates how Ansible serves as a powerful Infrastructure as Code (IaC) solution that can:

### **Core Capabilities:**
1. **Automate Complex Deployments**: From simple package installation to complete multi-tier application stacks
2. **Ensure Consistency**: Identical configurations across development, testing, and production environments
3. **Reduce Human Error**: Eliminate manual configuration mistakes through automation
4. **Provide Living Documentation**: Playbooks serve as both automation and documentation
5. **Enable Rapid Recovery**: Quickly rebuild environments from code
6. **Support Scalability**: Manage everything from single servers to thousands of hosts

### **Educational Value:**
- **Modern DevOps Practices**: Learn industry-standard automation techniques
- **Infrastructure as Code**: Understand version-controlled infrastructure management
- **Security Testing**: Safe environment for exploring vulnerabilities and hardening
- **System Administration**: Hands-on experience with Linux server management
- **Configuration Management**: Best practices for maintaining consistent systems

### **Role-Based Architecture Benefits:**
- **Modularity**: Independent, reusable components for different functionalities
- **Maintainability**: Easier updates and debugging with isolated roles
- **Flexibility**: Mix and match roles for different deployment scenarios
- **Collaboration**: Teams can work on different roles simultaneously
- **Testing**: Individual roles can be tested in isolation

### **Real-World Applications:**
- **Development Environments**: Consistent local development setups
- **Continuous Integration**: Automated testing environment provisioning
- **Disaster Recovery**: Rapid infrastructure recreation from code
- **Compliance**: Ensure systems meet security and regulatory requirements
- **Multi-Cloud Deployments**: Abstract infrastructure differences across providers

### **Key Learning Outcomes:**
By working with this Ansible lab environment, we gain:
- **Practical automation skills** applicable to any IT environment
- **Understanding of Infrastructure as Code** principles and benefits
- **Security awareness** through controlled vulnerability exploration
- **Modern deployment practices** used in enterprise environments
- **Troubleshooting abilities** for complex distributed systems

---

*This guide provides comprehensive coverage of Ansible concepts, features, and best practices within the context of our HBO-ICT Lab Environment. The role-based architecture demonstrated here reflects modern enterprise practices and provides an excellent foundation for further automation learning.*

**For role-specific implementation details, refer to:** [`ANSIBLE_ROLES_GUIDE.md`](ANSIBLE_ROLES_GUIDE.md)
**For Initial Setup details, refer to:**[`SETUP_DOCUMENTATION.md`](SETUP_DOCUMENTATION.md)