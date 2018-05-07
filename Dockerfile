FROM drupal:7.58-apache

#MAINTAINER: 

#### Cosign Pre-requisites ###
WORKDIR /usr/lib/apache2/modules

ENV COSIGN_URL https://github.com/umich-iam/cosign/archive/cosign-3.4.0.tar.gz
ENV CPPFLAGS="-I/usr/kerberos/include"
ENV OPENSSL_VERSION 1.0.1t-1+deb8u7
ENV APACHE2=/usr/sbin/apache2

# install PHP and Apache2 here
RUN apt-get update \
	&& apt-get install -y apt-utils wget gcc make openssl \
		libssl-dev apache2-dev autoconf \
		libaio1 libaio-dev gzip zip make

### Build Cosign ###
RUN wget "$COSIGN_URL" \
	&& mkdir -p src/cosign \
	&& tar -xvf cosign-3.4.0.tar.gz -C src/cosign --strip-components=1 \
	&& rm cosign-3.4.0.tar.gz \
	&& cd src/cosign \
	&& autoconf \
	&& ./configure --enable-apache2=/usr/bin/apxs \
	&& make \
	&& make install \
	&& cd ../../ \
	&& rm -r src/cosign \
	&& mkdir -p /var/cosign/filter \
	&& chmod 777 /var/cosign/filter

### Remove pre-reqs ###
RUN apt-get remove -y make wget autoconf \
	&& apt-get autoremove -y

# Section that sets up Apache and Cosign to run as non-root user.
EXPOSE 8080
EXPOSE 8443

# nothing here for the time being.
COPY . /var/www/html/

### There may be an easier way to do all of this by setting APACHE_RUN_USER
### and APACHE_RUN_GROUP in env vars or /etc/apache2/envvars

RUN mkdir -p /usr/local/etc/php /usr/local/lib/php /usr/lib/oracle /etc/oracle /opt/oracle

### change directory owner, as openshift user is in root group.
RUN chown -R root:root /var/www/html /var/log/apache2 /var/lock/apache2 \
	/var/run/apache2 /usr/local/etc/php /usr/local/lib/php \
	/usr/lib/oracle /etc/oracle /opt/oracle

### Modify perms for the openshift user, who is not root, but part of root group.
RUN chmod -R g+r /var/www/html /var/cosign 
RUN chmod -R g+rw /var/log/apache2 /var/www/html /etc/apache2 \
	/etc/ssl/certs /etc/ssl/private /etc/apache2/mods-enabled /etc/apache2/sites-enabled \
	/etc/apache2/sites-available /etc/apache2/mods-available \
	/var/lib/apache2/module/enabled_by_admin /var/lib/apache2/site/enabled_by_admin \
	/var/lock/apache2 /var/run/apache2 /usr/local/etc/php \
	 /usr/local/lib/php /usr/lib/oracle /etc/oracle /opt/oracle
RUN chmod g+x /etc/ssl/private

# Oracle subdirectories
#RUN mkdir -p /usr/local/etc/php/conf.d /usr/lib/oracle /etc/oracle /opt/oracle
#RUN chmod g+rw /usr/local/etc/php/conf.d /usr/lib/oracle /etc/oracle /opt/oracle

### Start script incorporates config files and sends logs to stdout ###
COPY start.sh /usr/local/bin
RUN chmod 755 /usr/local/bin/start.sh
CMD /usr/local/bin/start.sh
