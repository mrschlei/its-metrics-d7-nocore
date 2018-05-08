FROM drupal7-cosign:latest

COPY . /var/www/html/

CMD /usr/local/bin/start.sh
