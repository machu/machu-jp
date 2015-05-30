FROM php:apache

RUN mkdir -p /var/pukiwiki/backup && \
    mkdir -p /var/pukiwiki/cache && \
    mkdir -p /var/pukiwiki/counter && \
    mkdir -p /var/pukiwiki/diff && \
    mkdir -p /var/pukiwiki/wiki && \
    chown -R www-data:www-data /var/pukiwiki && \
    ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/
COPY pukiwiki.conf /etc/apache2/sites-enabled/
COPY start.sh /usr/local/bin/
COPY pukiwiki /var/www/html/
RUN cp -p default.ini.php  keitai.ini.php  pukiwiki.ini.php  rules.ini.php en.lng.php  ja.lng.php /var/pukiwiki/ && \
    cp -rp skin /var/pukiwiki/ && \
    cp -rp plugin /var/pukiwiki/

VOLUME /var/pukiwiki
CMD ["start.sh"]
