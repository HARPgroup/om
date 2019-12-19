#!/bin/sh

entity_type=$1
entity_id=$2
system_name=$3
src_entity_type='dh_feature'
src_entity_id=72575
src_propname='Water Supply Model Element Template'
om_parent=-1
om_template_id=340402

if [ $# -lt 3 ]; then
  echo 1>&2 "Usage: add_waterSystemElement.sh entity_type entity_id system_name [om_parent=NULL] [src_entity_type] [src_entity_id] [src_propname]"
  exit 2
fi 

if [ $# -gt 3 ]; then
  om_parent=$4
fi 

if [ $# -gt 6 ]; then
  src_entity_type=$5
  src_entity_id=$6
  src_propname=$7
fi 

# create the element
echo "drush scr modules/om/src/om_copy_subcomp.php cmd $src_entity_type $src_entity_id $entity_type $entity_id \"$src_propname|$system_name\""
drush scr modules/om/src/om_copy_subcomp.php cmd $src_entity_type $src_entity_id $entity_type $entity_id "$src_propname|$system_name"
echo "pid=`drush scr modules/om/src/om_getpid.php $entity_type $entity_id \"$system_name\"`"
pid=`drush scr modules/om/src/om_getpid.php $entity_type $entity_id "$system_name"`


# create an om_element_connection, with nothing
# modify element connection to have parent submitted
# call push always 

if [ $om_parent -gt 0 ]; then
# create a remote om element below the indicated parent 
# and then push the changes from the vahydro entity to the remote OM record
  echo "drush scr modules/om/src/om_setprop.php cmd dh_properties $pid om_element_connection om_element_connection NULL clone \"om_template_id=$om_template_id&remote_parentid=$om_parent\" "
  drush scr modules/om/src/om_setprop.php cmd dh_properties $pid om_element_connection om_element_connection NULL clone "om_template_id=$om_template_id&remote_parentid=$om_parent"
  drush scr modules/om/src/om_saveprop.php cmd $entity_type $entity_id "$system_name"
  
fi 
