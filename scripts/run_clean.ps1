# Clean Deployment Script
# Deploys clean base lab environment with all services
# Run from the ansible-control-node root directory

# Save the original location to restore later
$OriginalLocation = Get-Location

try {
    # Change to project root directory
    Set-Location -Path (Split-Path -Parent $PSScriptRoot)

    Write-Host "🚀 Starting Clean Lab Deployment..." -ForegroundColor Cyan
    Write-Host "📋 Running Ansible Playbook: site_clean.yml (Role-Based)" -ForegroundColor Yellow
    docker run --rm `
      -v D:\ansible-control-node:/ansible `
      -e ANSIBLE_ROLES_PATH=/ansible/playbooks/roles `
      ansible-control-node `
      ansible-playbook /ansible/playbooks/site_clean.yml -i /ansible/inventory.ini
}
finally {
    # Restore the original location
    Set-Location -Path $OriginalLocation
}
