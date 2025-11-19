#!/bin/bash
# Admin System Backup Script
# DO NOT RUN IN PRODUCTION - Contains hardcoded passwords

echo "Starting system backup..."
date >> /tmp/backup.log

# Database backups with embedded credentials
mysqldump -u root -pmaria --all-databases > /tmp/mysql_backup_$(date +%Y%m%d).sql
echo "MySQL backup completed with password: maria" >> /tmp/backup.log

# Application backups
tar -czf /tmp/web_backup_$(date +%Y%m%d).tar.gz /var/www/html/
tar -czf /tmp/config_backup_$(date +%Y%m%d).tar.gz /etc/

# System information
echo "=== SYSTEM INFO ===" >> /tmp/backup.log
df -h >> /tmp/backup.log
free -h >> /tmp/backup.log
ps aux >> /tmp/backup.log

echo "Backup completed successfully"
chmod 644 /tmp/backup.log  # World readable log with passwords!