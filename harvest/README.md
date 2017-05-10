
# Harvest integration

## Installation

### Backend

```
cd backend
composer install
cp .env.example .env
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
127.0.0.1 harvest-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName harvest-costlocker.dev
  DocumentRoot "/path-to/harvest/web"
  RewriteEngine On
</VirtualHost>
```
