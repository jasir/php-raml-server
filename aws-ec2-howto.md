
#AWS

####EC2 Instance 
Spawn an EC2 Instance, create a .pem key if you haven't already
https://us-west-2.console.aws.amazon.com/ec2/v2/home?region=us-west-2#

####Server settings:
 - Amazon Linux AMI 2015.03 (HVM), SSD Volume Type
 - t2.micro
 - subnet: usa-west-2x
 - Auto-assign Public IP: `enable`


#Local

```bash
# Make .pem readonly
chmod 400 ~/aws-lamp-server.pem
```

```bash
# Log into box using .pem
ssh -v -i "/Users/rsatsangi/aws-lamp-server.pem" ec2-user@54.148.30.160
```

####Sublime Configs
Some sublime configuration for SFTP setup. You can obviously have other configs but this is related to SFTP.
```
{
    
    "type": "sftp",
    "host": "54.148.30.160",
    "user": "ec2-user",
    
    "remote_path": "/var/www/html",
    "ignore_regexes": [
        "\\.sublime-(project|workspace)", "sftp-config(-alt\\d?)?\\.json",
        "sftp-settings\\.json", "/venv/", "\\.svn/", "\\.hg/", "\\.git/",
        "\\.bzr", "_darcs", "CVS", "\\.DS_Store", "Thumbs\\.db", "desktop\\.ini", "vendor"
    ],
    
    "connect_timeout": 30,
    "sftp_flags": ["-i", "/Users/rsatsangi/aws-lamp-server.pem"],
}
```

#Remote

####Yum
```bash
# Yum update
sudo yum update
```

####Git+bash

```bash
# first install git
sudo yum install git
# get some handy git tools
wget https://raw.githubusercontent.com/git/git/master/contrib/completion/git-completion.bash
mv git-completion.bash .git-completion.bash
wget https://raw.githubusercontent.com/git/git/master/contrib/completion/git-prompt.sh
mv git-prompt.sh .git-prompt.sh
```

####Bash
```bash
#truncate existing .bash_profile
truncate ~/.bash_profile --size 0
#edit that bad boy
sudo vim ~/.bash_profile 
#paste this son of a gun in there: https://gist.github.com/dethbird/e958b5926353c9b9269f
source ~/.bash_profile
```

##LAMP setup
####Official Docs on that:
https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/install-LAMP.html

####Httpd, PHP, MySQL

```bash
#yum install all the things
sudo yum install -y httpd24 php56 mysql55-server php56-mysqlnd
#start the httpd service
sudo service httpd start
```

Browse http://54.148.30.160

##Node, Redis
```bash
#you need the epel repo
sudo yum install nodejs npm redis --enablerepo=epel
```

##/var/www/html permissions

```bash
#Add ec2-user to www group
sudo groupadd www
sudo usermod -a -G www ec2-user

#web file ownership
sudo chown -R root:www /var/www
sudo chmod 2775 /var/www
find /var/www -type d -exec sudo chmod 2775 {} +
find /var/www -type f -exec sudo chmod 0664 {} +
```
##mysql
```bash
sudo service mysqld start
#this will take you through a security related quick-start
sudo mysql_secure_installation
```
##composer
```bash
cd ~/
mkdir bin
cd bin
curl -sS https://getcomposer.org/installer | php
```

#Git
Setup your Git key
```bash
#generate key https://help.github.com/articles/generating-ssh-keys/
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"
# start the ssh-agent in the background
eval "$(ssh-agent -s)"
# add your SSH key to the ssh-agent:
ssh-add ~/.ssh/id_rsa
# cat the thing so you can paste it into git at https://github.com/settings/ssh
cat ~/.ssh/id_rsa.pub
```

#DocRoot
You usually want to able to change the DocRoot

```bash
# mind the DocRoot, Directory, Allow Overrides ALL etc 
# example: https://gist.github.com/dethbird/aa9d729f2bf9125bf94e
sudo vim /etc/httpd/conf/httpd.conf
```