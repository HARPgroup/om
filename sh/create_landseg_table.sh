#!/bin/bash
. hspf_config

if [ $# -lt 2 ]; then
  echo 1>&2 "Usage: create_landuse_table.sh landseg scenario [overwrite=0/1]"
  echo 1>&2 "Usage: create_landuse_table.sh N51045 CFBASE30Y20180615 1"
  exit 2
fi 

landseg=$1
scenario=$2
overwrite=0
if [ "$#" -eq 3 ] ; then
  overwrite=$3
fi

# i.e. create_landseg_table.sh N51045 JU1_7630_7490 CFBASE30Y20180615
if [ -z "$CBP_EXPORT_DIR" ]; then 
  echo "Could not find CBP_EXPORT var or could not find file hspf.config on path or in /etc/ dir"
  echo "Run this script from a path containing hspf.config to set vars: CBP_RO_TEMPLATE & CBP_EXPORT_DIR"
else 
  template=$CBP_RO_TEMPLATE
  cbp_path=$CBP_EXPORT_DIR
fi
filename="$cbp_path/out/land/$scenario/eos/${landseg}_0111-0211-0411.csv"
tablename="cbp_p6_${scenario}_${landseg}"
tablename=`echo $tablename | tr '[:upper:]' '[:lower:]'`
hdrcols=`head -n 1 $filename`

template_table_file=`basename $CBP_RO_TEMPLATE`
template=`echo "$template_table_file" | cut -d'.' -f1`
echo "Populating $tablename from $template"

set -f
if [ $overwrite -eq 1 ]; then
  echo "drop table $tablename " | psql -U postgres -p 5444 model_scratch
fi

csql=" create table $tablename as select * from $template limit 0;"
isql="copy $tablename ($hdrcols) from '$filename' WITH CSV HEADER "
isql="$isql; update $tablename set timestamp = extract(epoch from thisdate) "
isql="$isql; create index ${tablename}_tix on $tablename (timestamp) "
echo "BEGIN; $csql; $isql; COMMIT;" | psql -U postgres -p 5444 model_scratch
set +f

exit

