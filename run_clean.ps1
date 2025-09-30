# D:\ansible-control-node\run_clean.ps1
#
# 1. Revert VirtualBox VM to the clean snapshot (manual step, but fast)
# 2. Run Ansible Playbook to install core services

Write-Host "Reverting VM to snapshot '00 - Clean Base Image'..."
# NOTE: Replace 'YourVMName' with the actual name of your VirtualBox VM
# VBoxManage snapshot "YourVMName" restore "00 - Clean Base Image"

Write-Host "Running Ansible Playbook: 01_clean_image_base.yml"
docker run --rm `
  -v D:\ansible-control-node:/ansible `
  ansible-control-node `
  ansible-playbook /ansible/playbooks/01_clean_image_base.yml -i /ansible/inventory.ini