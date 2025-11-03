#!/usr/bin/env python3
"""
Generate Ansible inventory.ini from .env configuration file
Usage: python generate_inventory.py
"""

import os
import sys
from pathlib import Path

def load_env_file(env_path='.env'):
    """Load environment variables from .env file"""
    env_vars = {}
    
    if not os.path.exists(env_path):
        print(f"ERROR: {env_path} not found!")
        print(f"Please create {env_path} file in the repository root.")
        sys.exit(1)
    
    with open(env_path, 'r') as f:
        for line in f:
            line = line.strip()
            # Skip comments and empty lines
            if not line or line.startswith('#'):
                continue
            
            # Parse KEY=VALUE
            if '=' in line:
                key, value = line.split('=', 1)
                key = key.strip()
                value = value.strip()
                
                # Handle variable references like ${VM1_PASSWORD}
                while '${' in value:
                    start = value.index('${')
                    end = value.index('}', start)
                    var_name = value[start+2:end]
                    var_value = env_vars.get(var_name, '')
                    value = value[:start] + var_value + value[end+1:]
                
                env_vars[key] = value
    
    return env_vars

def generate_inventory(env_vars):
    """Generate inventory.ini content from environment variables"""
    
    # Get values with defaults
    vm1_host = env_vars.get('VM1_HOSTNAME', 'host.docker.internal')
    vm1_user = env_vars.get('VM1_USERNAME', 'noah')
    vm1_port = env_vars.get('VM1_SSH_PORT', '2222')
    vm1_password = env_vars.get('VM1_PASSWORD', 'ColdBrew')
    vm1_key = env_vars.get('VM1_SSH_KEY_PATH', 'Keys/vps_key')
    docker_mount = env_vars.get('DOCKER_MOUNT_POINT', '/ansible')
    
    # Build inventory content
    inventory = f"""# Auto-generated from .env file
# To regenerate: python generate_inventory.py

[vps_lab]
vps_target ansible_host={vm1_host} ansible_user={vm1_user} ansible_port={vm1_port}

[all:vars]
ansible_become=yes
ansible_become_password={vm1_password}
ansible_private_key_file={docker_mount}/{vm1_key}
host_key_checking=False
ansible_ssh_common_args='-o StrictHostKeyChecking=no'
"""
    
    # Add VM2 if configured
    if 'VM2_HOSTNAME' in env_vars:
        vm2_host = env_vars.get('VM2_HOSTNAME')
        vm2_user = env_vars.get('VM2_USERNAME', 'admin')
        vm2_port = env_vars.get('VM2_SSH_PORT', '22')
        vm2_password = env_vars.get('VM2_PASSWORD', 'password')
        vm2_key = env_vars.get('VM2_SSH_KEY_PATH', 'Keys/vm2_key')
        
        inventory += f"""
[vps_lab_2]
vps_target_2 ansible_host={vm2_host} ansible_user={vm2_user} ansible_port={vm2_port}

[vps_lab_2:vars]
ansible_become=yes
ansible_become_password={vm2_password}
ansible_private_key_file={docker_mount}/{vm2_key}
"""
    
    return inventory

def main():
    """Main execution"""
    script_dir = Path(__file__).parent
    env_path = script_dir / '.env'
    inventory_path = script_dir / 'inventory.ini'
    
    print("=" * 70)
    print("Ansible Inventory Generator")
    print("=" * 70)
    
    # Load environment variables
    print(f"\n[1/3] Loading configuration from: {env_path}")
    env_vars = load_env_file(env_path)
    print(f"      ✓ Loaded {len(env_vars)} configuration variables")
    
    # Generate inventory content
    print(f"\n[2/3] Generating inventory content...")
    inventory_content = generate_inventory(env_vars)
    print(f"      ✓ Inventory generated")
    
    # Write inventory file
    print(f"\n[3/3] Writing inventory to: {inventory_path}")
    with open(inventory_path, 'w') as f:
        f.write(inventory_content)
    print(f"      ✓ Inventory file created successfully")
    
    print("\n" + "=" * 70)
    print("SUCCESS! inventory.ini has been generated from .env")
    print("=" * 70)
    print(f"\nVM1 Configuration:")
    print(f"  Host: {env_vars.get('VM1_HOSTNAME', 'N/A')}")
    print(f"  User: {env_vars.get('VM1_USERNAME', 'N/A')}")
    print(f"  Port: {env_vars.get('VM1_SSH_PORT', 'N/A')}")
    
    if 'VM2_HOSTNAME' in env_vars:
        print(f"\nVM2 Configuration:")
        print(f"  Host: {env_vars.get('VM2_HOSTNAME', 'N/A')}")
        print(f"  User: {env_vars.get('VM2_USERNAME', 'N/A')}")
        print(f"  Port: {env_vars.get('VM2_SSH_PORT', 'N/A')}")
    
    print("\nNext steps:")
    print("  1. Review inventory.ini")
    print("  2. Run your Ansible playbooks")
    print()

if __name__ == '__main__':
    main()
