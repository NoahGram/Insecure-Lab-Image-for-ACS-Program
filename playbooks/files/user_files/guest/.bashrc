# Guest User Profile
# Limited access account

# Basic aliases
alias ll='ls -l'
alias la='ls -la'
alias ..='cd ..'

# Simple prompt for guest
export PS1='\u@\h:\w\$ '

# Limit command history
export HISTSIZE=100
export HISTFILESIZE=100

# Safety aliases to prevent accidents
alias rm='rm -i'
alias cp='cp -i'
alias mv='mv -i'