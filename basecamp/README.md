
# Harvest integration

## Installation

### Backend

```
cd backend
createdb costlocker_basecamp -e -E utf8
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
127.0.0.1 basecamp.integrations-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName basecamp.integrations-costlocker.dev
  DocumentRoot "/path-to/basecamp/web"
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
docker exec -it basecamp_postgres_1 createuser costlocker_basecamp --pwprompt -U postgres -W
docker exec -it basecamp_postgres_1 createdb costlocker_basecamp -e -E utf8 --owner costlocker_basecamp -U postgres -W

# run migrations
docker exec -it basecamp-costlocker /app/backend/bin/console migrations:migrate

# run daemon
## you can setup daemon in your host supervisor, upstart, ...
## or you can use docker service, take a look at 'queue_daemon' in docker-compose.yml.example
## for experiments and development we recommend to run daemon interactively in your terminal
docker exec -it basecamp-costlocker /app/backend/bin/console queue:daemon

# refresh tokens in cron
docker exec -it basecamp-costlocker /app/backend/bin/console refreshTokens --expiration "1 day" --execute
```

### Nginx

```
server {  
  listen 8080;
  server_name basecamp.integrations-costlocker.dev;

  location / {
    proxy_pass http://127.0.0.1:19997/;
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