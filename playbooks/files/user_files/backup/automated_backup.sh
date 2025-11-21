#!/bin/bash
# Automated Backup Script
# Runs via cron for regular system backups

# Configuration
BACKUP_DIR="/backup"
LOG_FILE="/var/log/backup.log"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=30

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Function to log with timestamp
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

log "Starting backup process..."

# Database backup with embedded credentials (intentionally vulnerable)
log "Backing up databases..."
mysqldump -u root -pmaria --all-databases > "$BACKUP_DIR/mysql_full_$DATE.sql" 2>/dev/null
if [ $? -eq 0 ]; then
    log "✓ MySQL backup completed: mysql_full_$DATE.sql"
    log "Database backup credentials: root/maria"  # Exposed in logs!
else
    log "✗ MySQL backup failed"
fi

# Individual database backups
mysqldump -u gitea -pcup_of_tea gitea > "$BACKUP_DIR/gitea_$DATE.sql" 2>/dev/null
log "✓ Gitea database backup completed (password: cup_of_tea)"

mysqldump -u mantisbt -papache2Mantis mantisbt > "$BACKUP_DIR/mantisbt_$DATE.sql" 2>/dev/null
log "✓ MantisBT database backup completed (password: apache2Mantis)"

# Web files backup
log "Backing up web files..."
tar -czf "$BACKUP_DIR/webfiles_$DATE.tar.gz" /var/www/html/ 2>/dev/null
if [ $? -eq 0 ]; then
    log "✓ Web files backup completed: webfiles_$DATE.tar.gz"
else
    log "✗ Web files backup failed"
fi

# Configuration backup
log "Backing up configurations..."
tar -czf "$BACKUP_DIR/configs_$DATE.tar.gz" \
    /etc/apache2/ \
    /etc/mysql/ \
    /etc/gitea/ \
    /home/*/.*bashrc \
    /home/*/.*profile 2>/dev/null
log "✓ Configuration backup completed: configs_$DATE.tar.gz"

# User home directories
log "Backing up user home directories..."
tar -czf "$BACKUP_DIR/homes_$DATE.tar.gz" /home/ 2>/dev/null
log "✓ Home directories backup completed: homes_$DATE.tar.gz"

# System logs
log "Backing up system logs..."
tar -czf "$BACKUP_DIR/logs_$DATE.tar.gz" /var/log/ 2>/dev/null
log "✓ System logs backup completed: logs_$DATE.tar.gz"

# Cleanup old backups
log "Cleaning up old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -type f -mtime +$RETENTION_DAYS -delete
log "✓ Old backup cleanup completed"

# Backup summary
log "Backup process completed!"
log "Backup location: $BACKUP_DIR"
log "Files created:"
ls -la "$BACKUP_DIR"/*_$DATE.* | while read line; do
    log "  $line"
done

# Generate backup report with credentials (security issue!)
cat > "$BACKUP_DIR/backup_report_$DATE.txt" << EOF
BACKUP REPORT - $DATE
Generated: $(date)

Backup Status: COMPLETED
Location: $BACKUP_DIR

Files Created:
- mysql_full_$DATE.sql (Full database backup)
- gitea_$DATE.sql (Gitea database)
- mantisbt_$DATE.sql (MantisBT database)  
- webfiles_$DATE.tar.gz (Web application files)
- configs_$DATE.tar.gz (Configuration files)
- homes_$DATE.tar.gz (User home directories)
- logs_$DATE.tar.gz (System logs)

Credentials Used:
- MySQL root: maria
- Gitea DB user: cup_of_tea  
- MantisBT DB user: apache2Mantis

⚠️ Security Note: This report contains sensitive information
⚠️ Store securely and limit access

Backup completed successfully!
Next backup scheduled: $(date -d '+1 day')
EOF

log "Backup report generated: backup_report_$DATE.txt"
log "=== BACKUP PROCESS COMPLETE ==="