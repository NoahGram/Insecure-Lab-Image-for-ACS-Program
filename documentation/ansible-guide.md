# Ansible Guide for HBO-ICT Lab Environment

## 📋 Table of Contents
1. [What is Ansible?](#what-is-ansible)
2. [How Ansible Works](#how-ansible-works)
3. [Key Ansible Concepts](#key-ansible-concepts)
4. [Project Structure Overview](#project-structure-overview)
5. [How We Use Ansible in This Project](#how-we-use-ansible-in-this-project)
6. [Running the Playbooks](#running-the-playbooks)
7. [Understanding Our Setup](#understanding-our-setup)
8. [Troubleshooting Common Issues](#troubleshooting-common-issues)

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
- name: Install Apache web server
  apt:
    name: apache2
    state: present
```

### 📝 **Task**
A single action to perform (install package, copy file, start service, etc.)

### 🎭 **Module**
Pre-built functions that perform specific operations:
- `apt`: Package management
- `systemd`: Service management
- `copy`: File operations
- `mysql_user`: Database user management

### 📊 **Inventory**
A file listing target servers and their connection details:

```ini
[lab_servers]
ubuntu-vm ansible_host=host.docker.internal ansible_port=2222
```

### 🎯 **Host**
The target server where tasks will be executed.

### 🔄 **Handler**
Special tasks that only run when triggered (usually for restarting services).

---

## 📁 Project Structure Overview

```
ansible-control-node/
├── 📁 documentation/           # Project documentation
│   └── ansible-guide.md        # This guide
├── 📁 playbooks/              # Ansible playbooks
│   ├── 01_clean_image_base.yml # Main deployment playbook
│   ├── 02_introduce_vulnerabilities.yml
│   ├── initial_setup.yml      # Basic server setup
│   └── test_ping.yml          # Connection testing
├── 📁 Keys/                   # SSH authentication
│   ├── vps_key               # Private key
│   └── vps_key.pub           # Public key
├── 🐳 Dockerfile             # Container configuration
├── inventory.ini             # Server inventory
└── run_*.ps1                 # PowerShell execution scripts
```

---

## 🎯 How We Use Ansible in This Project

### **Our Architecture:**
```
Windows Host
    ↓
Docker Container (Ansible Control Node)
    ↓ SSH (port 2222)
VirtualBox Ubuntu VM (Target Server)
```

### **Educational Lab Setup:**
Our project creates a complete learning environment with:
- **Web Server**: Apache with PHP
- **Database**: MariaDB
- **Version Control**: Gitea Git server
- **Documentation**: DokuWiki
- **Bug Tracking**: MantisBT
- **System Monitoring**: Cockpit
- **Security**: Wazuh agent
- **Mail**: Postfix

### **Two Main Scenarios:**
1. **Clean Image** (`01_clean_image_base.yml`): Secure, production-like setup
2. **Vulnerable Image** (`02_introduce_vulnerabilities.yml`): For security testing

---

## 🚀 Running the Playbooks

### **Method 1: PowerShell Scripts (Recommended)**
```powershell
# Deploy clean, secure environment
.\run_clean.ps1

# Deploy vulnerable environment for security testing
.\run_vulnerable.ps1
```

### **Method 2: Direct Docker Commands**
```powershell
# Build the container
docker build -t ansible-control-node .

# Run clean deployment
docker run --rm -it ansible-control-node ansible-playbook -i inventory.ini playbooks/01_clean_image_base.yml

# Test connectivity
docker run --rm -it ansible-control-node ansible-playbook -i inventory.ini playbooks/test_ping.yml
```

### **Method 3: Interactive Container**
```powershell
# Enter container for manual control
docker run --rm -it ansible-control-node /bin/bash

# Inside container:
ansible-playbook -i inventory.ini playbooks/01_clean_image_base.yml
```

---

## 🔧 Understanding Our Setup

### **inventory.ini Configuration:**
```ini
[lab_servers]
ubuntu-vm ansible_host=host.docker.internal ansible_port=2222 ansible_user=student ansible_ssh_private_key_file=/root/.ssh/vps_key ansible_become_password=ColdBrew
```

**Breakdown:**
- `ubuntu-vm`: Hostname we use in playbooks
- `ansible_host=host.docker.internal`: Docker's way to reach the Windows host
- `ansible_port=2222`: VirtualBox port forwarding from guest SSH (22) to host (2222)
- `ansible_user=student`: Username on the Ubuntu VM
- `ansible_ssh_private_key_file`: Path to SSH private key
- `ansible_become_password`: Sudo password for privilege escalation

### **Key Features of Our Main Playbook:**

#### **Version Management:**
```yaml
vars:
  apache_version: "2.4"
  php_version: "8.3"
  mysql_version: "10.11"
  gitea_version: "1.21.0"
```

#### **Idempotent Operations:**
```yaml
- name: Check if MariaDB is already installed
  command: dpkg -l mariadb-server
  register: mariadb_installed
  failed_when: false
  
- name: Install MariaDB only if needed
  apt:
    name: mariadb-server
    state: present
  when: mariadb_installed.rc != 0
```

#### **Service Verification:**
```yaml
- name: Wait for Apache to be ready
  wait_for:
    port: 80
    host: localhost
    timeout: 30
```

---

## 🎓 Educational Benefits

### **For Students Learning Linux Administration:**
- **Declarative Configuration**: See exactly how services should be configured
- **Best Practices**: Learn proper service installation and configuration
- **Automation**: Understand how to automate repetitive tasks
- **Documentation**: Self-documenting infrastructure setup

### **For Security Education:**
- **Clean vs Vulnerable**: Compare secure and insecure configurations
- **Infrastructure as Code**: Version control your server configurations
- **Consistent Environments**: Every student gets identical lab setup
- **Rapid Deployment**: Quick environment reset for exercises

---

## 🔍 Troubleshooting Common Issues

### **Connection Issues:**
```bash
# Test basic connectivity
ansible-playbook -i inventory.ini playbooks/test_ping.yml

# Debug SSH connection
ansible ubuntu-vm -i inventory.ini -m ping -vvv
```

### **Permission Issues:**
```bash
# Check SSH key permissions
ls -la /root/.ssh/vps_key

# Should be 600 (readable only by owner)
chmod 600 /root/.ssh/vps_key
```

### **Service Failures:**
```bash
# Check specific service status
ansible ubuntu-vm -i inventory.ini -m shell -a "systemctl status apache2"

# View service logs
ansible ubuntu-vm -i inventory.ini -m shell -a "journalctl -u apache2 -n 20"
```

### **Port Forwarding Issues:**
1. Ensure VirtualBox port forwarding is configured:
   - Guest IP: 10.0.2.15
   - Guest Port: 22
   - Host IP: 127.0.0.1
   - Host Port: 2222

2. Test from Windows host:
   ```cmd
   telnet localhost 2222
   ```

### **Docker Issues:**
```powershell
# Rebuild container if needed
docker build --no-cache -t ansible-control-node .

# Check container logs
docker logs <container-id>
```

---

## 📚 Further Learning

### **Official Ansible Resources:**
- [Ansible Documentation](https://docs.ansible.com/)
- [Ansible Galaxy](https://galaxy.ansible.com/) - Community modules and roles
- [Ansible Best Practices](https://docs.ansible.com/ansible/latest/user_guide/playbooks_best_practices.html)

### **Key Modules to Learn:**
- `apt` / `yum`: Package management
- `systemd`: Service management
- `copy` / `template`: File operations
- `user` / `group`: User management
- `mysql_db` / `mysql_user`: Database management
- `get_url`: Download files
- `unarchive`: Extract archives

### **Advanced Topics:**
- **Ansible Vault**: Encrypting sensitive data
- **Roles**: Reusable automation components
- **Dynamic Inventory**: Automatically discover servers
- **Ansible Tower/AWX**: Web-based Ansible management

---

## 🎯 Summary

This project demonstrates how Ansible can:
1. **Automate** complex server deployments
2. **Ensure consistency** across environments
3. **Reduce human error** through automation
4. **Provide documentation** through code
5. **Enable rapid recovery** from failures

By using Infrastructure as Code principles, we create reproducible, version-controlled, and maintainable lab environments perfect for educational purposes.

---

*This guide is part of the HBO-ICT Lab Environment project. For questions or contributions, please refer to the project repository.*