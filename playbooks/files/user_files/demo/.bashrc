# Demo User Profile  
# Demonstration account for presentations

# Presentation-friendly aliases
alias demo='cd ~/demo_files'
alias show='cat'
alias list='ls -la'
alias clear='clear; echo "=== DEMO ENVIRONMENT ==="'

# Colored prompt for demo (yellow)
export PS1='\[\033[01;33m\]\u@\h\[\033[00m\]:\[\033[01;34m\]\w\[\033[00m\]\$ '

# Auto-change to demo directory on login
cd ~/demo_files 2>/dev/null || true

echo "=== Welcome to Demo Environment ==="
echo "Type 'demo' to go to demo files"
echo "Type 'list' to see available files"