if [ ! -f /etc/hostname ] || [ $(cat /etc/hostname) != 'bc-prod' ]
then
  echo 'ERROR: this script needs to be executed on the production server (bc-prod)!'
  exit
fi

read -p "ATTENTION! That will irreverisbly overwrite the database with the staging version. Are you sure? [y/n] " reply
if [[ $reply =~ ^[Yy]$ ]]
then
  USERNAME=`grep user /etc/mysql/debian.cnf | tail -n 1 | cut -d"=" -f2 | awk '{print $1}'`
  PASSWORD=`grep password /etc/mysql/debian.cnf | tail -n 1 | cut -d"=" -f2 | awk '{print $1}'`

  echo "backing up db"
  mkdir -p ~/bc_main_db
  mysqldump -u $USERNAME -p$PASSWORD --single-transaction bc_main > ~/bc_main_db/localdump-$(date +%Y-%m-%d-%H.%M.%S).sql  

  echo "fetching db snapshot..."
  dump_pointer=~/bc_main_db/.staging_snapshot
  [ -f $dump_pointer ] && rm $dump_pointer
  mysqldump -u booncon -p9GqwfphnxUXLEyVP -h bc-dev.booncon.com --single-transaction bc_main --ignore-table=bc_main.modx_context_setting > $dump_pointer

  echo "restoring snapshot..."
  mysql -u $USERNAME -p$PASSWORD bc_main < $dump_pointer

  echo "emptying cache..."
  rm -rf /var/www/bc_main/core/cache/*

  echo "pulling new code..."
  git pull origin master
 
  echo "all done here!"
fi
