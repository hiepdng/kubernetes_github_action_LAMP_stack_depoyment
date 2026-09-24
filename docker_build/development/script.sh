#!/bin/bash

### For httpd ##############################################################################
# Define ServerName and ServerAdmin for httpd-ssl.conf
SERVERNAME="www.example.com"
SERVERADMIN="you@example.com"

# Define your Docker ID and Token as variables
DHI_USER="your-docker-username"
DHI_TOKEN="your-personal-access-token"
DHI_H qTTPD_IMAGE="dhi.io/httpd:2.4.68-debian13-dev" # Replace with your required image and tag

# Authenticate to the DHI registry
echo "$DHI_TOKEN" | docker login dhi.io -u "$DHI_USER" --password-stdin

# Pull the image
docker pull "$DHI_HTTPD_IMAGE"

# Get files from the image
docker create --name my_httpd_container "$DHI_HTTPD_IMAGE"
docker cp my_httpd_container:/usr/local/apache2/conf/httpd.conf .
docker cp my_httpd_container:/usr/local/apache2/conf/extra/httpd-ssl.conf .
docker rm my_httpd_container
docker rmi "$DHI_HTTPD_IMAGE"

# Modify the httpd.conf and httpd-ssl.conf to use SSL/HTTPS
sed -i \
    -e "s/^#\(Include .*httpd-ssl.conf\)/\1/" \
    -e "s/^#\(LoadModule .*mod_ssl.so\)/\1/" \
    -e "s/^#\(LoadModule .*mod_socache_shmcb.so\)/\1/" \
    -e "s/^#ServerName www.example.com:80/ServerName ${SERVERNAME}/" \
    httpd.conf

sed -i \
    -e "s/^ServerName www.example.com:443/ServerName ${SERVERNAME}:443/" \
    -e "s/^ServerAdmin you@example.com/ServerAdmin ${SERVERADMIN}/" \
    httpd-ssl.conf

# Create a self-signed SSL Certificate for testing purposes:
# Will create server.key.secure, server.key and server.crt
openssl genrsa -des3 -passout pass:YourPasswordHere -out server.key.secure 
openssl rsa -in server.key.secure -passin pass:YourPasswordHere -out server.key # decrypted server.key, used for auto start web withour password
openssl req -new -x509 -nodes -sha1 -days 365 -key server.key -out server.crt -extensions usr_cert  -subj '/C=US/ST=state/L=city/O=example/OU=Sale-Dept/CN=httpd-server.example.com/emailAddress=your@example.com'

# Create mount directory on the host system
mkdir -p /home/app/apache2/htdocs
sudo chown -R 65532:65532 /home/app/apache2/



### For mysql ##############################################################################
## Create mount directory on the host system
mkdir -p /home/app/mysql
sudo chown -R 65532:65532 /home/app/mysql

## Create the Certificate Authority (CA): 
# Generate CA Private Key
openssl genrsa 2048 > ca-key.pem

# Generate CA Certificate
openssl req -new -x509 -nodes -days 3650 -key ca-key.pem -out ca.pem -subj '/C=US/ST=state/L=city/O=example/OU=Sale-Dept/CN=server.example.com/emailAddress=your@example.com'


### Create the Server Certificate:
# Generate Server Private Key
openssl req -newkey rsa:2048 -nodes -days 3650 -keyout server-key.pem -out server-req.pem -subj '/C=US/ST=state/L=city/O=example/OU=Sale-Dept/CN=server.example.com/emailAddress=your@example.com'

# Sign the Server Certificate with the CA
openssl x509 -req -days 3650 -in server-req.pem -CA ca.pem -CAkey ca-key.pem -set_serial 01 -out server-cert.pem


### Create the Client Certificate:
# Generate Client Private Key
openssl req -newkey rsa:2048 -days 3650 -nodes -keyout client-key.pem -out client-req.pem -subj '/C=US/ST=state/L=city/O=example/OU=Sale-Dept/CN=client.example.com/emailAddress=your@example.com'

# Sign the Client Certificate with the CA
openssl x509 -req -days 3650 -in client-req.pem -CA ca.pem -CAkey ca-key.pem -set_serial 01 -out client-cert.pem



### For php-fpm ##############################################################################

DHI_PHP_IMAGE="dhi.io/php:8.5.8-debian13-dev" # Replace with your required image and tag

# Pull the image
docker pull "$DHI_PHP_IMAGE"

# Get files from the image
docker create --name my_php_container "$DHI_PHP_IMAGE"
docker cp my_php_container:/usr/local/etc/php/php.ini .
docker rm my_php_container
docker rmi "$DHI_PHP_IMAGE"


# Modify the php.ini
sed -i \
    -e "s/^\(max_execution_time.*\)/;\1\nmax_execution_time = 60/" \
    -e "s/^\(max_input_time.*\)/;\1\nmax_input_time = 60/" \
    -e "s/^\(;max_input_vars.*\)/;\1\nmax_input_vars = 3000/" \
    -e "s/^\(memory_limit.*\)/;\1\nmemory_limit = 256M/" \
    -e "s/^\(post_max_size.*\)/;\1\npost_max_size = 64M/" \
    -e "s/^\(upload_max_filesize.*\)/;\1\nupload_max_filesize = 64M/" \
    -e "s/^\(display_errors.*\)/;\1\ndisplay_errors = Off/" \
    -e "s/^\(display_startup_errors.*\)/;\1\ndisplay_startup_errors = Off/" \
    -e "s/^\(log_errors.*\)/;\1\nlog_error = On/" \
    -e "s/^\(error_reporting.*\)/;\1\nerror_reporting = E_ALL \\& ~E_DEPRECATED \\& ~E_STRICT/" \
    -e "s/^\(expose_php.*\)/;\1\nexpose_php = Off/" \
    -e "s/^\(session.use_only_cookies.*\)/;\1\nsession.use_only_cookies = 1/" \
    -e "s/^\(session.cookie_httponly.*\)/;\1\nsession.cookie_httponly = 1/" \
    -e "s/^;\(session.cookie_secure.*\)/;\1\nsession.cookie_secure = 1/" \
    -e "s/^\(session.use_strict_mode.*\)/;\1\nsession.use_strict_mode = 1/" \
    -e "s/^\(session.cookie_samesite.*\)/;\1\nsession.cookie_samesite = "Lax"/" \
    -e "s/^\(allow_url_fopen.*\)/;\1\nallow_url_fopen = Off/" \
    -e "s/^\(allow_url_include.*\)/;\1\nallow_url_include = Off/" \
    -e "s/^\(disable_functions.*\)/;\1\ndisable_functions = exec,shell_exec,passthru,system,popen,proc_open/" \
    -e "s/^;\(opcache.enable=.*\)/;\1\nopcache.enable = 1/" \
    -e "s/^;\(opcache.enable_cli.*\)/;\1\nopcache.enable_cli = 1/" \
    -e "s/^;\(opcache.memory_consumption.*\)/;\1\nopcache.memory_consumption = 128/" \
    -e "s/^;\(opcache.interned_strings_buffer.*\)/;\1\nopcache.interned_strings_buffer = 8/" \
    -e "s/^;\(opcache.max_accelerated_files.*\)/;\1\nopcache.max_accelerated_files = 10000/" \
    -e "s/^;\(opcache.validate_timestamps.*\)/;\1\nopcache.validate_timestamps = 0/" \
    php.ini


# Create mount directory on the host system
mkdir -p /home/app/php
sudo chown -R 65532:65532 /home/app/php









