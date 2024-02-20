#!/bin/sh
# This adds common data matrixes from a template that do not get created 
# by default with the model type plugin
pid=$1
template=7423559

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_LocalTributariesBroadcasts.sh pid [template=$template]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Broadcast on Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Broadcast on Child"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Child"

