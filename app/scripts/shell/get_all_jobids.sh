#!/bin/bash
. /etc/profile.d/settings.sh
#echo "Retrieving cluster info";
qstat -u "*" | awk '{print $1}'
#echo "Parsing cluster info";
       