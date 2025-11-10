# Vulnerable Lab Environment Setup Documentation

## Overview
This documentation is quick overview with commands for setting up the VPS quickly.

## Tasks beforehand
Port forwarding in VPS:

| Name    | Protocol | Host port | Guest port |
|---------|----------|-----------|------------|
| ssh     | TCP      | 2222      | 22         |
| apache2 | TCP      | 8080      | 80         |
| gitea   | TCP      | 3000      | 3000       |
| cockpit | TCP      | 9090      | 9090       |

### Commands in VPS
```sh
sudo -i (password)

ufw enable

ufw allow 22/tcp

ufw allow 80/tcp

ufw allow 3000/tcp

ufw allow 8080/tcp

ufw status numbered

systemctl enable ssh

systemctl status ssh
```

Delete the old vps_key and vps_key.pub first before proceeding or overwrite.

## VPS/Docker note
Don't forget to have Docker, VPS open.

## Commands in Powershell
```sh
1. ssh-keygen -t rsa -b 4096 -f .\Keys\vps_key (enter, enter)

# Change vps_key.pub to user@server

2. python generate_inventory.py

3. Get-Content D:\ansible-control-node\Keys\vps_key.pub | ssh -p 2222 (user)@localhost "cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys" (yes, password)

4. docker build -t ansible-control-node .

# If the powershell doesn't work switch to 5b
5a. .\scripts\run_clean.ps1

# No need when 5a works
5b. docker run --rm -v D:\ansible-control-node:/ansible ansible-control-node sh -c "chmod 600 /ansible/Keys/vps_key && ansible-playbook /ansible/playbooks/site_clean.yml -i /ansible/inventory.ini"
```

## Commands in cmd
```sh
6. ssh user@localhost -p 2222 (password)

# Check services if on
7. sudo systemctl status apache2 mariadb gitea cockpit postfix
```

## Urls
| Name        | Location  | Port | Router        | Database  | Username       | Password               |
|-------------|-----------|------|---------------|-----------|----------------|------------------------|
| apache2     | localhost | 8080 | /             | -         | root           | CleanLabPassword123!   |
| labsys-wiki | localhost | 8080 | /labsys-wiki  | -         | -              | -                      |
| gitea       | localhost | 3000 | /             | gitea     | gitea          | GiteaDBPassword123!    |
| mantisbt    | localhost | 8080 | /mantisbt     | mantisbt  | mantisbt       | MantisBTDBPassword123! |
| cockpit     | localhost | 9090 | /             | -         | [vps username] | [vps password]         |
| dokuwiki    | localhost | 8080 | /dokuwiki     | -         | admin          | -                      |
