# Ansible Role-Based Architecture Guide

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Why Role-Based Structure](#why-role-based-structure)
3. [Role Structure Explained](#role-structure-explained)
4. [Current Role Implementation](#current-role-implementation)
5. [Usage for New Users](#usage-for-new-users)
6. [Adding New Roles](#adding-new-roles)
7. [Customization Guide](#customization-guide)

## Architecture Overview

This project implements a **modular, role-based Ansible architecture** that transforms a monolithic playbook into maintainable, reusable components. Each role has a single responsibility and can be developed, tested, and maintained independently.

### Design Principles
- **Single Responsibility**: Each role handles one specific aspect of the system
- **Modularity**: Roles can be used independently or combined
- **Reusability**: Roles can be shared across different projects
- **Maintainability**: Easy to update, debug, and extend individual components
- **Scalability**: Simple to add new functionality without affecting existing code

## Why Role-Based Structure

### Before: Monolithic Playbook Problems
- **Single Large File**: One massive playbook (1000+ lines) handling everything
- **Hard to Maintain**: Changes affect multiple unrelated components
- **No Reusability**: Code duplication across different deployments
- **Difficult Testing**: Must test entire stack for small changes
- **Poor Collaboration**: Multiple developers editing same large file

### After: Role-Based Benefits
- **Separation of Concerns**: Each role handles one specific function
- **Independent Development**: Teams can work on different roles simultaneously
- **Easy Testing**: Test individual roles in isolation
- **Reusable Components**: Share roles across projects
- **Clear Dependencies**: Explicit role ordering and relationships
- **Simplified Debugging**: Issues isolated to specific roles

## Role Structure Explained

### Standard Ansible Role Directory Structure
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
├── meta/                # Role metadata and dependencies
│   └── main.yml         # Dependencies, supported platforms
└── README.md            # Role documentation
```

### Project Structure
```
ansible-control-node/
├── playbooks/
│   ├── roles/                    # All roles directory
│   │   ├── base_system/          # System foundation layer
│   │   ├── web_stack/            # Web server infrastructure
│   │   ├── database/             # Database services
│   │   ├── applications/         # Service applications
│   │   ├── system_services/      # System monitoring/services
│   │   └── vulnerabilities/      # Security testing components
│   ├── site_clean.yml            # Main Manager playbook
│   └── site_vulnerable.yml       # Alternative configuration
├── vulnerability-profiles.yml    # Version configurations
├── inventory.ini                 # Target host definitions
└── ansible.cfg                   # Ansible behavior settings
```

### Role Execution Order
```yaml
# Logical dependency chain
1. base_system      → System preparation, packages
2. web_stack        → Apache, PHP (depends on base)
3. database         → MariaDB (depends on base)
4. applications     → Web apps (depends on web + database)
5. system_services  → Monitoring (depends on base)
6. vulnerabilities  → Security testing (optional)
```

## Current Role Implementation

### Role Hierarchy and Dependencies

```
┌─────────────────┐
│   base_system   │ ← Foundation (OS updates, core packages)
└─────────┬───────┘
          │
    ┌─────▼─────┬─────────────┬─────────────────┐
    │           │             │                 │
┌───▼─────┐ ┌───▼──────┐ ┌────▼──────────┐ ┌───▼──────────┐
│web_stack│ │ database │ │system_services│ │vulnerabilities│
└─────────┘ └─────┬────┘ └───────────────┘ └──────────────┘
                  │
              ┌───▼─────────┐
              │applications │ ← Depends on web + database
              └─────────────┘
```

### 🏗️ **base_system** - Foundation Layer
```
base_system/
├── tasks/main.yml        # System updates, core packages
└── defaults/main.yml     # Default version variables
```
**Responsibility**: System preparation, package management, dependencies
**Dependencies**: None (runs first)
**Provides**: Clean system foundation for other roles

### 🌐 **web_stack** - Web Infrastructure
```
web_stack/
├── tasks/main.yml        # Apache + PHP installation
├── handlers/main.yml     # Apache restart handler
└── templates/index.html.j2  # Dynamic landing page
```
**Responsibility**: Apache HTTP server, PHP runtime, web server configuration
**Dependencies**: base_system
**Provides**: Web server foundation for applications

### 🗄️ **database** - Data Layer
```
database/
└── tasks/main.yml        # MariaDB installation, security config
```
**Responsibility**: Database server, user management, application databases
**Dependencies**: base_system  
**Provides**: Database services for applications

### 📱 **applications** - Business Logic
```
applications/
├── tasks/main.yml        # Gitea, DokuWiki, MantisBT deployment
└── handlers/main.yml     # Application service handlers
```
**Responsibility**: Web applications (Gitea, DokuWiki, MantisBT)
**Dependencies**: base_system, web_stack, database
**Provides**: End-user applications and services

### ⚙️ **system_services** - Monitoring & Services
```
system_services/
├── tasks/main.yml        # Cockpit, Wazuh, Postfix
└── handlers/main.yml     # Service management handlers
```
**Responsibility**: System monitoring, security tools, email services
**Dependencies**: base_system
**Provides**: Administrative and monitoring capabilities

### 🔓 **vulnerabilities** - Security Testing (Optional)
```
vulnerabilities/
├── tasks/main.yml        # Vulnerable scripts, configs
├── handlers/main.yml     # Security service handlers
└── templates/            # Vulnerable code templates
```
**Responsibility**: Intentional security weaknesses for educational testing
**Dependencies**: applications (modifies existing apps)
**Provides**: Controlled vulnerabilities for security learning

## Usage for New Users

### Understanding the Manager Playbook

The main playbook (`site_clean.yml`) manages all roles:

```yaml
---
- name: Deploy Clean Base Lab Environment (Role-Based)
  hosts: vps_lab
  gather_facts: yes
  
  # Variable loading and profile setup
  vars_files:
    - ../vulnerability-profiles.yml
  
  pre_tasks:
    - name: Set vulnerability profile
      set_fact:
        current_profile: "{{ vulnerability_profile | default('secure_profile') }}"
  
  # Role execution in dependency order
  roles:
    - base_system      # Must run first
    - web_stack        # Depends on base_system
    - database         # Depends on base_system
    - applications     # Depends on web_stack + database
    - system_services  # Depends on base_system
    # - vulnerabilities # Optional, uncomment if needed
```

### Basic Usage Commands

**⚠️ Important**: This project uses a centralized `.env` configuration file. All paths and credentials are automatically loaded from `.env` - no manual editing of commands required!

#### Prerequisites
1. Create and configure your `.env` file (see main README.md)
2. Test connection: `.\scripts\test_connection.ps1` (Windows) or `./scripts/test_connection.sh` (Linux/Mac)

#### 1. **Deploy All Roles** (Complete Environment)

**Windows:**
```powershell
# Using convenience script
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_clean.ps1

# Or using generic playbook runner
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml
```

**Linux/Mac:**
```bash
# Using generic playbook runner
./scripts/run_playbook.sh playbooks/site_clean.yml
```

#### 2. **Deploy Specific Roles Only**

**Windows:**
```powershell
# Deploy only web infrastructure (base_system + web_stack)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "base_system,web_stack"

# Deploy only applications (assumes web_stack already deployed)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "applications"
```

**Linux/Mac:**
```bash
# Deploy only web infrastructure
./scripts/run_playbook.sh playbooks/site_clean.yml --tags "base_system,web_stack"

# Deploy only applications
./scripts/run_playbook.sh playbooks/site_clean.yml --tags "applications"
```

#### 3. **Skip Certain Roles**

**Windows:**
```powershell
# Deploy everything except vulnerabilities
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --skip-tags "vulnerabilities"

# Skip system services (monitoring)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --skip-tags "system_services"
```

**Linux/Mac:**
```bash
# Deploy everything except vulnerabilities
./scripts/run_playbook.sh playbooks/site_clean.yml --skip-tags "vulnerabilities"
```

#### 4. **Test Individual Roles**

**Windows:**
```powershell
# Test only the database role (dry run)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "database" --check --diff
```

**Linux/Mac:**
```bash
# Test only the database role (dry run)
./scripts/run_playbook.sh playbooks/site_clean.yml --tags "database" --check --diff
```

#### 5. **Deploy with Custom Variables**

**Windows:**
```powershell
# Override default variables
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --extra-vars "mysql_root_password=CustomPass123 apache_version=2.4.50"
```

**Linux/Mac:**
```bash
# Override default variables
./scripts/run_playbook.sh playbooks/site_clean.yml --extra-vars "mysql_root_password=CustomPass123 apache_version=2.4.50"
```

> **💡 Pro Tip**: All scripts automatically load your `.env` configuration, so you never need to edit paths or credentials in commands!

### Role Dependencies in Practice

**Safe execution order** (respects dependencies):

**Windows:**
```powershell
# These can run independently after base_system:
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "base_system,web_stack"
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "base_system,database"

# This requires both web_stack AND database:
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "base_system,web_stack,database,applications"
```

**Linux/Mac:**
```bash
# These can run independently after base_system:
./scripts/run_playbook.sh playbooks/site_clean.yml --tags "base_system,web_stack"
./scripts/run_playbook.sh playbooks/site_clean.yml --tags "base_system,database"

# This requires both web_stack AND database:
./scripts/run_playbook.sh playbooks/site_clean.yml --tags "base_system,web_stack,database,applications"
```

**Unsafe execution** (will fail due to missing dependencies):

**Windows:**
```powershell
# ❌ FAILS: applications needs web_stack + database
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "applications"

# ❌ FAILS: web_stack needs base_system packages  
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1 playbooks/site_clean.yml --tags "web_stack"
```

### Quick Deployment Shortcuts

For common deployments, use these convenience scripts:

**Windows:**
```powershell
# Deploy clean environment (all roles, no vulnerabilities)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_clean.ps1

# Deploy vulnerable environment (includes vulnerability role)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_vulnerable.ps1

# Deploy with specific vulnerability profile
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_vulnerability_profile.ps1 -Profile "secure_profile"
```

## Adding New Roles

### Step-by-Step Role Creation Process

#### 1. **Create the Role Directory Structure**
```bash
# Navigate to the roles directory
cd playbooks/roles

# Create a new role with standard Ansible structure
mkdir -p new_role/{tasks,handlers,templates,files,vars,defaults,meta}
```

#### 2. **Define the Role's Main Tasks**
```yaml
# playbooks/roles/new_role/tasks/main.yml
---
- name: Install application package
  ansible.builtin.apt:
    name: "{{ new_role_package_name }}"
    state: present
    update_cache: yes

- name: Configure application
  template:
    src: config.j2
    dest: /etc/new_role/config.conf
    owner: root
    group: root
    mode: '0644'
  notify: restart new_role service

- name: Start and enable service
  systemd:
    name: "{{ new_role_service_name }}"
    state: started
    enabled: yes
```

#### 3. **Set Default Variables (If Needed)**
```yaml
# playbooks/roles/new_role/defaults/main.yml
---
# Package and service configuration
new_role_package_name: "new-application"
new_role_service_name: "new-application"
new_role_version: "latest"

# Application-specific settings
new_role_config_option: "default_value"
new_role_port: 8080
```

#### 4. **Create Service Handlers**
```yaml
# playbooks/roles/new_role/handlers/main.yml
---
- name: restart new_role service
  systemd:
    name: "{{ new_role_service_name }}"
    state: restarted
    
- name: reload new_role config
  systemd:
    name: "{{ new_role_service_name }}"
    state: reloaded
```

#### 6. **Define Role Dependencies (Optional)**
```yaml
# playbooks/roles/new_role/meta/main.yml
---
dependencies:
  - role: base_system
  - role: web_stack
    when: new_role_needs_web_server

galaxy_info:
  author: "Your Name"
  description: "Description of the new role"
  min_ansible_version: "2.9"
  platforms:
    - name: Ubuntu
      versions:
        - "20.04"
        - "22.04"
```

#### 7. **Integrate into Main Playbook**
```yaml
# playbooks/site_clean.yml
roles:
    - role: base_system
      tags: base_system
    - role: web_stack           # If new role depends on web server
      tags: web_stack
    - role: database            # If new role depends on database
      tags: database
    - role: applications
      tags: applications
    - role: system_services
      tags: system_services
    - role: new_role            # Add your new role here
      tags: new_role            # Add your new role here
```

### Advanced Role Customization

#### **Role Tags and Conditional Execution**
```yaml
# playbooks/roles/new_role/tasks/main.yml
---
- name: Install development packages
  apt:
    name: "{{ dev_packages }}"
    state: present
  tags: 
    - new_role
    - development
  when: development_mode | default(false)

- name: Install production packages
  apt:
    name: "{{ prod_packages }}"
    state: present
  tags:
    - new_role
    - production
  when: not (development_mode | default(false))
```

#### **Cross-Role Variable Usage**
```yaml
# Access variables from other roles
- name: Configure application with database info
  template:
    src: app_config.j2
    dest: /etc/app/config.yml
  vars:
    db_host: "{{ hostvars[inventory_hostname]['mysql_host'] | default('localhost') }}"
    db_password: "{{ mysql_root_password }}"
```

### Role Testing and Validation

#### **Individual Role Testing**
```bash
# Test role syntax
ansible-playbook --syntax-check playbooks/site_clean.yml

# Dry run single role
ansible-playbook -i inventory.ini playbooks/site_clean.yml \
  --tags "new_role" --check --diff

# Test role with specific variables
ansible-playbook -i inventory.ini playbooks/site_clean.yml \
  --tags "new_role" \
  --extra-vars "new_role_version=1.2.3 development_mode=true"
```

#### **Role Validation Tasks**
```yaml
# Add at end of role tasks
- name: Validate new role installation
  uri:
    url: "http://localhost:{{ new_role_port }}/health"
    method: GET
    status_code: 200
  register: health_check
  retries: 3
  delay: 10

- name: Display validation results
  debug:
    msg: "New role is {{ 'working' if health_check is succeeded else 'failed' }}"
```