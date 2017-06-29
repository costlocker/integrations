
# Harvest integration

## Installation

### Backend

```
cd backend
createdb costlocker_fakturoid -e -E utf8
cp .env.example .env
bin/init
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
yarn watch-css
yarn start
```

### Apache VirtualHost example

```bash
# /etc/hosts
127.0.0.1 fakturoid.integrations-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName fakturoid.integrations-costlocker.dev
  DocumentRoot "/path-to/fakturoid/web"
  RewriteEngine On
</VirtualHost>
```
