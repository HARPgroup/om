#!/bin/bash


if [ $# -lt 2 ]; then
  echo 1>&2 "Usage: create_landuse_table.sh N51045 CFBASE30Y20180615"
  exit 2
fi 

landseg=$1
scenario=$2

# i.e. create_landseg_table.sh N51045 JU1_7630_7490 CFBASE30Y20180615
template="cbp_p6_lseg_runoff_template"
filename="/media/model/p6/out/land/$scenario/eos/${landseg}_0111-0211-0411.csv"
tablename="cbp_p6_${scenario}_${landseg}"
tablename=`echo $tablename | tr '[:upper:]' '[:lower:]'`
hdrcols=`head -n 1 $filename`

echo "Populating $tablename "

set -f
csql=" create table $tablename as select * from $template limit 0;"
isql="copy $tablename ($hdrcols) from '$filename' WITH CSV HEADER "
isql="$isql; update $tablename set timestamp = extract(epoch from thisdate) "
isql="$isql; create index ${tablename}_tix on $tablename (timestamp) "
echo "BEGIN; $csql; $isql; COMMIT;" | psql -U postgres -p 5444 model_scratch
set +f

exit

