# Scripts Directory

This directory contains all PowerShell scripts for managing the Ansible lab environment deployments. All scripts should be run from the project root directory (`D:\ansible-control-node`).

## 🚀 Deployment Scripts

### `run_clean.ps1`
**Purpose**: Deploy clean base lab environment with all services
- Installs Apache, PHP, MariaDB
- Deploys LabSys Wiki with proper styling
- Installs Gitea, MantisBT, and other applications
- Configures secure baseline

**Usage**:
```powershell
.\scripts\run_clean.ps1
```

### `run_vulnerable.ps1` 
**Purpose**: Deploy lab environment with vulnerabilities for security testing
- Applies all vulnerability configurations
- Used for penetration testing practice
- Creates intentionally insecure environment

**Usage**:
```powershell
.\scripts\run_vulnerable.ps1
```

### `run_vulnerability_profile.ps1`
**Purpose**: Deploy with specific vulnerability profiles (secure, vulnerable, highly vulnerable)
- Flexible deployment with profile selection
- Can run clean deployment first
- Supports different vulnerability levels

**Usage**:
```powershell
# Deploy with specific profile
.\scripts\run_vulnerability_profile.ps1 -Profile vulnerable_profile

# Clean deployment first, then apply profile
.\scripts\run_vulnerability_profile.ps1 -Profile highly_vulnerable_profile -CleanFirst

# Only apply vulnerabilities (no base deployment)
.\scripts\run_vulnerability_profile.ps1 -Profile vulnerable_profile -OnlyVulnerabilities
```

**Available Profiles**:
- `secure_profile`: Secure baseline configuration
- `vulnerable_profile`: Moderate vulnerabilities for training
- `highly_vulnerable_profile`: Maximum vulnerabilities for advanced testing

## 🔄 Testing & Reset Scripts

### `fresh_install_test.ps1`
**Purpose**: Reset environment and perform fresh deployment test
- Resets current deployment
- Runs clean deployment
- Tests all automated fixes
- Perfect for validating deployment automation

**Usage**:
```powershell
.\scripts\fresh_install_test.ps1
```

### `reset_vm_complete.ps1`
**Purpose**: Complete VirtualBox VM reset commands
- Contains VBoxManage commands for VM snapshots
- Used for complete VM state reset
- Requires VirtualBox CLI tools

**Usage**: 
Edit the script and run individual commands as needed.

## 📋 Script Structure

All scripts follow these conventions:
- **Path Management**: Scripts automatically change to project root directory
- **Color Coding**: Green for success, Red for errors, Yellow for info, Cyan for highlights  
- **Error Handling**: Proper exit code checking and user feedback
- **Docker Integration**: All use containerized Ansible execution

## 🎯 Quick Reference

| Task | Script | Time |
|------|--------|------|
| Fresh deployment | `.\scripts\run_clean.ps1` | ~5-10 min |
| Add vulnerabilities | `.\scripts\run_vulnerable.ps1` | ~2-3 min |
| Test deployment | `.\scripts\fresh_install_test.ps1` | ~3-5 min |
| Custom profile | `.\scripts\run_vulnerability_profile.ps1` | ~5-15 min |

## 🌐 Expected Results

After successful deployment:
- **Main Portal**: `http://localhost:8080/` (auto-redirects to LabSys Wiki)
- **LabSys Wiki**: `http://localhost:8080/labsys-wiki/` (with full styling)
- **Gitea**: `http://localhost:3000/`
- **SSH Access**: `ssh -i Keys/vps_key -p 2222 noah@host.docker.internal`

## ⚠️ Prerequisites

1. **Docker**: Running and accessible from PowerShell
2. **VirtualBox VM**: Running with SSH on port 2222
3. **Network**: VM accessible via `host.docker.internal:2222`
4. **SSH Keys**: Valid `Keys/vps_key` in project root

## 🔧 Troubleshooting

- **Permission Errors**: Ensure SSH keys have correct permissions
- **Connection Issues**: Verify VM is running and SSH is accessible
- **Docker Errors**: Check Docker Desktop is running
- **Path Issues**: Always run scripts from project root directory