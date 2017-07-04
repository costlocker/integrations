
# Fakturoid integration

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

### Docker

```bash
# create empty directory if you have db dir in subdirectory
# otherwise: directory "/var/lib/postgresql/data" exists but is not empty
mkdir backend/var/database

# build
docker-compose -f docker-compose.yml up --build -d

# create user and DB
docker exec -it fakturoid_postgres_1 createuser costlocker_fakturoid --pwprompt -U postgres -W
docker exec -it fakturoid_postgres_1 createdb costlocker_fakturoid -e -E utf8 --owner costlocker_fakturoid -U postgres -W

# run migrations
docker exec -it fakturoid-costlocker /app/backend/bin/console migrations:migrate
```

### Nginx

```
server {  
  listen 8080;
  server_name fakturoid.integrations-costlocker.dev;

  location / {
    proxy_pass http://127.0.0.1:19996/;
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