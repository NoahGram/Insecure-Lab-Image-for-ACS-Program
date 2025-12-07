#!/bin/bash
# System maintenance script - runs daily at 2:00 AM
# Performs routine cleanup and backup verification
# Last modified: 2025-11-15 by sysadmin

LOG_FILE="/var/log/maintenance.log"
BACKUP_DIR="/backup"
RETENTION_DAYS=7

log_msg() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

log_msg "Starting daily maintenance tasks"

# Clean old temporary files
find /tmp -type f -mtime +3 -delete 2>/dev/null
log_msg "Cleaned temporary files older than 3 days"

# Rotate old backups
find "$BACKUP_DIR" -name "*.sql" -mtime +$RETENTION_DAYS -delete 2>/dev/null
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete 2>/dev/null
log_msg "Rotated backups older than $RETENTION_DAYS days"

# Check disk space
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
if [ "$DISK_USAGE" -gt 80 ]; then
    log_msg "WARNING: Disk usage is at ${DISK_USAGE}%"
fi

# Verify backup integrity
if [ -f "$BACKUP_DIR/db_full.sql" ]; then
    log_msg "Database backup exists: $(ls -lh $BACKUP_DIR/db_full.sql | awk '{print $5}')"
else
    log_msg "WARNING: No database backup found"
fi

# Clear old log entries
journalctl --vacuum-time=7d 2>/dev/null

log_msg "Daily maintenance completed"
