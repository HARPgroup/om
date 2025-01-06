#!/bin/bash

pid=$1
template=6711736

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: set_rofile_props.sh model_pid [template_entity_id]" >&2
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 


for i in flow_scenario landuse_var luyear Flows; do
  echo "drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid $i"
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid $i
done

