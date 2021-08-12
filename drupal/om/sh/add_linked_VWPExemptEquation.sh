#!/bin/sh

pid=$1
entity_type=$2
entity_id=$3

if [ $# -lt 3 ]; then
  echo 1>&2 "Usage: add_linked_VWPExemptEquation.sh pid entity_type entity_id [template$template]"
  exit 2
fi 

# copy the element fac_current_mgy from it's feature wd_current_mgy 
echo "drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation riverseg_frac sw_frac $entity_type "
drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation vwp_exempt_mgd sw_frac $entity_type 
