
# Harvest integration

## Installation

### Backend

```
cd backend
createdb costlocker_basecamp -e -E utf8
composer install
cp .env.example .env
bin/console migrations:migrate
```

##### Development

```
bin/console mig:diff --formatted
bin/console mig:exec 20170606100151  --up
bin/console mig:exec 20170606100151  --down
```

### Frontend

```
cd frontend
yarn install
cp .env.example .env
```

### Apache VirtualHost example

```bash
# /etc/hosts
127.0.0.1 basecamp-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName harvest-costlocker.dev
  DocumentRoot "/path-to/basecamp/web"
  RewriteEngine On
</VirtualHost>
```
