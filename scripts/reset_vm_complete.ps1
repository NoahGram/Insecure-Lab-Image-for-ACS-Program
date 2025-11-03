# VirtualBox VM Reset Commands
# Run these in PowerShell to completely reset the VM

# 1. Stop the VM
&"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" controlvm NewTestServer poweroff

# 2. Create a snapshot before reset (optional - for safety)
&"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" snapshot NewTestServer take "before-reset" --description "Snapshot before complete reset"

# 3. Reset to fresh state - Option A: Restore VM to initial state
# &"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" snapshot NewTestServer restore "fresh-install"

# 3. Reset to fresh state - Option B: Complete rebuild (if no initial snapshot)
# This would require reimporting the original VM or recreating it

# 4. Start the VM
&"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm NewTestServer --type headless

# Wait for VM to boot
Start-Sleep 60

# 5. Test SSH connection
ssh -i Keys\vps_key -p 2222 noah@host.docker.internal "echo 'VM Reset Complete - Ready for fresh deployment'"