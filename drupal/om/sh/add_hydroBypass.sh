#!/bin/bash

pid=$1
template=4988636

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_hydroBypass.sh model_pid [template_entity_id]" >&2
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 


for i in Qbypass Qavail_divert Qturbine_max Qturbine unmet_demand_hydro_mgd; do
  echo "drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid $i"
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid $i
done

