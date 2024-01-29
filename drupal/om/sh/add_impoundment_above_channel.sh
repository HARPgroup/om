#!/bin/sh
# This adds common data matrixes from a template that do not get created 
# by default with the model type plugin
pid=$1
template=7407892

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

