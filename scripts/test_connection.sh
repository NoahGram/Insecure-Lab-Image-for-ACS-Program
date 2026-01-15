#!/usr/bin/env bash
# Test SSH Connection to VM
# This script loads .env and tests the SSH connection automatically

set -e

container_name="ansible-container"
started_container=false

if [ ! -f ".env" ]; then
    echo "ERROR: .env file not found!"
    echo "Please create .env from .env.example"
    exit 1
fi

echo "============================================"
echo " Testing VM Connection"
echo "============================================"
echo ""

# Load .env variables
eval "$(python3 load_env.py bash)"

echo "Target Configuration:"
echo "  Host: $VM1_HOSTNAME"
echo "  User: $VM1_USERNAME"
echo "  Port: $VM1_SSH_PORT"
echo ""

# Normalize path
repo_path="${ANSIBLE_CONTROL_NODE_PATH//\\//}"
mount_point="${DOCKER_MOUNT_POINT:-/ansible}"
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
        "$docker_image" \
        sh -c "sleep infinity"
    started_container=true
elif ! docker ps --format '{{.Names}}' | grep -qx "$container_name"; then
    echo "Starting ansible-container..."
    docker start "$container_name" >/dev/null
    started_container=true
fi

# ------------------------------------------------------------
# Execute SSH test
# ------------------------------------------------------------

echo "Executing test connection..."
echo ""

set +e
docker exec "$container_name" sh -c \
    "chmod 600 $mount_point/$key_path && ssh -o StrictHostKeyChecking=no -i $mount_point/$key_path -p $VM1_SSH_PORT $VM1_USERNAME@$VM1_HOSTNAME 'echo Connection successful'"
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
    echo " SUCCESS! VM is reachable"
    echo "============================================"
else
    echo ""
    echo "============================================"
    echo " FAILED! Could not connect to VM"
    echo "============================================"
    echo ""
    echo "Troubleshooting:"
    echo "  1. Check VM is running"
    echo "  2. Verify .env settings are correct"
    echo "  3. Check SSH key exists at: $VM1_SSH_KEY_PATH"
    echo "  4. Verify port forwarding for port $VM1_SSH_PORT"
    exit 1
fi
