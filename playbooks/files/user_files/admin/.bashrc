# Admin User Profile
# System Administrator Account

# Frequently used commands
alias ll='ls -la'
alias grep='grep --color=auto'
alias df='df -h'
alias free='free -h'
alias ports='netstat -tuln'

# Admin paths
export PATH=$PATH:/usr/sbin:/sbin

# History settings
export HISTSIZE=10000
export HISTFILESIZE=20000

# Color prompt for admin (red)
export PS1='\[\033[01;31m\]\u@\h\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '