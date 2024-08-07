#!/bin/sh
# This adds a set of broadcasts to the river model to take local_impoundment variables from child facility models to send to parent model from 1. Withdraws and Discharges
pid=$1
template=6974282 #Template for Withdrawals & Discharges  for river models

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_local_impoundment_listenRules.sh pid [template=$template]"
  exit 2
fi

if [ $# -gt 1 ]; then
  template=$2
fi

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Child"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Broadcast to Parent"

