FROM drupal:7-apache

#MAINTAINER: 

#### Cosign Pre-requisites ###
WORKDIR /usr/lib/apache2/modules

ENV COSIGN_URL https://github.com/umich-iam/cosign/archive/cosign-3.4.0.tar.gz
ENV CPPFLAGS="-I/usr/kerberos/include"
ENV OPENSSL_VERSION 1.0.1t-1+deb8u7
ENV APACHE2=/usr/sbin/apache2

# install PHP and Apache2 here
RUN apt-get update \
	&& apt-get install -y wget gcc make openssl \
		libssl-dev=$OPENSSL_VERSION apache2-dev autoconf

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

#WORKDIR /etc/apache2

### Remove pre-reqs ###
RUN apt-get remove -y make wget autoconf \
	&& apt-get autoremove -y

# Section that setups up Apache and Cosign to run as non-root user.
EXPOSE 8080
EXPOSE 8443

### There may be an easier way to do all of this by setting APACHE_RUN_USER
### and APACHE_RUN_GROUP in env vars or /etc/apache2/envvars

### Specify group www-data for Apache files, chmod them to 775
RUN chown -R root:www-data /var/www/html/sites /var/log/apache2 /var/lock/apache2 \
	/var/run/apache2

### Modify perms for the openshift user, who is not root, but part of root group.
RUN chmod -R 775 /var/www/html /var/cosign 
RUN chmod -R 775 /var/log/apache2 /var/www/html/sites/default /etc/apache2 \
	/etc/ssl/certs/ /etc/apache2/mods-enabled /etc/apache2/sites-enabled \
	/etc/apache2/sites-available /etc/apache2/mods-available \
	/var/lib/apache2/module/enabled_by_admin /var/lib/apache2/site/enabled_by_admin \
	/var/lock/apache2 /var/run/apache2

# nothing here for the time being.
#COPY . /var/www/html/

### Start script incorporates config files and sends logs to stdout ###
COPY start.sh /usr/local/bin
RUN chown root:www-data /usr/local/bin/start.sh
RUN chmod 775 /usr/local/bin/start.sh
CMD /usr/local/bin/start.sh


### Schleif adds - delete everything under here
# Redirect logs to stdout and stderr for docker reasons.
#RUN ln -sf /dev/stdout /var/log/apache2/access_log
#RUN ln -sf /dev/stderr /var/log/apache2/error_log

# apache and virtual host secrets
#RUN ln -sf /secrets/apache2/apache2.conf /etc/apache2/apache2.conf
#RUN ln -sf /secrets/apache2/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf
#RUN ln -sf /secrets/apache2/cosign.conf /etc/apache2/mods-available/cosign.conf

# SSL secrets
#RUN ln -sf /secrets/ssl/USERTrustRSACertificationAuthority.pem /etc/ssl/certs/USERTrustRSACertificationAuthority.pem
#RUN ln -sf /secrets/ssl/AddTrustExternalCARoot.pem /etc/ssl/certs/AddTrustExternalCARoot.pem
#RUN ln -sf /secrets/ssl/sha384-Intermediate-cert.pem /etc/ssl/certs/sha384-Intermediate-cert.pem

#SSLCertificateFile: file '/etc/ssl/certs/its-metrics.openshift.it.umich.edu.cert' does not exist or is empty
#RUN ln -sf /secrets/ssl/its-metrics.openshift.it.umich.edu.cert /etc/ssl/certs/its-metrics.openshift.it.umich.edu.cert

#if [ -f /secrets/app/local.start.sh ]
#then
#RUN /bin/sh /secrets/app/local.start.sh
#fi

## Rehash command needs to be run before starting apache.
#RUN c_rehash /etc/ssl/certs

#RUN a2enmod ssl
#RUN a2enmod include
#RUN a2ensite default-ssl 

## set SGID for www-data 
RUN chown -R www-data.www-data /var/www/html /var/cosign
RUN chmod -R 2775 /var/www/html /var/cosign

#RUN cd /var/www/html
#drush @sites cc all --yes
#RUN drush up --no-backup --yes

#RUN /usr/local/bin/apache2-foreground
