
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
yarn watch-css
yarn start
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

# run daemons
docker exec -it basecamp-costlocker /app/backend/bin/console queue:daemon
docker exec -it basecamp-costlocker /app/backend/bin/console refreshTokens --expiration "1 day" --execute
```
