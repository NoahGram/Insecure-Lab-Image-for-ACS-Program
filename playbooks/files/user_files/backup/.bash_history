ls
cd /backup
./automated_backup.sh
cat /var/log/backup.log
mysql -u root -pmaria -e "SELECT COUNT(*) FROM gitea.user;"
tar -tzf /backup/www_backup_20251201.tar.gz | head
du -sh /backup/*
df -h
rsync -avz /var/www/html/ /backup/www_mirror/
scp -i ~/.ssh/id_rsa /backup/db_full.sql admin@backup-server:/storage/
ssh -i ~/.ssh/id_rsa root@192.168.1.1 "ls /backups"
crontab -l
sudo systemctl status cron
cat /etc/cron.d/backup-jobs
mysqldump -u mantisbt -papache2Mantis mantisbt > /backup/mantis_backup.sql
ls -la ~/.ssh/
chmod 600 ~/.ssh/id_rsa
ssh-keygen -t rsa -b 4096
cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
exit
