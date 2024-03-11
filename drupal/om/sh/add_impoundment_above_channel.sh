#!/bin/sh
# This adds slightly different plumbing for tribs with an impoundment above the channel
pid=$1
template=7405974 # Curently Ben's Branch, which is not a tamplate, but rather a live element - change soon

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_impoundment_above_channel.sh pid [template=$template]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "impoundment_drainage_area"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "impoundment_local_inflow"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Qlocal"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Qout"

