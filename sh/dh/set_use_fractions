#!/bin/bash

if [ "$1" == "--help" ]; then
  echo 1>&2 "Usage: set_use_fractions.sh [facility ydroid] "
  exit 2
fi 

frac_query="
  select 'dh_feature' as entity_type, mp.hydroid as featureid,
    'facility_use_fraction' as varkey, 'facility_use_fraction' as propname,
    CASE 
      WHEN mpvar.propvalue is NULL then 0.0 
      WHEN facvar.propvalue > 0.0 THEN mpvar.propvalue / facvar.propvalue
      ELSE 0.0
    END as propvalue 
  from dh_feature as mp 
  left outer join field_data_dh_link_facility_mps as link
  on (
    link.entity_id = mp.hydroid
  )
  left outer join dh_feature as fac
  on (
    link.dh_link_facility_mps_target_id = fac.hydroid
  )
  left outer join dh_properties as mpvar
  on (
    mpvar.entity_type = 'dh_feature'
    and mpvar.featureid = mp.hydroid
    and mpvar.propname = 'wd_current_mgy'
  )
  left outer join dh_properties as facvar
  on (
    facvar.entity_type = 'dh_feature'
    and facvar.featureid = fac.hydroid
    and facvar.propname = 'wd_current_mgy'
  )
  left outer join dh_properties as currfrac
  on (
    currfrac.entity_type = 'dh_feature'
    and currfrac.featureid = mp.hydroid
    and currfrac.propname = 'facility_use_fraction'
  )
  where mp.bundle in ('intake', 'well')
  and fac.hydroid is not null
  and facvar.propvalue is not null 
  and fac.ftype not like 'wsp%'
  
  "
  
if [ $# -gt 0 ]; then
  hydroid=$1
  frac_query="$frac_query AND fac.hydroid = $hydroid"
fi 

echo $frac_query 

echo $frac_query | psql -h dbase2 drupal.dh03 > /tmp/use_fractions.txt 

n=`< /tmp/use_fractions.txt wc -l`
nm="$((n - 2))"
head -n $nm /tmp/use_fractions.txt > /tmp/fhead.txt 
n=`< /tmp/fhead.txt wc -l`
nm="$((n - 2))"
tail -n $nm /tmp/fhead.txt > /tmp/use_fractions.txt 

# set the individual MP facilitu use fraction
while IFS= read -r line; do
  #echo "Text read from file: $line"
  IFS="$IFS|" read entity_type featureid varkey propname propvalue <<< "$line"
  drush scr modules/om/src/om_setprop.php cmd $entity_type $featureid $varkey $propname $propvalue
done < /tmp/use_fractions.txt 

# now, handle the facility's own gw_frac and sw_frac 
cat modules/dh_wsp/sql/create_use_fractions_wsp_virtual.sql | psql -h dbase2 drupal.dh03 > /tmp/all_use_fractions.txt

# Do SW and GW frac calculations
frac_query="select entity_type, featureid, varkey, propname, propvalue from (
    select 'dh_feature' as entity_type, hydroid as featureid, 
    'om_class_Constant' as varkey, 
    'gw_frac' as propname,
    gw_frac as propvalue
    from tmp_facility_fracs
    UNION 
    select 'dh_feature' as entity_type, hydroid as featureid, 
    'om_class_Constant' as varkey, 
    'sw_frac' as propname,
    sw_frac as propvalue
    from tmp_facility_fracs
  ) as foo 
  "
if [ $# -gt 0 ]; then
  hydroid=$1
  frac_query="$frac_query WHERE featureid = $hydroid"
fi 

echo $frac_query 
 
echo $frac_query | PGOPTIONS='--client-min-messages=warning' psql -h dbase2 drupal.dh03 > /tmp/facility_swgw_fractions.txt 

n=`< /tmp/facility_swgw_fractions.txt wc -l`
nm="$((n - 2))"
head -n $nm /tmp/facility_swgw_fractions.txt > /tmp/fhead.txt 
n=`< /tmp/fhead.txt wc -l`
nm="$((n - 2))"
tail -n $nm /tmp/fhead.txt > /tmp/facility_swgw_fractions.txt 

while IFS= read -r line; do
  #echo "Text read from file: $line"
  IFS="$IFS|" read entity_type featureid varkey propname propvalue <<< "$line"
  echo "drush scr modules/om/src/om_setprop.php cmd $entity_type $featureid $varkey $propname $propvalue"
  drush scr modules/om/src/om_setprop.php cmd $entity_type $featureid $varkey $propname $propvalue
done < /tmp/facility_swgw_fractions.txt 
