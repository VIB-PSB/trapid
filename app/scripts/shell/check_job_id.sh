#!/bin/bash
. /etc/profile.d/settings.sh
qstat -j $1 | awk '{ print $1 "\t" $2}'
