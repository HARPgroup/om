#!/bin/sh

template=4988636
filename=$1

for i in `cat $filename`; do 
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $i unaccounted_losses;
  drush scr modules/om/src/om_copy_subcomp.php cmd dh_properties $template dh_properties $i discharge_mgd;
done
