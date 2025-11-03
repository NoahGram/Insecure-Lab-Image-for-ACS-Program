# Quick Reset Script
# Restores VM to base snapshot for fresh deployment
# This is the main reset method for the lab

# Execute the snapshot reset
& "$PSScriptRoot\reset_vm_snapshot.ps1"

# Check if successful
if ($LASTEXITCODE -eq 0) {
    Write-Host "Ready to deploy!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Run one of:" -ForegroundColor Yellow
    Write-Host "  .\scripts\run_clean.ps1" -ForegroundColor Cyan
    Write-Host "  .\scripts\run_vulnerable.ps1" -ForegroundColor Cyan
} else {
    Write-Host "Reset failed - see errors above" -ForegroundColor Red
    exit 1
}
