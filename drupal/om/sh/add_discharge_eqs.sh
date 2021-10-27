#!/bin/sh
# This adds new return flow structure as developed during spring of 2021
pid=$1
template=4988636

if [ $# -lt 1 ]; then
  echo 1>&2 "Usage: add_discharge_eqs.sh pid [template=$template]"
  exit 2
fi 

if [ $# -gt 1 ]; then
  template=$2
fi 

drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_local_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_nextdown_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid ps_other_mgd;
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid discharge_mgd; 
drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $pid "Send to Parent"; 