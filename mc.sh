#!/bin/bash
# /etc/init.d/minecraft
# version 0.3.2 2011-01-27 (YYYY-MM-DD)

  ### BEGIN INIT INFO
  # Provides:   minecraft
  # Required-Start: $local_fs $remote_fs
  # Required-Stop:  $local_fs $remote_fs
  # Should-Start:   $network
  # Should-Stop:    $network
  # Default-Start:  2 3 4 5
  # Default-Stop:   0 1 6
  # Short-Description:    Minecraft server
  # Description:    Starts the minecraft server
  ### END INIT INFO

#Settings
SERVICE='minecraft_server.jar'
USERNAME="minecraft"
MCPATH="/home/minecraft/minecraft_$2"
INVOCATION='java -Xmx512M -Xms512M -jar minecraft_server.jar nogui'

ME=`whoami`
as_user() {
  if [ "$ME" == "$USERNAME" ] ; then
    bash -c "$1"
  else
    su - $USERNAME -c "$1"
  fi
}

mc_start() {
  if ps ax | grep -v grep | grep -v -i SCREEN | grep $SERVICE > /dev/null
  then
    echo "Tried to start but $SERVICE was already running!"
  else
    echo "$SERVICE was not running... starting."
    cd $MCPATH
    as_user "cd $MCPATH && screen -dmS minecraft $INVOCATION"
    sleep 7
    if ps ax | grep -v grep | grep -v -i SCREEN | grep $SERVICE > /dev/null
    then
      echo "$SERVICE is now running."
    else
      echo "Could not start $SERVICE."
    fi
  fi
}

mc_stop() {
        if ps ax | grep -v grep | grep -v -i SCREEN | grep $SERVICE > /dev/null
        then
                echo "$SERVICE is running... stopping."
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"say SERVER SHUTTING DOWN IN 5 SECONDS. Saving map...\"\015'"
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"save-all\"\015'"
                sleep 5
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"stop\"\015'"
                sleep 7
        else
                echo "$SERVICE was not running."
        fi
        if ps ax | grep -v grep | grep -v -i SCREEN | grep $SERVICE > /dev/null
        then
                echo "$SERVICE could not be shut down... still running."
        else
                echo "$SERVICE is shut down."
        fi
}

mc_backup() {
        if ps ax | grep -v grep | grep -v -i SCREEN | grep $SERVICE > /dev/null
        then
                YEAR=`date +%Y`
                MONTH=`date +%m`
                DAY=`date +%d`
                HOUR=`date +%H`
                MINUTE=`date +%M`
                SECONDS=`date +%S`
                FOLDERNAME="$YEAR-$MONTH-$DAY-$HOUR.$MINUTE.$SECONDS-world.back"
                TARBALL="$FOLDERNAME.tar"
                echo "$SERVICE is about to being backuped..."
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"say SERVER BACKING UP IN 5 SECONDS.\"\015'"
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"save-all\"\015'"
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"save-off\"\015'"
                sleep 5
                as_user "cd $MCPATH && cp -R world/ backups/$FOLDERNAME && tar -vcf  backups/$TARBALL backups/$FOLDERNAME && rm -rf backups/$FOLDERNAME"
                as_user "screen -p 0 -S minecraft -X eval 'stuff \"save-on\"\015'"
                as_user "echo $TARBALL"
        else
                echo "$SERVICE was not running."
        fi
        if test -f "$MCPATH/backups/$TARBALL";
        then
                echo "The world is now backuped."
        else
                echo "An error occured while saving the word."
        fi
}

#Start-Stop here
case "$1" in
  start)
    mc_start
    ;;
  stop)
    mc_stop
    ;;
  backup)
    mc_backup
    ;;
  restart)
    mc_stop
    mc_start
    ;;
  status)
    if ps ax | grep -v grep | grep -v -i SCREEN | grep $SERVICE > /dev/null
    then
      echo "$SERVICE is running."
    else
      echo "$SERVICE is not running."
    fi
    ;;

  *)
  echo "Usage: /etc/init.d/minecraft {start|stop|status|restart}"
  exit 1
  ;;
esac

exit 0