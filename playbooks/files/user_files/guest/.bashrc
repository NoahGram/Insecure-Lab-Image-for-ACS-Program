# TechCorp Solutions - Intern Account
# Guest/Intern access account
# Email: intern@techcorp.local

# Basic aliases
alias ll='ls -l'
alias la='ls -la'
alias ..='cd ..'

# Simple prompt for intern
export PS1='\[\033[01;32m\][guest@techcorp]\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Limit command history
export HISTSIZE=100
export HISTFILESIZE=100

# Safety aliases to prevent accidents
alias rm='rm -i'
alias cp='cp -i'
alias mv='mv -i'

# Welcome message
echo "Welcome to TechCorp Solutions!"
echo "You are logged in as: guest (Intern)"
echo "Email: intern@techcorp.local"
echo ""
echo "Type 'cat ~/welcome.txt' for help"