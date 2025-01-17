#!/bin/sh

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: set_reservoir_system_props.sh vahydro_model_pid (entity_type=model parent, default=auto) (entity_id=model parent)"
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
template=7423249


# make sure it is using the new discharge_mgd variable 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Reservoir Operations"
# delete the property refill_max_mgd 
# since it is included in the Reservoir Operations listener
# and add refill_allowed_mgd which is set to listen to refill_max_mgd 
echo "Deleting old refill_max_mgd (it it exists) since it is included in the Reservoir Operations listener"
drush scr modules/om/src/om_deleteprop.php cmd dh_properties $pid refill_max_mgd
echo "Add refill_allowed_mgd which is set to listen to refill_max_mgd from broadcast"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid refill_allowed_mgd
echo "Copt available_mgd which is set to use refill_flowby from broadcast"
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid available_mgd

echo "Properties have been copied.  Note: if this is a small trib with impoundment, you must edit the impoundment sub-component and set release = release_cfs (default is zero)"
