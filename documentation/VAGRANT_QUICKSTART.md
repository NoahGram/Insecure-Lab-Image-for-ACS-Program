# Vagrant Quick Start Guide

## Overview

This guide shows how to use Vagrant to **fully automate** VM creation, eliminating the 30-minute manual setup process. With Vagrant, you get a production-ready VM in ~5 minutes with a single command.

## What Vagrant Automates

### Manual Process (30 minutes):
1. Download Ubuntu ISO (~10 min)
2. Create VM in VirtualBox GUI (~2 min)
3. Install Ubuntu (~10 min)
4. Configure SSH server (~2 min)
5. Setup port forwarding rules (~3 min)
6. Configure UFW firewall (~2 min)
7. Copy SSH keys (~1 min)
8. Create base snapshot (~1 min)

### Vagrant Process (5 minutes):
1. `vagrant up` - Everything automated!
2. `vagrant snapshot save base` - Create snapshot

## Prerequisites

1. **Install Vagrant**
   - Download from: https://www.vagrantup.com/downloads
   - Windows: Use the MSI installer
   - Verify: `vagrant --version`

2. **Install VirtualBox**
   - Download from: https://www.virtualbox.org/wiki/Downloads
   - Or use VMware/Hyper-V if preferred

3. **SSH Key Pair**
   - Must exist at: `Keys/vps_key.pub`
   - This key will be installed for both `vagrant` and `root` users

## One-Time Setup

### Step 1: Create the VM

From the project root (`d:\ansible-control-node`):

```powershell
vagrant up
```

This will:
- ✅ Download Ubuntu 24.04 base box (first time only)
- ✅ Create VM with 2GB RAM, 2 CPUs
- ✅ Configure port forwarding (SSH: 2222, HTTP: 8080, etc.)
- ✅ Install and configure SSH server
- ✅ Setup UFW firewall (allow SSH, deny others)
- ✅ Install your SSH keys for `vagrant` and `root` users
- ✅ Install Python3 (required for Ansible)
- ✅ Start the VM

**Time**: ~5 minutes (first time includes box download ~2 min)

### Step 2: Create Base Snapshot

After `vagrant up` completes successfully:

```powershell
vagrant snapshot save base
```

This creates a snapshot named `base` that your reset scripts will restore to.

### Step 3: Update Configuration

Edit `.env` file:

```env
VM_PLATFORM=vagrant
```

**Done!** You can now use all existing scripts.

## Daily Usage

### Using Vagrant Commands

```powershell
# Reset to base snapshot (Vagrant native)
vagrant snapshot restore base --no-provision

# Check VM status
vagrant status

# Stop VM
vagrant halt

# Start VM
vagrant up

# SSH into VM (uses vagrant user)
vagrant ssh

# Destroy VM completely (requires vagrant up to recreate)
vagrant destroy
```

### Using Your Existing Scripts

All your scripts work automatically with `VM_PLATFORM=vagrant`:

```powershell
# Reset VM to clean state
.\scripts\reset_vm.ps1

# Deploy clean configuration
.\scripts\run_clean.ps1

# Deploy vulnerable configuration
.\scripts\run_vulnerable.ps1

# Full test workflow
.\scripts\fresh_install_test.ps1
```

## Configuration Details

### Port Forwarding

The Vagrantfile configures these port forwards automatically:

| Service | Host Port | Guest Port | Description |
|---------|-----------|------------|-------------|
| SSH     | 2222      | 22         | SSH access |
| HTTP    | 8080      | 80         | Web server |
| HTTPS   | 8443      | 443        | Secure web |
| Gitea   | 3000      | 3000       | Git server |
| MariaDB | 3306      | 3306       | Database |
| Cockpit | 9090      | 9090       | Admin panel |

### VM Resources

- **Memory**: 2GB RAM
- **CPUs**: 2 cores
- **Disk**: 10GB (expandable)
- **Network**: NAT + Port Forwarding

### Provisioning

The Vagrantfile runs this setup automatically:

1. Update apt package cache
2. Install OpenSSH server
3. Configure SSH keys for `vagrant` and `root` users
4. Install and configure UFW firewall:
   - Allow SSH (port 22)
   - Deny all other incoming by default
   - Enable firewall
5. Install Python3 (for Ansible)
6. Clean apt cache

## Troubleshooting

### Issue: "Vagrant not installed or not in PATH"

**Solution**: Install Vagrant from https://www.vagrantup.com/downloads and restart PowerShell.

### Issue: "The guest machine entered an invalid state"

**Solution**: 
```powershell
vagrant destroy -f
vagrant up
```

### Issue: "SSH Key not found"

**Error**: `Keys/vps_key.pub` doesn't exist

**Solution**: Generate SSH key pair:
```powershell
ssh-keygen -t rsa -b 4096 -f Keys\vps_key -N '""'
```

### Issue: Port forwarding conflict

**Error**: "Port 2222 is already in use"

**Solution 1**: Stop other VMs using that port
```powershell
# Check VirtualBox VMs
VBoxManage list runningvms
VBoxManage controlvm <vm-name> poweroff
```

**Solution 2**: Edit Vagrantfile to use different ports

### Issue: VM slow or unresponsive

**Solution**: Increase resources in Vagrantfile:
```ruby
vb.memory = "4096"  # Change from 2048 to 4096
vb.cpus = 4         # Change from 2 to 4
```

Then:
```powershell
vagrant reload
```

## Multi-Provider Support

The Vagrantfile supports multiple virtualization platforms:

### VirtualBox (Default)
```powershell
vagrant up  # Uses VirtualBox by default
```

### VMware Desktop
```powershell
vagrant up --provider=vmware_desktop
```

Requirements:
- Install VMware Workstation/Fusion
- Install Vagrant VMware plugin: `vagrant plugin install vagrant-vmware-desktop`

### Hyper-V (Windows)
```powershell
vagrant up --provider=hyperv
```

Requirements:
- Enable Hyper-V in Windows Features
- Run PowerShell as Administrator

## Advanced Usage

### SSH Access

```powershell
# SSH as vagrant user (has sudo privileges)
vagrant ssh

# SSH as root user (using your SSH key)
ssh -i Keys\vps_key -p 2222 root@localhost

# SSH as vagrant user (using your SSH key)
ssh -i Keys\vps_key -p 2222 vagrant@localhost
```

### Snapshot Management

```powershell
# List all snapshots
vagrant snapshot list

# Save new snapshot
vagrant snapshot save my-snapshot

# Restore specific snapshot
vagrant snapshot restore my-snapshot

# Delete snapshot
vagrant snapshot delete my-snapshot
```

### Vagrant Box Updates

```powershell
# Check for box updates
vagrant box outdated

# Update to latest box version
vagrant box update

# Rebuild VM with updated box
vagrant destroy -f
vagrant up
vagrant snapshot save base
```

## Comparison: Manual vs Vagrant

| Aspect | Manual Setup | Vagrant Setup |
|--------|-------------|---------------|
| **Time** | ~30 minutes | ~5 minutes |
| **Reproducibility** | Manual steps, prone to errors | 100% reproducible |
| **Documentation** | Need detailed guide | Self-documenting code |
| **Consistency** | Varies each time | Identical every time |
| **Version Control** | Can't track changes | Vagrantfile in git |
| **Sharing** | Send detailed instructions | Share Vagrantfile |
| **Updates** | Re-do manual steps | Edit Vagrantfile, `vagrant reload` |

## Integration with Existing Workflow

Vagrant fits seamlessly into your existing workflow:

```
┌─────────────────────────────────────────────────────┐
│  ONE-TIME SETUP                                     │
├─────────────────────────────────────────────────────┤
│  1. vagrant up              (creates VM)            │
│  2. vagrant snapshot save base  (saves clean state) │
│  3. Update .env: VM_PLATFORM=vagrant                │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│  DAILY USAGE                                        │
├─────────────────────────────────────────────────────┤
│  Option A: Use your scripts (recommended)           │
│    • .\scripts\reset_vm.ps1                         │
│    • .\scripts\run_clean.ps1                        │
│    • .\scripts\run_vulnerable.ps1                   │
│                                                      │
│  Option B: Use Vagrant directly                     │
│    • vagrant snapshot restore base --no-provision   │
│    • docker run ansible...                          │
└─────────────────────────────────────────────────────┘
```

## Next Steps

1. **Complete one-time setup** (above)
2. **Test reset script**: `.\scripts\reset_vm.ps1`
3. **Deploy clean config**: `.\scripts\run_clean.ps1`
4. **Verify everything works** as before
5. **Enjoy automated VM creation** for future labs!

## Benefits Summary

✅ **Time Saved**: 25 minutes per fresh VM setup  
✅ **Consistency**: Identical VM every time  
✅ **Portability**: Share Vagrantfile with team  
✅ **Version Control**: Track VM config in git  
✅ **Multi-Platform**: VirtualBox, VMware, Hyper-V support  
✅ **Integration**: Works with all existing scripts  

---

**Need Help?**
- Vagrant Docs: https://www.vagrantup.com/docs
- VirtualBox Docs: https://www.virtualbox.org/manual/
- Check other guides in `documentation/` folder
