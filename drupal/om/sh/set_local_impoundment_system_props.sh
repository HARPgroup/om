#!/bin/sh

if [ $# -lt 1 ]; then
  echo 1>&2 "This is used to model a water suply facility with off-line impoundment"
  echo 1>&2 "Usage: set_local_impoundment_system_props.sh vahydro_model_pid  [template] [entity_type=model parent, default=dh_feature] [entity_id=model parent id]"
  exit 2
fi
pid=$1
# Water Supply Model Element Template 
template=6717035
if [ $# -gt 1 ]; then
  template=$2
fi 
entity_type='dh_feature'
if [ $# -gt 2 ]; then
  entity_type=$3
fi 
entity_id="auto"
if [ $# -gt 3 ]; then
  entity_id=$4
fi 


# make sure it is using the new discharge_mgd variable 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid available_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid local_area_sqmi
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid local_flow_cfs
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid local_impoundment
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_available_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_max_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_historic_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_current_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_proposed_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_plus_demand
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_pump_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Send to Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid zero
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid imp_enabled
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid wd_net_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid release
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid release_current
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid release_historic
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid release_proposed
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "adj_demand_mgd_local_rsvr|adj_demand_mgd"

