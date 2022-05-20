!/bin/bash

entity_type=$1
entity_id=$2

if [ $# -lt 3 ]; then
  echo 1>&2 "Usage: get_or_copy_prop.sh entity_type entity_id system_name [om_parent=NULL] [template_entity_type] [template_entity_id] [src_propname]" >&2
  exit 2
fi 

pid=`drush scr modules/om/src/om_getpid.php entity_type entity_id NULL cbp6`

# get the feature hydroid 
# search for an existing model of the proper version
# copy a model if none exists 
# run the QA routine to verify existence of runoff table, length and stats

if [ $om_parent -gt 0 ]; then