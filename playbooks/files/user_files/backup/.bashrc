# TechCorp Solutions - Backup Service Account
# Automated backup service
# backup-service@techcorp.local

# Backup-specific aliases
alias ll='ls -la'
alias backup_status='ls -la /backup/'
alias backup_logs='tail -f /var/log/backup.log'
alias check_space='df -h /backup'

# Simple prompt for backup user
export PS1='\[\033[01;33m\][backup@techcorp]\[\033[00m\]:\w\$ '

# Backup paths
export BACKUP_HOME=/backup
export BACKUP_LOGS=/var/log

# Limited history for service account
export HISTSIZE=1000
export HISTFILESIZE=1000

echo "TechCorp Backup Service Account"
echo "Backup location: /backup"
echo "Check status: backup_status"