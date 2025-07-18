#!/bin/sh
# Water Supply Model Element Template 
template=6717035

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: set_impoundment_refill_sender vahydro_model_pid [template=default($template)] [overwrite=0] [debug=0]"
  exit 2
fi
pid=$1
if [ $# -gt 1 ]; then
  if [ "$2" != "default" ]; then
    template=$2
  fi
fi  
overwrite=0
if [ $# -gt 2 ]; then
  overwrite=$3
fi 
debug=0
if [ $# -gt 3 ]; then
  debug=$4
fi 

# make sure it is using the new discharge_mgd variable 
for i in refill_current refill_historic refill_proposed refill_max_mgd refill_available_mgd refill_plus_demand refill_pump_mgd; do
  echo "om_copy_property dh_properties $template dh_properties $pid $i 1 $overwrite"
  om_copy_property dh_properties $template dh_properties $pid $i 1 $overwrite $debug
done

echo "Finished. Note, other relevant properties may be copied in set_impoundment_release_rules"