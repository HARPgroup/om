#!/bin/sh

if [ $# -lt 1 ]; then
  echo 1>&2 "This is used to model a water suply facility with off-line impoundment"
  echo 1>&2 "Usage: set_local_impoundment_system_props.sh vahydro_model_pid (entity_type=model parent, default=auto) (entity_id=model parent)"
  exit 2
fi
pid=$1
entity_type='dh_feature'
if [ $# -gt 1 ]; then
  entity_type=$2
fi 
entity_id="auto"
if [ $# -gt 2 ]; then
  entity_id=$3
fi 
# Water Supply Model Element Template 
template=6528239


# make sure it is using the new discharge_mgd variable 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid available_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid local_area_sqmi
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid local_flow_cfs
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid local_impoundment
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_available_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_max_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_plus_demand
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_pump_mgd
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Send to Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Listen on Parent"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid zero
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid imp_enabled
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid wd_net_mgd

