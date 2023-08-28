#!/bin/sh

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: set_impoundment_release_sender.sh vahydro_model_pid (entity_type=model parent, default=auto) (entity_id=model parent)"
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


# make sure it is using the new discharge_mgd variable 
for i in release release_current release_historic release_proposed; do
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid $i
}

print "Finished. Note, if you wish to control a remote impoundment, you must also run set_impoundment_release_sender.sh"
