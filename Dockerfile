FROM drupal7-cosign:latest

COPY . /var/www/html/

### change directory owner, as openshift user is in root group.
###RUN chown -R root:root /var/www/html /var/log/apache2 /var/lock/apache2 \
###	/var/run/apache2 /usr/local/etc/php /usr/local/lib/php

RUN mkdir -p /var/www/html/sites/default/
	
### Modify perms for the openshift user, who is not root, but part of root group.
RUN chmod -R g+r /var/www/html
 RUN chmod -R g+rw /var/log/apache2 /var/www/html /etc/apache2 \
	/etc/ssl/certs /etc/ssl/private /etc/apache2/mods-enabled /etc/apache2/sites-enabled
### 	/etc/apache2/sites-available /etc/apache2/mods-available \
### 	/var/lib/apache2/module/enabled_by_admin /var/lib/apache2/site/enabled_by_admin \
### 	/var/lock/apache2 /var/run/apache2 /usr/local/etc/php \
### 	 /usr/local/lib/php
RUN chmod g+x /etc/ssl/private


# From here: http://docs.drush.org/en/7.x/install/
# git is not previously installed on the image
ENV DRUSH_VERSION 8.1.17

RUN apt-get update \
  && apt-get install -y git

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer \
  && ln -s /usr/local/bin/composer /usr/bin/composer

RUN git clone https://github.com/drush-ops/drush.git /usr/local/src/drush \
  && cd /usr/local/src/drush \
  && git checkout ${DRUSH_VERSION} \
  && ln -s /usr/local/src/drush/drush /usr/bin/drush \
  && composer install \
  && drush --version
  # end drush doings


COPY start.sh /usr/local/bin
RUN chmod 755 /usr/local/bin/start.sh
CMD /usr/local/bin/start.sh
