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

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid base_demand_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid adj_demand_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid drought_response_enabled;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid drought_pct; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid flowby; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid available_mgd; 
# Deprecated - was old part of vwp templates.  Try to remove.
#drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid pump_mgd; 
