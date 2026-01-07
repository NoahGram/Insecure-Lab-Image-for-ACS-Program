#!/usr/bin/env bash
# Run Ansible Playbook
# This script loads .env and runs an Ansible playbook using your configured settings
# Usage: ./run_playbook.sh [playbook_path]

set -e

playbook="${1:-playbooks/site_clean.yml}"

if [ ! -f ".env" ]; then
    echo "ERROR: .env file not found!"
    echo "Please create .env from .env.example"
    exit 1
fi

echo "============================================"
echo " Ansible Playbook Runner"
echo "============================================"
echo ""

# Load .env variables
eval "$(python3 load_env.py bash)"

echo "Configuration:"
echo "  Repository: $ANSIBLE_CONTROL_NODE_PATH"
echo "  Playbook: $playbook"
echo "  Target: $VM1_USERNAME@$VM1_HOSTNAME:$VM1_SSH_PORT"
echo ""

# Normalize path and ensure absolute path for Docker volume
repo_path="${ANSIBLE_CONTROL_NODE_PATH//\\//}"
mount_point="${DOCKER_MOUNT_POINT:-/ansible}"
# If the path is not absolute, make it relative to current working directory
if [[ "$repo_path" != /* ]]; then
    repo_path="$PWD/$repo_path"
fi
# Try to canonicalize the path if realpath is available
if command -v realpath >/dev/null 2>&1; then
    repo_path="$(realpath -m "$repo_path")"
fi
inventory_file="${ANSIBLE_INVENTORY_FILE:-inventory.ini}"
key_path="${VM1_SSH_KEY_PATH:-Keys/vps_key}"
docker_image="${DOCKER_IMAGE_NAME:-ansible-control-node}"

# Build docker command
docker_cmd="docker run --rm -v \"$repo_path:$mount_point\" $docker_image sh -c \"chmod 600 $mount_point/$key_path && ansible-playbook $mount_point/$playbook -i $mount_point/$inventory_file\""

echo "Executing playbook..."
echo "$docker_cmd"
echo ""

eval "$docker_cmd"

if [ $? -eq 0 ]; then
    echo ""
    echo "============================================"
    echo " Playbook completed successfully!"
    echo "============================================"
else
    echo ""
    echo "============================================"
    echo " Playbook execution failed!"
    echo "============================================"
    exit 1
fi
