# D:\ansible-control-node\run_vulnerable.ps1
#
# Run Ansible Playbook to introduce all vulnerabilities

Write-Host "Running Ansible Playbook: 02_introduce_vulnerabilities.yml"
docker run --rm `
  -v D:\ansible-control-node:/ansible `
  ansible-control-node `
  ansible-playbook /ansible/playbooks/02_introduce_vulnerabilities.yml -i /ansible/inventory.ini