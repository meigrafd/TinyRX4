#!/bin/bash
# TinyTX Sensor.pl watchdog
#
### CONFIG - START
SCRIPT=/root/Sensor.pl
### CONFIG - END


#SCRIPT gestartet ?
PID="$(pgrep -x $(basename $SCRIPT))"
if [[ ! -z "$PID" ]] ; then
	echo "$(date +"%Y-%m-%d %H:%M")    Watchdog - $SCRIPT laeuft"
else
	screen -dmS sensor $SCRIPT
	echo "$(date +"%Y-%m-%d %H:%M")    Watchdog - $SCRIPT wurde neu gestartet"
fi

exit 0

