#!/bin/sh
# Set up scripts
# All
sudo chown www-data:allmodelers www/om/cache
# set up cbp to www link
ln -s /opt/model/p53/p532c-sova/out /opt/model/p53/p532c-sova/tmp/out       
# Make folder always have specific owner and group 


# NAS big links
sudo mount deqnas:/data /media/NAS
sudo mount deqnas2:/data /media/NAS2
ln -s /media/model/p6/tmp/wdm/river /opt/model/p6/p6_gb604/tmp/wdm/river
ln -s /media/model/p6/tmp/wdm/land /opt/model/p6/p6_gb604/tmp/wdm/land
ln -s /media/model/p6/out /opt/model/p6/p6_gb604/out
ln -s /media/NAS2/omdata /var/www/html/data
ln -s /media/NAS2/omdata /var/www/html/data

// Libraries

rm /var/www/html/lib/phpmath 
ln -s /opt/model/om/php/lib/phpmath /var/www/html/lib/phpmath
rm /var/www/html/lib/lib_hydrology.php
ln -s /opt/model/om/php/lib/lib_hydrology.php /var/www/html/lib/lib_hydrology.php
rm /var/www/html/lib/lib_wooomm.php
ln -s /opt/model/om/php/lib/lib_wooomm.php /var/www/html/lib/lib_wooomm.php
rm /var/www/html/lib/lib_wooomm.USGS.php
ln -s /opt/model/om/php/lib/lib_wooomm.USGS.php /var/www/html/lib/lib_wooomm.USGS.php
rm /var/www/html/lib/lib_usgs.php
ln -s /opt/model/om/php/lib/lib_usgs.php /var/www/html/lib/lib_usgs.php
rm /var/www/html/lib/lib_wooomm.cbp.php
ln -s /opt/model/om/php/lib/lib_wooomm.cbp.php /var/www/html/lib/lib_wooomm.cbp.php
rm /var/www/html/lib/misc_functions.php
ln -s /opt/model/om/php/lib/misc_functions.php /var/www/html/lib/misc_functions.php
rm /var/www/html/lib/lib_wooomm.wsp.php
ln -s /opt/model/om/php/lib/lib_wooomm.wsp.php /var/www/html/lib/lib_wooomm.wsp.php
rm /var/www/html/lib/lib_equation2.php
ln -s /opt/model/om/php/lib/lib_equation2.php /var/www/html/lib/lib_equation2.php
rm /var/www/html/lib/psql_functions.php
ln -s /opt/model/om/php/lib/psql_functions.php /var/www/html/lib/psql_functions.php
rm /var/www/html/lib/PEAR -Rf
ln -s /opt/model/om/php/lib/PEAR /var/www/html/lib/PEAR

# dev
# We used to do dev separate from live, because the path is /opt/model/om-dev,  but now we have a 
# soft-link for opt/model/om to opt/model/om-dev so all is one. we can regenerate if need be later
# live
rm /var/www/html/om/who_xmlobjects.frisk.php
ln -s  /opt/model/om/php/src/who_xmlobjects.frisk.php /var/www/html/om/who_xmlobjects.frisk.php
rm /var/www/html/om/who_xmlobjects.usgs.php
ln -s  /opt/model/om/php/src/who_xmlobjects.usgs.php /var/www/html/om/who_xmlobjects.usgs.php
rm /var/www/html/om/who_xmlobjects.wsp.php
ln -s  /opt/model/om/php/src/who_xmlobjects.wsp.php /var/www/html/om/who_xmlobjects.wsp.php
rm /var/www/html/om/who_xmlobjects.php
ln -s  /opt/model/om/php/src/who_xmlobjects.php /var/www/html/om/who_xmlobjects.php
rm /var/www/html/om/remote/get_modelData.php
ln -s  /opt/model/om/php/src/remote/get_modelData.php /var/www/html/om/remote/get_modelData.php
rm /var/www/html/om/set_elemNHDlanduse.php
ln -s /opt/model/om/php/src/set_elemNHDlanduse.php /var/www/html/om/set_elemNHDlanduse.php
rm /var/www/html/om/get_comids_shape.php
ln -s /opt/model/om/php/src/get_comids_shape.php /var/www/html/om/get_comids_shape.php
rm /var/www/html/om/get_nhd_basins.php
ln -s /opt/model/om/php/src/get_nhd_basins.php /var/www/html/om/get_nhd_basins.php
rm /var/www/html/om/fn_find_missing_subcomp.php
ln -s /opt/model/om/php/src/fn_find_missing_subcomp.php /var/www/html/om/fn_find_missing_subcomp.php
rm /var/www/html/om/fn_find_bad_matrices.php
ln -s /opt/model/om/php/src/fn_find_bad_matrices.php /var/www/html/om/fn_find_bad_matrices.php
rm /var/www/html/om/fn_find_prop_value.php
ln -s /opt/model/om/php/src/fn_find_prop_value.php /var/www/html/om/fn_find_prop_value.php
rm /var/www/html/om/fn_copy_element.php
ln -s /opt/model/om/php/src/fn_copy_element.php /var/www/html/om/fn_copy_element.php
rm /var/www/html/om/fn_rename_group_subcomp.php
ln -s /opt/model/om/php/src/fn_rename_group_subcomp.php /var/www/html/om/fn_rename_group_subcomp.php
rm /var/www/html/om/fn_delete_group_subcomp.php
ln -s /opt/model/om/php/src/fn_delete_group_subcomp.php /var/www/html/om/fn_delete_group_subcomp.php
rm /var/www/html/om/fn_copy_group_subcomp.php
ln -s /opt/model/om/php/src/fn_copy_group_subcomp.php /var/www/html/om/fn_copy_group_subcomp.php
rm /var/www/html/om/run_shakeTree.php
ln -s /opt/model/om/php/src/run_shakeTree.php /var/www/html/om/run_shakeTree.php
rm  /var/www/html/om/run_model.php
ln -s /opt/model/om/php/src/run_model.php /var/www/html/om/run_model.php
rm /var/www/html/om/lib_verify.php
ln -s /opt/model/om/php/src/lib_verify.php /var/www/html/om/lib_verify.php
rm /var/www/html/om/fn_checkTreeRunDate.php
ln -s /opt/model/om/php/src/fn_checkTreeRunDate.php /var/www/html/om/fn_checkTreeRunDate.php
rm /var/www/html/om/fn_batchedit_broadcast_matrix.php
ln -s /opt/model/om/php/src/fn_batchedit_broadcast_matrix.php /var/www/html/om/fn_batchedit_broadcast_matrix.php
rm /var/www/html/om/adminsetup.php
ln -s /opt/model/om/php/src/adminsetup.php /var/www/html/om/adminsetup.php
rm /var/www/html/om/set_subprop.php
ln -s /opt/model/om/php/src/set_subprop.php /var/www/html/om/set_subprop.php
rm /var/www/html/om/setprop.php
ln -s /opt/model/om/php/src/setprop.php /var/www/html/om/setprop.php
rm /var/www/html/om/get_modelStatus.php
ln -s /opt/model/om/php/src/get_modelStatus.php /var/www/html/om/get_modelStatus.php
rm /var/www/html/om/get_model.php
ln -s /opt/model/om/php/src/get_model.php /var/www/html/om/get_model.php
rm /var/www/html/om/get_statusTree.php
ln -s /opt/model/om/php/src/get_statusTree.php /var/www/html/om/get_statusTree.php
rm /var/www/html/om/xajax_modeling.element.php
ln -s /opt/model/om/php/src/xajax_modeling.element.php /var/www/html/om/xajax_modeling.element.php
rm /var/www/html/om/xajax_config.php
ln -s /opt/model/om/php/src/xajax_config.php /var/www/html/om/xajax_config.php
rm /var/www/html/om/fn_message_model.php
ln -s /opt/model/om/php/src/fn_message_model.php /var/www/html/om/fn_message_model.php
rm /var/www/html/om/fn_addObjectLink.php
ln -s /opt/model/om/php/src/fn_addObjectLink.php /var/www/html/om/fn_addObjectLink.php
rm /var/www/html/om/test_order.php
ln -s /opt/model/om/php/src/test_order.php /var/www/html/om/test_order.php
rm /var/www/html/om/test_db.php
ln -s /opt/model/om/php/src/test_db.php /var/www/html/om/test_db.php
rm /var/www/html/om/fn_getRunFile.php
ln -s /opt/model/om/php/src/fn_getRunFile.php /var/www/html/om/fn_getRunFile.php
