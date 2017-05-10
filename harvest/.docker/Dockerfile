FROM nimmis/apache-php7

RUN a2enmod rewrite

ADD ./ /app
ADD ./.docker/httpd-vhosts.conf /etc/apache2/sites-available/000-default.conf

RUN php -r "readfile('https://getcomposer.org/installer');" > composer-setup.php && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer && \
    (cd /app/backend && composer install --no-dev) && \
    chown www-data.www-data -R /app 

EXPOSE 80
