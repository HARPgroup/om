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
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid base_demand_pstatus_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid Qintake;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid unaccounted_losses;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid unmet_demand_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid adj_demand_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid drought_response_enabled;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid drought_pct; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid permit_status; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid flowby; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid flowby_current; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid flowby_proposed; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid flowby_historic; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid available_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_base_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_demand_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_prop_max_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_prop_max_mgy
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_prop_base_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_prop_demand_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_max_mgy
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_max_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_base_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_demand_mgd


# Deprecated - was old part of vwp templates.  Try to remove.
#drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid pump_mgd; 
