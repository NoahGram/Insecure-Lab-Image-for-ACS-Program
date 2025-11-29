# Complete Mail System Guide

## 🚀 What's Deployed

Your Ansible playbook now deploys a **complete mail server stack**:

### Components:
1. **Postfix** - SMTP Server (sending/receiving mail)
2. **Dovecot** - IMAP/POP3 Server (mailbox access)
3. **Roundcube** - Webmail Interface (web-based email client)
4. **SASL Authentication** - Secure SMTP authentication
5. **TLS Encryption** - Encrypted connections

---

## 🌐 Web Access

### Roundcube Webmail
**URL**: `http://localhost:8080/roundcube` or `http://YOUR_VM_IP/roundcube`

**Login Credentials**:
- **Username**: Linux system username (e.g., `vagrant`, `user`)
- **Password**: Linux system password

---

## 📧 CLI/Terminal Usage

### 1. Send Email via Command Line

```bash
# Install mailutils if not done by Ansible
sudo apt install mailutils

# Send a simple email
echo "Test email body" | mail -s "Test Subject" user@lab.local

# Send email with content from file
mail -s "Subject" user@lab.local < message.txt

# Send to multiple recipients
echo "Body" | mail -s "Subject" user1@lab.local,user2@lab.local
```

### 2. Check Mail Queue

```bash
# View mail queue
mailq
# or
postqueue -p

# View specific message in queue
postcat -q MESSAGE_ID

# Flush queue (retry sending)
postqueue -f

# Delete specific message
sudo postsuper -d MESSAGE_ID

# Delete ALL queued mail
sudo postsuper -d ALL
```

### 3. View Mailbox (CLI)

```bash
# Check mail for current user
mail

# Inside mail program:
# - Type number to read message
# - 'd' to delete
# - 'q' to quit

# Alternative: Use mutt (more user-friendly)
sudo apt install mutt
mutt
```

### 4. Test Mail Delivery

```bash
# Test local delivery
echo "Test" | mail -s "Local Test" $USER

# Check if mail was delivered
ls -la ~/Maildir/new/

# Read mail file directly
cat ~/Maildir/new/*
```

### 5. Monitor Mail Logs

```bash
# Follow mail logs in real-time
sudo tail -f /var/log/mail.log

# Search for specific sender
sudo grep "from=<user@lab.local>" /var/log/mail.log

# Check authentication attempts
sudo grep "sasl_method" /var/log/mail.log

# View systemd journal
sudo journalctl -u postfix -f
sudo journalctl -u dovecot -f
```

### 6. Check Service Status

```bash
# Check all mail services
sudo systemctl status postfix
sudo systemctl status dovecot

# Restart services
sudo systemctl restart postfix
sudo systemctl restart dovecot

# Check if ports are listening
sudo netstat -tulpn | grep -E ':(25|143|587|993)'
```

### 7. Test SMTP Connection

```bash
# Test SMTP locally
telnet localhost 25

# Commands to type in telnet:
EHLO lab.local
MAIL FROM: <user@lab.local>
RCPT TO: <recipient@lab.local>
DATA
Subject: Test

This is a test email.
.
QUIT
```

### 8. Test IMAP Connection

```bash
# Test IMAP
telnet localhost 143

# Commands:
a1 LOGIN username password
a2 LIST "" "*"
a3 SELECT INBOX
a4 FETCH 1 BODY[]
a5 LOGOUT
```

### 9. Check Postfix Configuration

```bash
# View all Postfix settings
postconf

# View specific setting
postconf myhostname
postconf mydomain

# Test Postfix config for errors
postfix check

# Reload config without stopping
sudo postfix reload
```

### 10. Create Test Users

```bash
# Create mail user
sudo useradd -m -s /bin/bash testuser
echo "testuser:password123" | sudo chpasswd

# Verify Maildir was created (sent when first email arrives)
ls -la /home/testuser/Maildir/
```

---

## 🔧 Advanced Testing

### Send Authenticated Email (SMTP with AUTH)

```bash
# Using swaks (Swiss Army Knife for SMTP)
sudo apt install swaks

# Send authenticated email
swaks --to user@lab.local \
      --from sender@lab.local \
      --server localhost:587 \
      --auth LOGIN \
      --auth-user username \
      --auth-password password \
      --tls

# Test TLS connection
swaks --to user@lab.local \
      --from sender@lab.local \
      --server localhost:25 \
      --tls
```

### Check Mailbox Size

```bash
# Check mailbox size for user
du -sh ~/Maildir

# Count emails in folders
find ~/Maildir/new -type f | wc -l
find ~/Maildir/cur -type f | wc -l
```

### Debug Mail Delivery

```bash
# Enable verbose logging temporarily
sudo postconf -e debug_peer_list=localhost
sudo postconf -e debug_peer_level=2
sudo postfix reload

# Send test email and watch logs
sudo tail -f /var/log/mail.log &
echo "Debug test" | mail -s "Debug" user@lab.local

# Disable debug after
sudo postconf -e debug_peer_list=
sudo postconf -e debug_peer_level=0
sudo postfix reload
```

---

## 🔐 Security Features Enabled

1. ✅ **SASL Authentication** - Users must authenticate to send email
2. ✅ **TLS Encryption** - Encrypted connections (opportunistic)
3. ✅ **Submission Port (587)** - Proper authenticated submission
4. ✅ **IMAP/POP3** - Full mailbox access protocols
5. ✅ **Firewall Rules** - Ports 25, 143, 587, 993, 995 opened

---

## 📊 Configuration Summary

### Postfix (SMTP)
- **Hostname**: `lab.local`
- **Ports**: 25 (SMTP), 587 (Submission)
- **Mailbox Format**: Maildir (~/Maildir/)
- **Auth**: Dovecot SASL
- **TLS**: Opportunistic (may)

### Dovecot (IMAP/POP3)
- **Ports**: 143 (IMAP), 993 (IMAPS), 995 (POP3S)
- **Auth Mechanisms**: PLAIN, LOGIN
- **Mail Location**: maildir:~/Maildir

### Roundcube
- **URL**: http://localhost:8080/roundcube
- **Database**: MariaDB/MySQL
- **PHP Version**: System default
- **Session Lifetime**: 30 minutes

---

## 🐛 Troubleshooting

### Email Not Sending
```bash
# Check if Postfix is running
sudo systemctl status postfix

# Check for errors
sudo tail -50 /var/log/mail.log | grep -i error

# Verify DNS/hostname
hostname -f
postconf myhostname
```

### Cannot Login to Roundcube
```bash
# Check Dovecot status
sudo systemctl status dovecot

# Test IMAP manually
telnet localhost 143

# Check Roundcube logs
sudo tail -f /var/www/html/roundcube/logs/errors.log

# Verify database connection
mysql -u roundcube -proundcube_secure_pass roundcube -e "SHOW TABLES;"
```

### Authentication Failures
```bash
# Check SASL is working
sudo testsaslauthd -u username -p password

# Verify Dovecot auth socket
ls -la /var/spool/postfix/private/auth

# Check permissions
sudo ls -la /var/spool/postfix/private/
```

### Mail Not Being Received
```bash
# Check if Postfix is accepting connections
sudo netstat -tulpn | grep :25

# Test delivery locally
echo "Test" | mail -s "Test" $USER
ls ~/Maildir/new/

# Check mail delivery logs
sudo grep "status=sent" /var/log/mail.log
```

---

## 📝 Common Tasks

### Change Mail Password
```bash
# Change Linux user password (used for IMAP/SMTP auth)
sudo passwd username
```

### Backup Mail Data
```bash
# Backup all mailboxes
sudo tar -czf /backup/mailboxes-$(date +%Y%m%d).tar.gz /home/*/Maildir

# Backup Roundcube database
mysqldump -u root roundcube > roundcube_backup.sql
```

### View User's Mail Statistics
```bash
# Count emails
find ~/Maildir -type f | wc -l

# Find largest emails
find ~/Maildir -type f -exec du -h {} + | sort -rh | head -10

# Recently received emails
ls -lt ~/Maildir/new/ | head
```

---

## 🎯 Quick Test Checklist

```bash
# 1. Services running?
sudo systemctl status postfix dovecot apache2

# 2. Can send local mail?
echo "Test" | mail -s "Test" $USER

# 3. Mail delivered?
ls ~/Maildir/new/

# 4. Can login to Roundcube?
# Open browser: http://localhost:8080/roundcube

# 5. SMTP working?
telnet localhost 25
# Type: EHLO lab.local

# 6. IMAP working?
telnet localhost 143
# Type: a1 LOGIN username password

# 7. Queue empty?
mailq
```

---

## 🔗 Access Points

| Service | Type | Access | Port |
|---------|------|--------|------|
| Roundcube | Web | http://localhost:8080/roundcube | 80 |
| SMTP | Mail | localhost:25 | 25 |
| SMTP Submission | Mail | localhost:587 | 587 |
| IMAP | Mail | localhost:143 | 143 |
| IMAPS | Mail | localhost:993 | 993 |
| POP3S | Mail | localhost:995 | 995 |

---

## 💡 Pro Tips

1. **Always use localhost** for testing initially
2. **Check logs first** when troubleshooting: `/var/log/mail.log`
3. **Test with telnet** before using complex clients
4. **Maildir format** means each email is a separate file
5. **Roundcube uses Linux accounts** - create users with `useradd`
6. **TLS certificates** are self-signed by default (expect warnings)

---

**Your mail system is now production-ready for educational/lab use!** 🎉
