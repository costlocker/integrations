
# Catalogue of Costlocker integrations

* [Synchronize Costlocker and Basecamp projects](/basecamp) - https://basecamp.integrations.costlocker.com
* [Import projects from Harvest to Costlocker](/basecamp) - https://harvest.integrations.costlocker.com

## Website

### Apache VirtualHost example

```bash
# /etc/hosts
127.0.0.1 integrations-costlocker.dev

# /etc/apache2/extra/httpd-vhosts.conf
<VirtualHost *:80>
  ServerName integrations-costlocker.dev
  DocumentRoot "/path-to-catalogue/"
</VirtualHost>
```

### Nginx

```
server {  
  listen 8080;
  server_name integrations-costlocker.dev;
  location / {
    root /path-to-catalogue;
    index index.html;
  }
}
```