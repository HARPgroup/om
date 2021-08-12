#!/bin/sh
# This adds common data matrixes from a template that do not get created 
# by default with the model type plugin
pid=$1
template=6541489

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_waterTribBroadcasts.sh pid [template=$template]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Broadcast Mainstem"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Broadcast to Child"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Broadcast to Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen to Children"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Local Runoff"

