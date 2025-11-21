# Vagrant + VMware — quick reference
This document explains how to use VMware as a Vagrant provider after Vagrant is installed. It covers prerequisites, installing the VMware provider plugin (commercial), preparing boxes, example Vagrantfile, and common troubleshooting notes for Windows, macOS, and Linux.
## Prerequisites
- Vagrant installed and on a supported version: `vagrant --version`
- VMware product installed:
    - Windows/Linux: VMware Workstation Pro (supported)
    - macOS: VMware Fusion / Fusion Pro (supported)
    - On Apple Silicon, VMware must support ARM and boxes must be ARM-compatible
## Install and license the VMware provider plugin

1. Install plugin (unified provider name):
```powershell
vagrant plugin install vagrant-vmware-desktop
```

2. Download and install Vagrant VMware Utility:
   Go to this the [Download page for the Vagrant VMware Utility tool](https://developer.hashicorp.com/vagrant/install/vmware), follow the instructions for your operating system.
	1. If you are on windows, use and install the [Windows Binary](https://developer.hashicorp.com/vagrant/install/vmware#windows) on the website
	2. If you are using Mac, use `brew` to install the software with the instruction given on the webpage.
	3. If you are on Linux, follow the instructions on the page based on your Linux Distribution.

After you completed the installation of the VMware Plugin for Vagrant. You can start using the software by building it on top of your VMware desktop.

Start the VM:
```powershell
vagrant up --provider=vmware_desktop
```

# Troubleshooting
If you encounter issues, refer to the [Vagrant VMware Troubleshooting Guide](https://developer.hashicorp.com/vagrant/docs/providers/vmware/troubleshooting) for common problems and solutions.