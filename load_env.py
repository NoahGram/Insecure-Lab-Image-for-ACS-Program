#!/usr/bin/env python3
"""
Load environment variables from .env file and make them available to shell
Usage: 
  PowerShell: python load_env.py powershell
  Bash: source <(python load_env.py bash)
"""

import os
import sys
from pathlib import Path

def load_env_file(env_path='.env'):
    """Load environment variables from .env file"""
    env_vars = {}
    
    if not os.path.exists(env_path):
        print(f"ERROR: {env_path} not found!", file=sys.stderr)
        print(f"Please create {env_path} file in the repository root.", file=sys.stderr)
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

def output_powershell(env_vars):
    """Output PowerShell variable assignments"""
    for key, value in env_vars.items():
        # Escape special characters for PowerShell
        value_escaped = value.replace('"', '`"')
        print(f'$env:{key}="{value_escaped}"')

def output_bash(env_vars):
    """Output Bash export statements"""
    for key, value in env_vars.items():
        # Escape special characters for Bash
        value_escaped = value.replace('"', '\\"')
        print(f'export {key}="{value_escaped}"')

def main():
    shell = sys.argv[1] if len(sys.argv) > 1 else 'powershell'
    
    script_dir = Path(__file__).parent
    env_path = script_dir / '.env'
    
    env_vars = load_env_file(env_path)
    
    # Provide sensible defaults when using Vagrant to avoid manual .env edits
    # These defaults can be overridden by explicit entries in .env
    vm_platform = env_vars.get('VM_PLATFORM', '').lower()
    if vm_platform == 'vagrant':
        # If VM1 values are not set, set them to Vagrant-friendly defaults
        env_vars.setdefault('VM1_USERNAME', 'vagrant')
        # For Docker containers connecting to host-forwarded ports, prefer host.docker.internal
        env_vars.setdefault('VM1_HOSTNAME', 'host.docker.internal')
        env_vars.setdefault('VM1_SSH_PORT', '2222')
        env_vars.setdefault('VM1_SSH_KEY_PATH', 'Keys/vps_key')
    
    if shell.lower() in ['powershell', 'ps', 'pwsh']:
        output_powershell(env_vars)
    elif shell.lower() in ['bash', 'sh', 'zsh']:
        output_bash(env_vars)
    else:
        print(f"Unknown shell: {shell}", file=sys.stderr)
        sys.exit(1)

if __name__ == '__main__':
    main()
