#!/bin/bash

entity_type='dh_feature'
model_version='vahydro-1.0'
entity_id=$1
# Water Supply Model Element Template 
template=4988636

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: facility_add_common_waterSystemElement.sh entity_id [model_version=vahydro-1.0] [entity_type=dh_feature]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  model_version=$2
fi 
if [ $# -gt 2 ]; then
  entity_type=$3
fi 


frac_query="
select pid from dh_properties 
where featureid in ($entity_id)
and entity_type = '$entity_type'
and propcode = '$model_version';
"
echo $frac_query 
echo $frac_query | PGOPTIONS='--client-min-messages=warning' psql -h dbase1 drupal.dh03 > /tmp/wsp_facility_models.txt 

n=`< /tmp/wsp_facility_models.txt wc -l`
nm="$((n - 2))"
head -n $nm /tmp/wsp_facility_models.txt > /tmp/fhead.txt 
n=`< /tmp/fhead.txt wc -l`
nm="$((n - 2))"
tail -n $nm /tmp/fhead.txt > /tmp/wsp_facility_models.txt 

while IFS= read -r line; do
  #echo "Text read from file: $line"
  IFS="$IFS|" read pid <<< "$line"
  # copy the element fac_current_mgy from it's feature wd_current_mgy 
  drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation fac_current_mgy wd_current_mgy $entity_type 
  drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation wsp2020_2020_mgy wsp2020_2020_mgy $entity_type 
  drush scr modules/om/src/om.model.wsp.props.php cmd $pid $entity_id om_class_Equation wsp2020_2040_mgy wsp2020_2040_mgy $entity_type 
  monpid=`drush scr modules/om/src/om_getpid.php $entity_type $entity_id "wd_current_mon_factors"`
  drush scr modules/om/src/om.model.historic_monthly_pct.php cmd $pid $monpid om_class_DataMatrix historic_monthly_pct field_dh_matrix dh_properties 
  # Calculate the riverseg frac ... this could be tough = sum(fac:mp) in riverseg, divided by fac_current_mgy 

done < /tmp/wsp_facility_models.txt 

rm /tmp/fhead.txt 
rm /tmp/wsp_facility_models.txt
