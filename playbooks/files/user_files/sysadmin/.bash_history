cd /var/log
tail -f syslog
sudo systemctl status apache2
df -h
free -m
top
cd /var/www/html
ls -la
sudo nano /etc/apache2/sites-available/000-default.conf
sudo systemctl restart apache2
mysql -u root -pmaria -e "SHOW DATABASES;"
cd /home/backup
ls -la
cat backup_config.txt
sudo tar czf /backup/www_backup_$(date +%Y%m%d).tar.gz /var/www/html
mysqldump -u root -pmaria --all-databases > /backup/db_full.sql
scp /backup/db_full.sql backup@192.168.1.50:/backups/
history
sudo cat /etc/shadow
exit
cd /var/log/apache2
tail -100 error.log
grep -i error access.log
sudo journalctl -u apache2 --since "1 hour ago"
mysql -u gitea -pcup_of_tea gitea -e "SELECT * FROM user LIMIT 5;"
cd /opt/gitea
./gitea admin user list
sudo systemctl restart gitea
ping -c 4 8.8.8.8
curl -I http://localhost
netstat -tlnp
ss -tlnp
