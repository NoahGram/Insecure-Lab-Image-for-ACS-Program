# Vagrant Guide - Automated VM Management

Complete guide to using Vagrant for automated VM creation, management, and snapshot-based resets.

## 📋 Table of Contents
1. [What is Vagrant?](#what-is-vagrant)
2. [Why Use Vagrant?](#why-use-vagrant)
3. [Installation](#installation)
4. [Initial Setup](#initial-setup)
5. [Daily Usage](#daily-usage)
6. [Snapshot Management](#snapshot-management)
7. [Troubleshooting](#troubleshooting)
8. [Advanced Configuration](#advanced-configuration)

---

## 🤖 What is Vagrant?

**Vagrant** is an open-source tool for building and managing virtual machine environments. It provides a simple command-line interface to automate VM creation, configuration, and management.

### Key Features
- **Declarative Configuration**: Define VM setup in a `Vagrantfile`
- **Multi-Platform**: Works with VirtualBox, VMware, Hyper-V
- **Automation**: Fully automated VM provisioning
- **Reproducibility**: Same VM configuration every time
- **Version Control**: Track VM configuration in Git

---

## 🎯 Why Use Vagrant?

### Manual VM Setup (30 minutes)
1. Download Ubuntu ISO (~10 min)
2. Create VM in VirtualBox GUI (~2 min)
3. Install Ubuntu (~10 min)
4. Configure SSH server (~2 min)
5. Setup port forwarding (~3 min)
6. Configure UFW firewall (~2 min)
7. Copy SSH keys (~1 min)
8. Create base snapshot (~1 min)

### Vagrant Setup (5 minutes)
```powershell
vagrant up                    # Does everything above!
vagrant snapshot save base    # Create snapshot
```

### Benefits
✅ **Time Saved**: 25 minutes per VM setup  
✅ **Consistency**: Identical VM every time  
✅ **Version Control**: Vagrantfile tracked in Git  
✅ **Team Sharing**: Share configuration with team  
✅ **Multi-Platform**: VirtualBox, VMware, Hyper-V  
✅ **Zero Manual Steps**: Fully automated, Less Stress  

---

## 💾 Installation

### Windows

1. **Download Vagrant**
   - Visit: https://www.vagrantup.com/downloads
   - Download Windows installer (64-bit)
   - Run installer (requires admin rights)

2. **Install VirtualBox** (if not already installed)
   - Visit: https://www.virtualbox.org/wiki/Downloads
   - Download Windows installer
   - Run installer

3. **Restart Computer**
   - Required for Vagrant to work properly

4. **Verify Installation**
   ```powershell
   vagrant --version
   # Output: Vagrant 2.4.0 (or similar)
   ```

### Linux

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install vagrant virtualbox

# Fedora
sudo dnf install vagrant virtualbox

# Verify
vagrant --version
```

### macOS

```bash
# Using Homebrew
brew install --cask vagrant
brew install --cask virtualbox

# Verify
vagrant --version
```

---

## 🚀 Initial Setup

### Step 1: Create the VM

Navigate to your project directory:

```powershell
cd D:\ansible-control-node
```

Start Vagrant (first time):

```powershell
vagrant up
```

**What happens:**
- ✅ Downloads Ubuntu 24.04 base box (first time only, ~2 min)
- ✅ Creates VM with 2GB RAM, 2 CPUs
- ✅ Configures port forwarding:
  - SSH: 2222 → 22
  - HTTP: 8080 → 80
  - HTTPS: 8443 → 443
  - Gitea: 3000 → 3000
  - MariaDB: 3306 → 3306
  - Cockpit: 9090 → 9090
- ✅ Installs and configures SSH server
- ✅ Sets up UFW firewall (allows SSH)
- ✅ Installs your SSH keys for `vagrant` and `root` users
- ✅ Installs Python3 (required for Ansible)
- ✅ Starts the VM

**Time**: ~5 minutes (first time includes box download)

**Output:**
```
Bringing machine 'default' up with 'virtualbox' provider...
==> default: Importing base box 'bento/ubuntu-24.04'...
==> default: Forwarding ports...
    default: 22 (guest) => 2222 (host)
    default: 80 (guest) => 8080 (host)
==> default: Running provisioner: shell...
==> default: ========================================
==> default: Provisioning ACS-VM Base System
==> default: ========================================
==> default: SSH Server: Active
==> default: UFW Firewall: Enabled (SSH allowed)
==> default: SSH Key: Installed
==> default: Python: 3.12.x
```

### Step 2: Create Base Snapshot

After `vagrant up` completes:

```powershell
vagrant snapshot save base
```

This creates a snapshot named `base` that reset scripts will restore to.

**Output:**
```
==> default: Snapshotting the machine as 'base'...
==> default: Snapshot saved! You can restore the snapshot at any time by
==> default: using `vagrant snapshot restore base`.
```

### Step 3: Configure .env

Edit your `.env` file:

```bash
# Set platform to vagrant
VM_PLATFORM=vagrant

# Optional: These auto-default when VM_PLATFORM=vagrant
# VM1_USERNAME=vagrant
# VM1_HOSTNAME=host.docker.internal
# VM1_SSH_PORT=2222
# VM1_SSH_KEY_PATH=Keys/vps_key
```

**Done!** All scripts now work automatically with Vagrant.

---

## 📅 Daily Usage

### Using Project Scripts (Recommended)

All existing scripts work automatically:

```powershell
# Reset VM to clean state
.\scripts\reset_vm.ps1

# Deploy clean lab
.\scripts\run_clean.ps1

# Deploy vulnerable lab
.\scripts\run_vulnerable.ps1

# Full workflow: reset + deploy
.\scripts\fresh_install_test.ps1

# Test SSH connection
.\scripts\test_connection.ps1
```

### Using Vagrant Commands

**Check VM Status:**
```powershell
vagrant status
```

**Start VM:**
```powershell
vagrant up
```

**Stop VM:**
```powershell
vagrant halt
```

**Restart VM:**
```powershell
vagrant reload
```

**SSH into VM:**
```powershell
vagrant ssh
```

**Destroy VM Completely:**
```powershell
vagrant destroy
# Then recreate: vagrant up
```

---

## 📸 Snapshot Management

### Why Snapshots?

Snapshots provide instant VM resets to a clean state. Perfect for:
- Testing configurations
- Security training (reset after each exploit)
- Development (reset after failed changes)
- Guaranteed clean state every time

### Creating Snapshots

**Save current state:**
```powershell
vagrant snapshot save snapshot_name
```

**Example:**
```powershell
# Create base snapshot (clean system)
vagrant snapshot save base

# Create snapshot with specific config
vagrant snapshot save web_only

# Create snapshot before testing
vagrant snapshot save before_exploit
```

### Restoring Snapshots

**Restore to snapshot (fast method):**
```powershell
vagrant snapshot restore base
```

**Restore using project script:**
```powershell
.\scripts\reset_vm.ps1
```

**What happens:**
1. VM is halted
2. Snapshot is restored
3. VM is started
4. SSH connectivity is verified

**Time**: ~30 seconds

### Listing Snapshots

```powershell
vagrant snapshot list
```

**Output:**
```
base
web_only
before_exploit
```

### Deleting Snapshots

```powershell
vagrant snapshot delete snapshot_name
```

**Example:**
```powershell
vagrant snapshot delete before_exploit
```

### Snapshot Best Practices

✅ **Create `base` snapshot first** - Clean system with SSH + UFW only  
✅ **Name snapshots descriptively** - `clean_base`, `web_configured`, etc.  
✅ **Keep snapshots minimal** - More snapshots = more disk space  
✅ **Test snapshots** - Verify they restore correctly  
❌ **Don't rely on default snapshots** - Explicitly name them  

---

## 🔧 Troubleshooting

### VM Won't Start

**Problem:**
```
The guest machine entered an invalid state while waiting for it to boot.
```

**Solutions:**
1. Check VirtualBox is running:
   ```powershell
   # Open VirtualBox GUI and check VM status
   ```

2. Destroy and recreate:
   ```powershell
   vagrant destroy -f
   vagrant up
   ```

3. Check virtualization is enabled in BIOS

4. Restart VirtualBox service:
   ```powershell
   # Windows: Restart VirtualBox from Services
   ```

### Port Already in Use

**Problem:**
```
Vagrant cannot forward the specified ports on this VM, since they
would collide with some other application that is already listening
```

**Solutions:**
1. Stop conflicting application (e.g., existing Apache on port 8080)

2. Change port in Vagrantfile:
   ```ruby
   config.vm.network "forwarded_port", guest: 80, host: 8081  # Changed from 8080
   ```

3. Find what's using the port:
   ```powershell
   netstat -ano | findstr :8080
   ```

### SSH Connection Failed

**Problem:**
```
ssh_exchange_identification: read: Connection reset by peer
```

**Solutions:**
1. Check VM is running:
   ```powershell
   vagrant status
   ```

2. Try SSH via Vagrant:
   ```powershell
   vagrant ssh
   ```

3. Restart SSH service:
   ```powershell
   vagrant ssh -c "sudo systemctl restart ssh"
   ```

4. Re-provision VM:
   ```powershell
   vagrant provision
   ```

### Box Download Fails

**Problem:**
```
Failed to download box 'bento/ubuntu-24.04'
```

**Solutions:**
1. Check internet connection

2. Try different box mirror:
   ```ruby
   # In Vagrantfile
   config.vm.box = "bento/ubuntu-24.04"
   config.vm.box_url = "https://app.vagrantup.com/bento/boxes/ubuntu-24.04"
   ```

3. Download manually and add:
   ```powershell
   vagrant box add bento/ubuntu-24.04 path/to/downloaded/box.box
   ```

### Snapshot Restore Issues

**Problem:**
```
A VM with that name already exists. Please use a different name.
```

**Solutions:**
1. Use project reset script (handles this automatically):
   ```powershell
   .\scripts\reset_vm.ps1
   ```

2. Or manually halt before restore:
   ```powershell
   vagrant halt
   vagrant snapshot restore base
   vagrant up --no-provision
   ```

### "Vagrant" Command Not Found

**Problem:**
```
vagrant : The term 'vagrant' is not recognized
```

**Solutions:**
1. Restart PowerShell/Terminal after installation

2. Restart computer (Vagrant installer modifies PATH)

3. Verify installation:
   ```powershell
   # Check if Vagrant is in PATH
   $env:PATH -split ';' | Select-String vagrant
   ```

4. Reinstall Vagrant with administrator privileges

---

## 🎛️ Advanced Configuration

### Our Vagrantfile Explained

Located at: `d:\ansible-control-node\Vagrantfile`

**Base Box:**
```ruby
config.vm.box = "bento/ubuntu-24.04"  # Ubuntu 24.04 LTS Server
config.vm.hostname = "acs-vm"
```

**Port Forwarding:**
```ruby
config.vm.network "forwarded_port", guest: 22, host: 2222, id: "ssh"
config.vm.network "forwarded_port", guest: 80, host: 8080  # HTTP
config.vm.network "forwarded_port", guest: 3000, host: 3000  # Gitea
config.vm.network "forwarded_port", guest: 9090, host: 9090  # Cockpit
```

**SSH Configuration:**
```ruby
config.ssh.insert_key = false        # Use our custom key
config.ssh.username = "vagrant"
config.ssh.port = 2222
```

**Resource Allocation:**
```ruby
config.vm.provider "virtualbox" do |vb|
  vb.name = "ansible-control-node-acs-001"
  vb.memory = "2048"  # 2GB RAM
  vb.cpus = 2         # 2 CPU cores
end
```

**Provisioning Script:**
```ruby
config.vm.provision "shell", inline: <<-SHELL
  # Updates, SSH server, UFW, SSH keys, Python installation
  # See full Vagrantfile for complete provisioning logic
SHELL
```

### Customizing Your Vagrantfile

**Change RAM/CPU:**
```ruby
vb.memory = "4096"  # 4GB RAM
vb.cpus = 4         # 4 CPU cores
```

**Add More Port Forwards:**
```ruby
config.vm.network "forwarded_port", guest: 8000, host: 8000
```

**Use Different Box:**
```ruby
config.vm.box = "ubuntu/jammy64"  # Ubuntu 22.04
```

**Add Shared Folders:**
```ruby
config.vm.synced_folder "./shared", "/vagrant_shared"
```

**After changes:**
```powershell
vagrant reload  # Apply configuration changes
```

### Multi-Provider Support

Our Vagrantfile supports multiple platforms:

**VirtualBox** (default):
```powershell
vagrant up
```

**VMware:**
```powershell
vagrant up --provider=vmware_desktop
```

**Hyper-V:**
```powershell
vagrant up --provider=hyperv
```

---

## 🔄 Integration with Project Scripts

### How Scripts Detect Vagrant

**`.env` file:**
```bash
VM_PLATFORM=vagrant
```

**`load_env.py` provides defaults:**
```python
if vm_platform == 'vagrant':
    env_vars.setdefault('VM1_USERNAME', 'vagrant')
    env_vars.setdefault('VM1_HOSTNAME', 'host.docker.internal')
    env_vars.setdefault('VM1_SSH_PORT', '2222')
    env_vars.setdefault('VM1_SSH_KEY_PATH', 'Keys/vps_key')
```

**`generate_inventory.py` forces vagrant user:**
```python
if vm_platform == 'vagrant':
    vm1_user = 'vagrant'  # Override even if .env says different
```

### Reset Script Workflow

`.\scripts\reset_vm.ps1` automatically:

1. Detects `VM_PLATFORM=vagrant` from `.env`
2. Calls `vagrant snapshot restore base`
3. Waits for VM to boot
4. Tests SSH connectivity
5. Confirms ready for deployment

---

## 📚 Additional Resources

### Vagrant Documentation
- [Official Vagrant Docs](https://www.vagrantup.com/docs)
- [Vagrant Boxes](https://app.vagrantup.com/boxes/search) - Find more boxes
- [Vagrantfile Reference](https://www.vagrantup.com/docs/vagrantfile)

### Project Documentation
- `documentation/Ansible_Guide.md` - Ansible and roles guide
- `documentation/Quick_Start.md` - Quick onboarding
- `documentation/SETUP_DOCUMENTATION.md` - Old Detailed setup

### VirtualBox
- [VirtualBox Manual](https://www.virtualbox.org/manual/)
- [VirtualBox Downloads](https://www.virtualbox.org/wiki/Downloads)

---

## 🎓 Vagrant Commands Cheatsheet

### VM Lifecycle
```powershell
vagrant up              # Create/start VM
vagrant halt            # Stop VM
vagrant reload          # Restart VM
vagrant destroy         # Delete VM
vagrant suspend         # Pause VM
vagrant resume          # Resume suspended VM
```

### Snapshots
```powershell
vagrant snapshot save NAME       # Create snapshot
vagrant snapshot restore NAME    # Restore snapshot
vagrant snapshot list            # List snapshots
vagrant snapshot delete NAME     # Delete snapshot
```

### Information
```powershell
vagrant status          # Show VM status
vagrant global-status   # Show all Vagrant VMs
vagrant version         # Show Vagrant version
vagrant box list        # List installed boxes
```

### SSH
```powershell
vagrant ssh                    # SSH into VM
vagrant ssh -c "COMMAND"       # Run command in VM
vagrant ssh-config             # Show SSH configuration
```

### Provisioning
```powershell
vagrant provision       # Re-run provisioning scripts
vagrant reload --provision  # Restart and provision
```

### Boxes
```powershell
vagrant box add NAME           # Add new box
vagrant box remove NAME        # Remove box
vagrant box update            # Update current box
```

---

**Need Help?** Check `documentation/Quick_Start.md` for step-by-step onboarding or `documentation/Ansible_Guide.md` for Ansible usage!
