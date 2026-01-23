#!/usr/bin/env bash
# Run Ansible Playbook
# This script loads .env and runs an Ansible playbook using your configured settings
# Usage: ./run_playbook.sh [playbook_path]

set -e

playbook="${1:-playbooks/site_clean.yml}"
container_name="ansible-container"
started_container=false

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

# Normalize path and ensure absolute path
repo_path="${ANSIBLE_CONTROL_NODE_PATH//\\//}"
mount_point="${DOCKER_MOUNT_POINT:-/ansible}"
inventory_file="${ANSIBLE_INVENTORY_FILE:-inventory.ini}"
key_path="${VM1_SSH_KEY_PATH:-Keys/vps_key}"
docker_image="${DOCKER_IMAGE_NAME:-ansible-control-node}"

if [[ "$repo_path" != /* ]]; then
    repo_path="$PWD/$repo_path"
fi

if command -v realpath >/dev/null 2>&1; then
    repo_path="$(realpath -m "$repo_path")"
fi

volume_mount="$repo_path:$mount_point"

# ------------------------------------------------------------
# Ensure ansible-container exists and is running
# ------------------------------------------------------------

if ! docker ps -a --format '{{.Names}}' | grep -qx "$container_name"; then
    echo "Creating ansible-container..."
    docker run -d \
        --name "$container_name" \
        -v "$volume_mount" \
        -e ANSIBLE_ROLES_PATH="$mount_point/playbooks/roles" \
        "$docker_image" \
        sh -c "sleep infinity"
    started_container=true
elif ! docker ps --format '{{.Names}}' | grep -qx "$container_name"; then
    echo "Starting ansible-container..."
    docker start "$container_name" >/dev/null
    started_container=true
fi

# ------------------------------------------------------------
# Execute playbook
# ------------------------------------------------------------

echo "Executing playbook..."
echo ""

set +e
docker exec "$container_name" sh -c \
    "chmod 600 $mount_point/$key_path && ansible-playbook $mount_point/$playbook -i $mount_point/$inventory_file"
exit_code=$?
set -e

# ------------------------------------------------------------
# Stop container if we started it
# ------------------------------------------------------------

if [ "$started_container" = true ]; then
    echo "Stopping ansible-container..."
    docker stop "$container_name" >/dev/null
fi

if [ $exit_code -eq 0 ]; then
    echo ""
    echo "============================================"
    echo " Playbook completed successfully!"
    echo "============================================"
else
    echo ""
    echo "============================================"
    echo " Playbook execution failed!"
    echo "============================================"
    exit $exit_code
fi
