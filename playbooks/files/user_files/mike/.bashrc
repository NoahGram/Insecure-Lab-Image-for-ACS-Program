# TechCorp Solutions - Mike Thompson Profile
# IT Manager Account
# mike.thompson@techcorp.local

# Frequently used commands
alias ll='ls -la'
alias grep='grep --color=auto'
alias df='df -h'
alias free='free -h'
alias ports='netstat -tuln'
alias services='systemctl list-units --type=service --state=running'

# Quick access aliases
alias logs='sudo tail -f /var/log/syslog'
alias apachelog='sudo tail -f /var/log/apache2/error.log'
alias mysqlcon='mysql -u root -pmaria'

# Admin paths
export PATH=$PATH:/usr/sbin:/sbin

# History settings
export HISTSIZE=10000
export HISTFILESIZE=20000
export HISTTIMEFORMAT="%Y-%m-%d %H:%M:%S "

# Color prompt for IT Manager (red - admin indicator)
export PS1='\[\033[01;31m\][mike@techcorp]\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Welcome message
echo "Welcome Mike - TechCorp IT Manager"
echo "Email: mike.thompson@techcorp.local"