# Test User Profile
# Development/Testing Account

# Basic aliases for testing
alias ll='ls -la'
alias la='ls -A'
alias l='ls -CF'
alias ..='cd ..'
alias ...='cd ../..'

# Development tools
alias serve='python3 -m http.server 8000'
alias json='python3 -m json.tool'

# Colored prompt for test user (green)
export PS1='\[\033[01;32m\]\u@\h\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Add local bin to path
export PATH=$HOME/.local/bin:$PATH