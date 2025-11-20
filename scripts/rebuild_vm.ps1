# Rebuild Vagrant
vagrant halt
vagrant destroy -f
vagrant up
vagrant snapshot save base

# Reset
.\scripts\reset_vm.ps1  

# Run
.\scripts\run_clean.ps1
