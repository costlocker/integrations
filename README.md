
# Costlocker integrations https://integrations.costlocker.com/

* [Synchronize Costlocker and Basecamp projects](/basecamp) - https://basecamp.integrations.costlocker.com
* [Create invoices in Fakturoid](/fakturoid) - https://fakturoid.integrations.costlocker.com
* [Import projects from Harvest to Costlocker](/harvest) - https://harvest.integrations.costlocker.com

## Docker

### Shared Postgres 

Postgres is connected via `network` and `external_links`.
At first create the network `docker network create costlocker_addons`.
Otherwise you'll get an error

```bash
$ docker-compose up -d
ERROR: Network costlocker_addons declared as external, but could not be found.
Please create the network manually using `docker network create costlocker_addons` and try again.
```

#### Shared service

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

#### Addon

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
