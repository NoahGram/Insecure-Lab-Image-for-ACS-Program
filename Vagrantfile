# -*- mode: ruby -*-
# vi: set ft=ruby :

# Ansible Control Node - Vagrant Configuration
# Automates VM creation with SSH, UFW, and base snapshot setup

Vagrant.configure("2") do |config|
  # Base box - Ubuntu 24.04 LTS Server
  # Use a widely-available server image (not desktop) so Vagrant Cloud resolves correctly
  # Use a stable, widely-available Ubuntu 24.04 LTS Server box.
  # 'bento/ubuntu-24.04' is a commonly used server image (no desktop) maintained by the Bento project.
  config.vm.box = "bento/ubuntu-24.04"  # Ubuntu 24.04 LTS Server
  config.vm.hostname = "acs-vm"
  
  # Network Configuration - Port Forwarding
  # SSH
  config.vm.network "forwarded_port", guest: 22, host: 2222, host_ip: "127.0.0.1", id: "ssh"
  
  # Web Services
  config.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"      # Apache/HTTP
  config.vm.network "forwarded_port", guest: 443, host: 8443, host_ip: "127.0.0.1"    # HTTPS
  
  # Applications
  config.vm.network "forwarded_port", guest: 3000, host: 3000, host_ip: "127.0.0.1"   # Gitea
  config.vm.network "forwarded_port", guest: 3306, host: 3306, host_ip: "127.0.0.1"   # MariaDB (optional)
  config.vm.network "forwarded_port", guest: 9090, host: 9090, host_ip: "127.0.0.1"   # Cockpit
  
  # Disable default Vagrant SSH key (we'll use our own)
  config.ssh.insert_key = false
  
  # SSH Configuration
  config.ssh.username = "vagrant"
  config.ssh.port = 2222
  
  # Copy SSH public key to VM
  config.vm.provision "file", source: "./Keys/vps_key.pub", destination: "/tmp/vps_key.pub"
  
  # Base System Provisioning
  config.vm.provision "shell", inline: <<-SHELL
    set -e
    
    echo "========================================="
    echo "Provisioning ACS-VM Base System"
    echo "========================================="
    
    # Update package lists
    echo "Updating package lists..."
    apt-get update -qq
    
    # Install SSH server (usually pre-installed but ensure it's there)
    echo "Installing OpenSSH server..."
    apt-get install -y openssh-server
    
    # Setup SSH key for vagrant user
    echo "Configuring SSH key..."
    mkdir -p /home/vagrant/.ssh
    chmod 700 /home/vagrant/.ssh
    cat /tmp/vps_key.pub >> /home/vagrant/.ssh/authorized_keys
    chmod 600 /home/vagrant/.ssh/authorized_keys
    chown -R vagrant:vagrant /home/vagrant/.ssh
    rm /tmp/vps_key.pub
    
    # Also add for root (in case needed)
    mkdir -p /root/.ssh
    chmod 700 /root/.ssh
    cat /home/vagrant/.ssh/authorized_keys >> /root/.ssh/authorized_keys
    chmod 600 /root/.ssh/authorized_keys
    
    # Install and configure UFW
    echo "Installing and configuring UFW firewall..."
    apt-get install -y ufw
    
    # Allow SSH before enabling firewall
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow 22/tcp
    ufw --force enable
    
    # Ensure SSH service is running
    systemctl enable ssh
    systemctl start ssh
    
    # Install Python (required for Ansible)
    echo "Installing Python..."
    apt-get install -y python3 python3-pip
    
    # Cleanup
    echo "Cleaning up..."
    apt-get autoremove -y
    apt-get autoclean -y
    
    # Display summary
    echo ""
    echo "========================================="
    echo "Base System Provisioning Complete!"
    echo "========================================="
    echo "SSH Server: Active"
    echo "UFW Firewall: Enabled (SSH allowed)"
    echo "SSH Key: Installed"
    echo "Python: $(python3 --version)"
    echo ""
    echo "Next steps:"
    echo "1. Exit and run: vagrant snapshot save base"
    echo "2. Update .env: VM_PLATFORM=vagrant"
    echo "3. Deploy lab: .\\scripts\\run_clean.ps1"
    echo "========================================="
  SHELL
  
  # VirtualBox Provider Configuration
  config.vm.provider "virtualbox" do |vb|
  # Use a unique VM name to avoid conflicts with any existing manual VMs named 'ACS-VM'
  # Use a numeric suffix to reduce chance of collision with existing VMs
  vb.name = "ansible-control-node-acs-001"
    vb.memory = "4096"  # 4GB RAM, 2048MB minimum for Ansible Control Node
    vb.cpus = 2         # 2 CPU cores
    
    # Performance optimizations
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
    vb.customize ["modifyvm", :id, "--audio", "none"]
    vb.customize ["modifyvm", :id, "--usb", "off"]
    vb.customize ["modifyvm", :id, "--usbehci", "off"]
  end
  
  # VMware Provider Configuration (optional)
  config.vm.provider "vmware_desktop" do |vmware|
  vmware.vmx["displayName"] = "ansible-control-node-acs-001"
    vmware.vmx["memsize"] = "4096"
    vmware.vmx["numvcpus"] = "2"
  end
  
  # Hyper-V Provider Configuration (optional)
  config.vm.provider "hyperv" do |hv|
  hv.vmname = "ansible-control-node-acs-001"
    hv.memory = 4096
    hv.cpus = 2
    hv.enable_virtualization_extensions = true
  end
end
