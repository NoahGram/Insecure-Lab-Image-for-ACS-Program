# Backup User Profile
# Automated backup service account

# Backup-specific aliases
alias ll='ls -la'
alias backup_status='ls -la /backup/'
alias backup_logs='tail -f /var/log/backup.log'
alias check_space='df -h /backup'

# Simple prompt for backup user
export PS1='\[\033[01;36m\][BACKUP]\[\033[00m\] \u@\h:\w\$ '

# Backup paths
export BACKUP_HOME=/backup
export BACKUP_LOGS=/var/log

# Limited history for service account
export HISTSIZE=1000
export HISTFILESIZE=1000

echo "Backup service account active"
echo "Backup location: /backup"
echo "Check status with: backup_status"