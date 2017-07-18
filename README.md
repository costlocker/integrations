
# Costlocker integrations

## https://integrations.costlocker.com/

### Official integrations

We've listened to you and build some of the most wanted integrations.

* [Synchronize Costlocker and Basecamp projects](/basecamp) - https://basecamp.integrations.costlocker.com
* [Create invoices in Fakturoid](/fakturoid) - https://fakturoid.integrations.costlocker.com
* [Import projects from Harvest to Costlocker](/harvest) - https://harvest.integrations.costlocker.com

### Community integrations

Costlocker provides an [API](http://docs.costlocker.apiary.io).
Write your own integrations and let us know about it!

<br>

## Development

### Basecamp, Fakturoid, Harvest

Addons have similar structure:

* **backend** - [silex](https://silex.symfony.com/) for api, [doctrine/migrations](https://github.com/doctrine/migrations) for managing Postgres database, [league/oauth2-client](http://oauth2-client.thephpleague.com/), sessions and logs saved in `backend/var/` ([sentry](https://sentry.io/) is supported for logging)
* **frontend** - [create-react-app](https://github.com/facebookincubator/create-react-app) for app, [ui-router](https://ui-router.github.io/react/) for routing, [immstruct](https://github.com/omniscientjs/immstruct) for state management, [custom bootstrap theme](https://github.com/facebookincubator/create-react-app/blob/master/packages/react-scripts/template/README.md#using-a-custom-theme)
* **configuration** - in `.env` files
* **webserver** - [nginx (+php-fpm)](/fakturoid/.docker/nginx.conf), or Apache (backend and frontend is symlinked in directory [`web/`](/harvest/web))

### Docker

Every addon contains an example of `docker-compose.yml`.
Copy the example and configure it according to your needs.

```bash
cd addon
cp .docker-compose.yml.example .docker-compose.yml
nano .docker-compose.yml
```

_Recommendation:_ use docker volumes for persistent files (logs, sessions, database),
otherwise you'll lose data when container is restarted

```yaml
        volumes:
            - "./backend/var/sessions:/app/backend/var/sessions"
            - "./backend/var/database:/app/backend/var/database"
            - "./backend/var/log:/app/backend/var/log"
```

#### Shared Postgres 

You can define shared Postgres, if you want to run only one Postgres database.

* Postgres is connected via `network` and `external_links`.
* At first create the network `docker network create costlocker_addons`, otherwise you'll get an error.

##### Shared service

```yaml
version: "2"

services:
    postgres:
        image: postgres:9.6.3
        volumes:
            - "./db:/var/lib/postgresql/data"
        environment:
            POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}

networks:
  default:
    external:
      name: costlocker_addons
```

##### Addon

```yaml
version: "2"

services:
    application:
        container_name: addon-costlocker
        build:
            context: .
            dockerfile: ./.docker/Dockerfile
        external_links:
            - postgres:db.local

networks:
  default:
    external:
      name: costlocker_addons

```
