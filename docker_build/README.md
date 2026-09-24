
## <div align="center">$${\color{blue}Secure \space LAMP \space project \space deployment \space using \space Docker \space Hardened \space Images}$$
## <div align="center"> $${\color{blue}and \space automated \space GitHub \space Actions \space workflow}$$


### Introduction:
- The LAMP stack project uses Docker Compose and hardened Docker images for high security. For testing, I enabled both unencrypted (port 80) and encrypted (port 443) traffic. To secure the web server, HTTPS must be enforced alongside other security configurations.
- Automated GitHub Actions:
  - Deploy LAMP on self-hosted runner (local machine)
  - Deploy LAMP on a remover server via SSH
  - Clean up docker containers/images on self-hosted runner
  - Clean up docker containers/images on a remote server via SSH

For demonstration, the following docker images are used: 
  - Apache image: [dhi.io/httpd:2.4.68-debian13](https://hub.docker.com/hardened-images/catalog/dhi/httpd)  
  - MySQL image: [dhi.io/mysql:lts-debian13](https://hub.docker.com/hardened-images/catalog/dhi/mysql)  
  - PHP image: [dhi.io/php:8.5.8-debian13-fpm](https://hub.docker.com/hardened-images/catalog/dhi/php)

<br/>

Files provided:  
```
├── certs/  
│   ├── httpd/  
│   └── mysql/
├── cleanup.sh  
├── docker-compose.yml
├── .env  
├── etc/  
│   ├── httpd.conf  
│   ├── httpd-ssl.conf  
│   ├── my.cnf  
│   └── php.ini  
├── htdocs/
│   ├── database.php  
│   ├── index.php  
│   ├── module_list.php  
│   ├── upload.php  
│   └── uploads/  
├── log/  
│   └── httpd/  
├── mysql_data/  
├── setup.sh  
└── versions/  
    ├── httpd:2.4.68-debian13/  
    │   └── Dockerfile  
    ├── mysql:lts-debian13/  
    │   └── Dockerfile  
    └── php:8.5.8-debian13-fpm/  
        └── Dockerfile
```
- **.env**: Environment file
- **cleanup.sh**: a script to delete all project containers and images
- **setup.sh**:
   - Generate SSL/TLS self-signed certificates for **httpd** server
   - Modify httpd.conf and httpd-ssl.conf to work with SSL certificates 
   - Generate SSL/TLS Certificates for **mysql** server
   - Modify php.ini for **php-fpm** server  
- **docker-compose.yml**:
   - Used to create **httpd**, **mysql** and **php-fpm** containers.
   - Modify it if neccessary.
- **./etc/***:  
   - All configuration files - **httpd.conf**, **httpd-ssl.conf**, **my.cnf**, and **php.ini** - are bound to the host system as bind mounts.
   - Modify them if necessary.  
- **./htdocs**: Document root mounted directory
   - **index.php**: An example **index.php** web page
   - **database.php**: An example of database upload/insert/query web page
   - **module_list.php**: List of all installed php modules page
   - **upload.php**: An example of file upload web page
- **./mysql_data/**  
   - A directory containing mysql data  

<br/>

### <ins>Method 1:</ins> Deploy LAMP Stack using CLI
- **Download the repository**
```
git clone https://github.com/hiepdng/docker_build_DHI_LAMP_Project.git
```
- **Login to dockerhub in your terminal**
```
docker login dhi.io
```
- **Configure/setup environment**
  - This will set up directories, create certificates and modify configuration files 
```
cd docker_build_DHI_LAMP_Project
sh setup.sh
```
- **Build httpd, mysql, php-fpm images and Running httpd, mysql, php-fpm containers**
```
docker compose build --no-cache
docker compose up -d
```

### <ins>Method 2:</ins> Deploy LAMP Stack using GitHub Actions
- Set up your DOCKERHUB_USERNAME and DOCKERHUB_TOKEN secrets in GitHub Actions.
- You can deploy LAMP Stack either on Local Machine or on Remote Host. Both  are triggered by workflow_dispatch
  - If you deploy the LAMP Stack on a Remote Host, setup SERVER_HOST, SERVER_USER and SSH_PRIVATE_KEY secrets in GitHub Actions.
  - If you deploy the LAMP stack on local machine (self-hosted runner), you need to register a Local Self-Hosted Runner:
    - In your GitHub repo, go to Settings > Actions > Runners > New self-hosted runner.
    - Download and configure the runner package on your local machine, then start it (./run.sh).
- On GitHub Action, choose an action, then click "Run workflow" to deploy the LAMP Stack

<br/><br/>

### Checking:
To visit your page, to to https://localhost/index.php

<br/>

### Cleaup your LAMP Project:
```
sh cleanup.sh
```
<br/>

### Webpage Samples:
<img width="800" height="399" alt="1" src="https://github.com/user-attachments/assets/ef5d540b-7ab8-4e0c-81b6-e8d6c0dee77c" />

<img width="746" height="849" alt="2" src="https://github.com/user-attachments/assets/6908515f-063d-49b0-9754-3b6bc75e725e" />

<br/>

### Some commands for maintenance:  
```
docker compose restart
docker network ls
docker compose exec httpd sh
docker compose exec mysqld sh
```

<br/>

### Basic docker commands:
```
$ docker pull <image_name>       – Pulls an image from dockerhub
$ docker image ls                – Lists all locally stored Docker images on your host system
$ docker run -d <image_name>     – Creates & starts a new Docker container from animage and runs it in the background
  docker run -it -d --name image_name <image_name>
$ docker ps                      – Lists all currently running Docker container IDs on your system
$ docker ps -a                   – lists all Docker container IDs on your system, regardless of their current status. 
$ docker stop <containerID>      – Gracefully shuts down a running Docker container
$ docker start <containerID>     – Resumes and boots up stopped Docker container
$ docker rm <containerID>        – Remove Docker container

$ docker exec -it <containerID> bash – Opens an interactive command-line terminal (Bash) inside a Docker
                                       container that is already running.
```

<br/>

