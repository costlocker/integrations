
# Webhooks integration

## Installation

### Frontend

```
cd frontend
yarn install
yarn watch-css
yarn start
```

### Nginx example

Take a look at [/.docker/nginx.conf](/.docker/nginx.conf).

### Docker

```bash
docker-compose -f docker-compose.yml up --build -d
```

### Nginx

```
server {  
  listen 8080;
  server_name webhooks.integrations-costlocker.dev;

  location / {
    proxy_pass http://127.0.0.1:19994/;
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

Nginx is recommended. Following configguration does not rewrite html requests,
Only homepage works, not http://webhooks.integrations-costlocker.dev/login).

You would have to use extra web directory with symlinks and `.htaccess`.
Take a look into [history](https://github.com/costlocker/integrations), if you are interested.

```bash
# /etc/hosts
127.0.0.1 webhooks.integrations-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName webhooks.integrations-costlocker.dev
  DocumentRoot "/path-to/webhooks/frontend/build"
  RewriteEngine On
</VirtualHost>
```