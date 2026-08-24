FROM php:8.4-apache

RUN docker-php-ext-install pdo_mysql mysqli \
    && rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
        /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
        /etc/apache2/mods-enabled/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.conf \
    && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p assets/uploads/profiles assets/uploads/homestays assets/uploads/rooms assets/uploads/gallery \
    && cp -a assets/uploads /opt/sonam-seed-uploads \
    && chown -R www-data:www-data assets/uploads

EXPOSE 8080

CMD if [ ! -f assets/uploads/.volume-initialized ]; then cp -an /opt/sonam-seed-uploads/. assets/uploads/ && touch assets/uploads/.volume-initialized; fi \
    && chown -R www-data:www-data assets/uploads \
    && rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
        /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf \
    && a2enmod mpm_prefork >/dev/null \
    && sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-8080}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
