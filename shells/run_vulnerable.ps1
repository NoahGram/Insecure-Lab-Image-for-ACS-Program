# D:\ansible-control-node\run_vulnerable.ps1
#
# Run Ansible Playbook to introduce all vulnerabilities

Write-Host "Running Ansible Playbook: site_vulnerable.yml (Role-Based)"
docker run --rm `
  -v D:\ansible-control-node:/ansible `
  ansible-control-node `
  ansible-playbook /ansible/playbooks/site_vulnerable.yml -i /ansible/inventory.ini
