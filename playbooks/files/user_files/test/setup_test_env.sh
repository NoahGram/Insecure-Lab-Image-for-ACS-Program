#!/bin/bash
# Test Environment Setup Script
# Prepares development/testing environment

echo "Setting up test environment..."

# Create test directories
mkdir -p ~/projects
mkdir -p ~/scripts
mkdir -p ~/temp
mkdir -p ~/logs

# Download sample test files
echo "Creating sample test files..."
cat > ~/projects/test_app.py << 'EOF'
#!/usr/bin/env python3
import requests
import json

# Simple web app tester
def test_web_endpoints():
    endpoints = [
        'http://localhost',
        'http://localhost/info.php',
        'http://localhost/xss_test.php',
        'http://localhost:3000'  # Gitea
    ]
    
    for url in endpoints:
        try:
            response = requests.get(url, timeout=5)
            print(f"{url}: {response.status_code}")
        except Exception as e:
            print(f"{url}: ERROR - {e}")

if __name__ == "__main__":
    test_web_endpoints()
EOF

# Set permissions
chmod +x ~/projects/test_app.py

echo "Test environment setup completed!"
echo "Files created in:"
echo "  - ~/projects/"
echo "  - ~/scripts/"
echo "  - ~/temp/"
echo "  - ~/logs/"