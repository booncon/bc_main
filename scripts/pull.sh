#/bin/bash

if [ ! -f /vagrant/Vagrantfile ]
then
  echo 'ERROR: this script needs to be executed in your virtual machine!'
  echo '(where your database is running)'
  exit
fi

read -p "ATTENTION! That will overwrite your database, all unpushed changes will be lost. Are you sure? [y/n] " reply
if [[ $reply =~ ^[Yy]$ ]]
then
  echo "Options:"
  echo "[0] cancel"
  echo "[2] load current snapshot"
  read -p "What shall it be? " choice

  if [ $choice = 0 ]
  then
    echo 'byebye'
    exit
  else
    mkdir -p ~/bc_main_db
    mysqldump -u root -punknown --single-transaction bc_main > ~/bc_main_db/localdump-$(date +%Y-%m-%d-%H.%M.%S).sql
  fi

  if [ $choice = 2 ]
  then
    dump_pointer=~/bc_main_db/.snapshot
    [ -f $dump_pointer ] && rm $dump_pointer
    echo 'fetching snapshot ...'
    mysqldump -u booncon -p9GqwfphnxUXLEyVP -h internal.booncon.com --single-transaction bc_pixels > $dump_pointer
    echo 'restoring snapshot ...'
    mysql -u root -punknown bc_main < $dump_pointer
    echo '... database restore complete'
  else
    echo 'invalid choice, aborting'
  fi
fi
