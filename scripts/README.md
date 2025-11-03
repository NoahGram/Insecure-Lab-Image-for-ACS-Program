# Scripts Directory

This directory contains all PowerShell scripts for managing the Ansible lab environment deployments. All scripts should be run from the project root directory (`D:\ansible-control-node`).

## 🚀 Deployment Scripts

### `run_clean.ps1`
**Purpose**: Deploy clean base lab environment with all services

**Usage**:
```powershell
.\scripts\run_clean.ps1
```

### `run_vulnerable.ps1` 
**Purpose**: Deploy lab environment with vulnerabilities for security testing

**Usage**:
```powershell
.\scripts\run_vulnerable.ps1
```

### `run_vulnerability_profile.ps1`
**Purpose**: Deploy with specific vulnerability profiles (secure, vulnerable, highly vulnerable)

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

## 🔄 Testing & Reset Scripts

### `fresh_install_test.ps1`
**Purpose**: Reset environment and perform fresh deployment test

**Usage**:
```powershell
.\scripts\fresh_install_test.ps1
```

### `reset_vm_complete.ps1`
**Purpose**: Complete VirtualBox VM reset commands

**Usage**: 
Edit the script and run individual commands as needed.

## 📋 Script Structure

All scripts follow these conventions:

## 🎯 Quick Reference

| Task | Script | Time |
|------|--------|------|
| Fresh deployment | `.\scripts\run_clean.ps1` | ~5-10 min |
| Add vulnerabilities | `.\scripts\run_vulnerable.ps1` | ~2-3 min |
| Test deployment | `.\scripts\fresh_install_test.ps1` | ~3-5 min |
| Custom profile | `.\scripts\run_vulnerability_profile.ps1` | ~5-15 min |

## 🌐 Expected Results

After successful deployment:

## ⚠️ Prerequisites

1. **Docker**: Running and accessible from PowerShell
2. **VirtualBox VM**: Running with SSH on port 2222
3. **Network**: VM accessible via `host.docker.internal:2222`
4. **SSH Keys**: Valid `Keys/vps_key` in project root

## 🔧 Troubleshooting


## New wrapper scripts

Two small wrappers help run the Dockerized Ansible environment without editing your local path:

- `run_playbook.ps1` — PowerShell wrapper (Windows). Automatically detects the repo root and mounts it into the container.
- `run_playbook.sh` — POSIX wrapper (Linux/macOS/WSL/git-bash). Automatically detects the repo root and mounts it into the container.

Both wrappers support two optional environment variables:

- `DOCKER_IMAGE` — override the docker image name (default: `ansible-control-node`).
- `DOCKER_MOUNT` — override the path that will be mounted into the container (useful if your repo is on a different drive).

Examples:

PowerShell (Windows):

```powershell
# default run (playbooks/site_clean.yml)
PowerShell -ExecutionPolicy Bypass -File .\scripts\run_playbook.ps1

# override mount and image
$env:DOCKER_MOUNT = 'D:\repos\ansible-control-node'
$env:DOCKER_IMAGE = 'my-ansible-image'
.\scripts\run_playbook.ps1
```

POSIX (Linux/macOS/WSL):

```bash
chmod +x ./scripts/run_playbook.sh
./scripts/run_playbook.sh

# override mount and image
export DOCKER_MOUNT="/mnt/d/repos/ansible-control-node"
export DOCKER_IMAGE="my-ansible-image"
./scripts/run_playbook.sh
```

## Notes & caveats

- Docker Desktop on Windows may use different backends (Hyper-V, WSL2). The wrappers attempt to normalize paths by converting backslashes to forward slashes. If you encounter mount errors, try running from WSL or ensure the drive is shared in Docker Desktop settings.
- Windows file-permission semantics can make `chmod` inside the container ineffective. If SSH key permission errors occur, run the wrapper from WSL or copy the key to a Linux filesystem first.

If you want, I can add a small `scripts/validate_env.ps1` to detect problematic Windows/Docker setups and print recommended remediation steps.