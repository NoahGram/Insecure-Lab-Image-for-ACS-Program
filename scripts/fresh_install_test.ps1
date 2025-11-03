# Fresh Install Test Script
# Resets VM and deploys clean environment for testing
# Run from the ansible-control-node root directory

Write-Host "Starting Fresh Install Test..." -ForegroundColor Cyan

# Change to project root directory
Set-Location -Path (Split-Path -Parent $PSScriptRoot)

# Step 1: Reset current deployment
Write-Host "Step 1: Resetting current deployment..." -ForegroundColor Yellow
docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key; ansible-playbook /ansible/playbooks/reset_vm.yml -i /ansible/inventory.ini"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Reset completed successfully" -ForegroundColor Green
    
    # Step 2: Deploy fresh environment
    Write-Host "Step 2: Deploying fresh environment..." -ForegroundColor Yellow
    docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key; ansible-playbook /ansible/playbooks/site_clean.yml -i /ansible/inventory.ini"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Fresh deployment completed successfully!" -ForegroundColor Green
        Write-Host "LabSys Wiki available at: http://localhost:8080/labsys-wiki/" -ForegroundColor Cyan
        Write-Host "Landing page redirects from: http://localhost:8080/" -ForegroundColor Cyan
    } else {
        Write-Host "Deployment failed" -ForegroundColor Red
    }
} else {
    Write-Host "Reset failed" -ForegroundColor Red
}