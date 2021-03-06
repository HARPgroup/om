#!/bin/sh

pid=$1
entity_type=$2
entity_id=$3
# Virtual County Facility Model Template
template=5234733

if [ $# -lt 3 ]; then
  echo 1>&2 "Usage: add_linked_virtualSystemElement.sh pid entity_type entity_id [template$template]"
  exit 2
fi 

if [ $# -gt 3 ]; then
  template=$4
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid riverseg_frac;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid current_mgy;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid vwp_exempt_mgd;
# copy the element fac_current_mgy from it's feature wd_current_mgy 
echo "drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation riverseg_frac sw_frac $entity_type "
drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation riverseg_frac sw_frac $entity_type 
drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation wsp2020_2020_mgy wsp2020_2020_mgy $entity_type 
drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation wsp2020_2040_mgy wsp2020_2040_mgy $entity_type
# Copy monthly from template, NOT linked to facility
echo "drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid historic_monthly_pct "
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid historic_monthly_pct 
# Calculate the riverseg frac ... this could be tough = sum(fac:mp) in riverseg, divided by fac_current_mgy 
