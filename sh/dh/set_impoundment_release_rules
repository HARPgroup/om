#!/bin/sh

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: set_impoundment_release_rules.sh vahydro_model_pid (entity_type=model parent, default=auto) (entity_id=model parent)"
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
template=4988636


# Set destination model to not write
drush scr modules/om/src/om_setprop.php cmd dh_properties $pid om_element_connection om_element_connection $elementid 0

# make sure it is using the new discharge_mgd variable 
for i in release release_current release_historic release_proposed refill_transfer_available_mgd refill_transfer_max_mgd; do
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid $i
done

# Restore destination write setting and save
drush scr modules/om/src/om_setprop.php cmd dh_properties $pid om_element_connection om_element_connection $elementid 1

print "Finished. Note, if you wish to control a remote impoundment, you must also run set_impoundment_release_sender.sh"
