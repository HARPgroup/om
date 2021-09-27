#!/bin/sh
# This adds common data matrixes from a template that do not get created 
# by default with the model type plugin
pid=$1
template=6541481

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_common_waterSupplyModelNode.sh pid [template=$template]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_cumulative_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid riverseg;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid wd_cumulative_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid imp_off;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid Qout;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid custom1

# not yet.  these may vary from normal to subnodal varieties
#drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Send to Parent"
#drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Send to Children"
#drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Parent"
#drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen to Children"
#
