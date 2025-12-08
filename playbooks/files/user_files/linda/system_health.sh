#!/bin/bash
# Comprehensive System Health Check Script
# System Administrator Tool

echo "=== SYSTEM HEALTH CHECK REPORT ==="
echo "Generated: $(date)"
echo "Host: $(hostname -f)"
echo ""

# System uptime and load
echo "=== SYSTEM UPTIME ==="
uptime
echo ""

# Disk usage check
echo "=== DISK USAGE ==="
df -h | grep -vE '^Filesystem|tmpfs|cdrom'
echo ""

# Memory usage
echo "=== MEMORY USAGE ==="
free -h
echo ""

# CPU usage
echo "=== TOP CPU PROCESSES ==="
ps aux --sort=-%cpu | head -10
echo ""

# Memory usage by process
echo "=== TOP MEMORY PROCESSES ==="  
ps aux --sort=-%mem | head -10
echo ""

# Network connections
echo "=== NETWORK CONNECTIONS ==="
netstat -tuln | head -20
echo ""

# Service status
echo "=== CRITICAL SERVICE STATUS ==="
services=("apache2" "mysql" "ssh" "gitea")
for service in "${services[@]}"; do
    if systemctl is-active --quiet "$service"; then
        echo "✓ $service: RUNNING"
    else  
        echo "✗ $service: NOT RUNNING"
    fi
done
echo ""

# Log file sizes
echo "=== LOG FILE SIZES ==="
find /var/log -name "*.log" -type f -exec ls -lh {} \; 2>/dev/null | head -10
echo ""

# Failed login attempts
echo "=== RECENT FAILED LOGINS ==="
grep "Failed password" /var/log/auth.log 2>/dev/null | tail -5 || echo "No failed logins found"
echo ""

# System load warnings
load1=$(uptime | awk -F'load average:' '{ print $2 }' | cut -d, -f1 | sed 's/ //g')
if (( $(echo "$load1 > 2.0" | bc -l) )); then
    echo "⚠️  WARNING: High system load detected: $load1"
fi

# Disk usage warnings
df -h | awk '$5 > 80 { print "⚠️  WARNING: High disk usage on " $6 ": " $5 }'

echo ""
echo "=== HEALTH CHECK COMPLETE ==="