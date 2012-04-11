#!/bin/bash

USER_NAME='bugstats'
SRC_PATH="$PWD/src"
CRON_FILE="/etc/cron.d/fetch_bugzilla_statistics"
NETRC_FILE="/home/$USER_NAME/.netrc"
BIN_PATH="/usr/local"
ETC_PATH="/usr/local"
WWW_PATH="/var/www/BAM"
LOG_PATH="/var/log/BAM"

# Installing or updating dialog
apt-get install dialog

echo "Creating $USER_NAME user for fetching data..."

if [ "`cut -d ":" -f 1 /etc/passwd | grep "$USER_NAME"`" != "" ];
then
    echo "User $USER_NAME already exists."
    exit 1
else
    useradd -s /bin/bash -m -U $USER_NAME
    echo -e "admin\nadmin" | passwd $USER_NAME
fi


echo "Copying files..."

cp $SRC_PATH/etc/cron.d/fetch_bugzilla_statistics $CRON_FILE
sed "s/^HOME=.*$/HOME=\/home\/$USER_NAME/" -i $CRON_FILE
echo "0  5  * * 1-5  $USER_NAME  fetch_statistics.sh"  >> $CRON_FILE

cp $SRC_PATH/home/bugstats/.netrc $NETRC_FILE

echo -n "machine " > $NETRC_FILE
dialog --title "Bugzilla details" --inputbox "Bugzilla host" 10 50 2>> $NETRC_FILE
if [ "$(echo $?)" != 0 ];
then
    rm $NETRC_FILE
else
    echo -n " login " >> $NETRC_FILE
    dialog --title "Bugzilla details" --inputbox "Login" 10 50 2>> $NETRC_FILE
    if [ "$(echo $?)" != 0 ];
    then
        rm $NETRC_FILE
    else
        echo -n " password " >> $NETRC_FILE
        dialog --title "Bugzilla details" --inputbox "Password" 10 50 2>> $NETRC_FILE
        if [ "$(echo $?)" != 0 ];
        then
            rm $NETRC_FILE
        else
            chmod 600 $NETRC_FILE
            chown $USER_NAME:$USER_NAME $NETRC_FILE
        fi
    fi
fi

cp -R $SRC_PATH/usr/local/bin/ $BIN_PATH                                        
cp -R $SRC_PATH/usr/local/etc/ $BIN_PATH                                        

cp -R $SRC_PATH/var/www/ $WWW_PATH                                              
sed "s#.DATA_FOLDER =.*#\$DATA_FOLDER = \"$WWW_PATH/data\";#" -i $WWW_PATH/lib/static_variables.php                                              
                                                                                
mkdir $WWW_PATH/data/                                                           
chown $USER_NAME:$USER_NAME $WWW_PATH/data/                                     
                                                                                
mkdir $LOG_PATH                                                                 
touch $LOG_PATH/info.log                                                        
chown -hR $USER_NAME:$USER_NAME $LOG_PATH                                       
                                                                                
echo "Copying finished."                 

# Metrics Manager
dialog --yesno "Do you also want to install Metrics Manager?" 6 30

result=$?

if [ "$result" = "0" ]
then
	tmp_file="/tmp/input.$$"
	dialog --title "Use up/down arrows to navigate and space button to copy location" --dselect $HOME 10 50 2>$tmp_file
	MANAGER_SRC=`cat $tmp_file`
	rm -f $tmp_file
	
	echo "Copying files from $MANAGER_SRC to $WWW_PATH/Manager/..."
	cp -R $MANAGER_SRC $WWW_PATH/Manager/ 
	
# Setting proper access rights
#Set 755 owner: <your_user> for all files first
	chmod -R 755 $WWW_PATH/Manager/ 
	chown -R $USER_NAME $WWW_PATH/Manager/ 
#tmp/ 775, owner: www-data
	chmod 775 $WWW_PATH/Manager/tmp
	chown -R www-data $WWW_PATH/Manager/tmp
#lib/user-settings.php 666, owner: www-data
	chmod 666 $WWW_PATH/Manager/lib/user_settings.php
	chown -R www-data $WWW_PATH/Manager/lib/user_settings.php
#users/ 733
	chmod 733 $WWW_PATH/Manager/users/
#users/admin.cl50cp1eoq9zj3scotij1a84 644, owner: www-data
	chmod 644 $WWW_PATH/Manager/users/admin.cl50cp1eoq9zj3scotij1a84
	chown -R www-data $WWW_PATH/Manager/users/admin.cl50cp1eoq9zj3scotij1a84
#log/syslog 666
	chmod 666 $WWW_PATH/Manager/log/syslog
	chown -R www-data $WWW_PATH/Manager/log/syslog

# Adding line to /etc/sudoers
echo "www-data ALL=(ALL) NOPASSWD: $WWW_PATH/Manager/lib/libcontentaction.pl">>/etc/sudoers
	clear
	echo "Metrics and Metrics Manager have been installed."
else
	clear
	echo "Metrics has been installed."
fi
