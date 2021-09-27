#!/bin/sh
# This adds common data matrixes from a template that do not get created 
# by default with the model type plugin
pid=$1
template=4988636

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_common_waterSystemElement.sh pid [template=$template]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid custom1;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_enabled;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_local_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_nextdown_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_other_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid fac_demand_mgy; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid wd_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid discharge_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid discharge_local_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid discharge_other_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid gw_sw_factor
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid gw_demand_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid discharge_from_gw_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid historic_monthly_pct
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Send to Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Parent"

