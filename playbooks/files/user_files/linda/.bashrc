# TechCorp Solutions - Linda Sanchez Profile
# Senior System Administrator
# linda.sanchez@techcorp.local

# Advanced aliases
alias ll='ls -la'
alias l='ls -CF'
alias la='ls -A'
alias grep='grep --color=auto'
alias fgrep='fgrep --color=auto'
alias egrep='egrep --color=auto'

# System monitoring aliases
alias psg='ps aux | grep'
alias topcpu='ps aux --sort=-%cpu | head'
alias topmem='ps aux --sort=-%mem | head'
alias df='df -h'
alias du='du -h'
alias free='free -h'
alias netstat='netstat -tuln'

# Log monitoring
alias logs='journalctl -f'
alias apache_logs='tail -f /var/log/apache2/access.log'
alias error_logs='tail -f /var/log/apache2/error.log'
alias mysql_logs='tail -f /var/log/mysql/mysql.log'

# Service management
alias services='systemctl list-units --type=service'
alias failed='systemctl --failed'

# Network tools
alias ports='netstat -tuln'
alias connections='ss -tuln'

# Security tools
alias lastlog='lastlog | grep -v "Never"'
alias failed_logins='grep "Failed password" /var/log/auth.log'

# Sysadmin prompt (cyan)
export PS1='\[\033[01;36m\][linda@techcorp]\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Extended PATH for admin tools  
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# History settings for audit trail
export HISTSIZE=50000
export HISTFILESIZE=100000
export HISTTIMEFORMAT="%Y-%m-%d %H:%M:%S "

# Welcome message
echo "Welcome Linda - TechCorp Senior Sysadmin"
echo "Email: linda.sanchez@techcorp.local"

# Auto-completion
if [ -f /etc/bash_completion ]; then
    . /etc/bash_completion
fi