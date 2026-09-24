#!/usr/bin/bash
# This script will stop/delete all containers
# and remove all images and network.
#---------------------------------------------

export $(grep -v '^#' .env | xargs)

# Stop and remove containers
docker stop lamp-httpd lamp-mysql lamp-php-fpm
docker rm lamp-httpd lamp-mysql lamp-php-fpm

# Remove images
docker rmi "${PROJECT_NAME}-${HTTPDVERSION}"
docker rmi "${PROJECT_NAME}-${MYSQLVERSION}"
docker rmi "${PROJECT_NAME}-${PHPVERSION}"

# Remove docker network
docker network ls --filter name=lamp_network -q |xargs -r docker network rm



