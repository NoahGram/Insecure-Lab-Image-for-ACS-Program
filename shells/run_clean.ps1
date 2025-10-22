# D:\ansible-control-node\run_clean.ps1
#
# 1. Revert VirtualBox VM to the clean snapshot (manual step, but fast)
# 2. Run Ansible Playbook to install core services

Write-Host "Reverting VM to snapshot '00 - Clean Base Image'..."
# NOTE: Replace 'YourVMName' with the actual name of your VirtualBox VM
# VBoxManage snapshot "YourVMName" restore "00 - Clean Base Image"

Write-Host "Running Ansible Playbook: site_clean.yml (Role-Based)"
docker run --rm `
  -v D:\ansible-control-node:/ansible `
  -e ANSIBLE_ROLES_PATH=/ansible/playbooks/roles `
  ansible-control-node `
  ansible-playbook /ansible/playbooks/site_clean.yml -i /ansible/inventory.ini
