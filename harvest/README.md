
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
yarn watch-css
yarn start
```

### Docker

```bash
docker-compose -f docker-compose.yml up --build -d
```

### Nginx

```
server {  
  listen 8080;
  server_name harvest.integrations-costlocker.dev;

  location / {
    proxy_pass http://127.0.0.1:19998/;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_set_header X-Forwarded-Host $host:$server_port;
    proxy_set_header X-Forwarded-Server $host;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_cache_bypass $http_upgrade;
  }
}
```

### Apache VirtualHost example

Nginx is recommended. Following configuration does not rewrite html requests,
Only homepage works, not http://harvest.integrations-costlocker.dev/step/1).

You would have to use extra web directory with symlinks and `.htaccess`.
Take a look into [history](https://github.com/costlocker/integrations), if you are interested.

```bash
# /etc/hosts
127.0.0.1 harvest.integrations-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName harvest.integrations-costlocker.dev
  DocumentRoot "/path-to/harvest/frontend/build"
  RewriteEngine On
  Alias /api  "/path-to/harvest/backend/web"
</VirtualHost>
```