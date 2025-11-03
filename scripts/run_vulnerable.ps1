# Vulnerable Deployment Script
# Deploys lab environment with vulnerabilities for security testing
# Run from the ansible-control-node root directory

# Save the original location to restore later
$OriginalLocation = Get-Location

try {
    # Change to project root directory
    Set-Location -Path (Split-Path -Parent $PSScriptRoot)

    Write-Host "⚠️ Starting Vulnerable Lab Deployment..." -ForegroundColor Red
    Write-Host "📋 Running Ansible Playbook: site_vulnerable.yml (Role-Based)" -ForegroundColor Yellow
    docker run --rm `
      -v D:\ansible-control-node:/ansible `
      ansible-control-node `
      ansible-playbook /ansible/playbooks/site_vulnerable.yml -i /ansible/inventory.ini
}
finally {
    # Restore the original location
    Set-Location -Path $OriginalLocation
}
