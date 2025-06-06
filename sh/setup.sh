#!/bin/sh
# Set up scripts
# put landseg creator, which now looks for hspf_config to determine which data set to use
cp create_landseg_table.sh /usr/local/bin/

# All
sudo chown www-data:allmodelers www/om/cache
# set up cbp to www link
ln -s /opt/model/p53/p532c-sova/out /opt/model/p53/p532c-sova/tmp/out       
# Make folder always have specific owner and group 

# link drupal module in
rm /var/www/html/d.dh/modules/om
ln -s /opt/model/om/drupal/om /var/www/html/d.dh/modules/om

# NAS big links
sudo mount deqnas:/data /media/NAS
sudo mount deqnas2:/data /media/NAS2
ln -s /media/model/p6/tmp/wdm/river /opt/model/p6/p6_gb604/tmp/wdm/river
ln -s /media/model/p6/tmp/wdm/land /opt/model/p6/p6_gb604/tmp/wdm/land
ln -s /media/model/p6/out /opt/model/p6/p6_gb604/out
ln -s /media/model/omdata /var/www/html/data
ln -s /media/model/p532/tmp/wdm /opt/model/p53/p532c-sova/tmp/wdm

// Libraries
rm /var/www/html/scripts
ln -s /opt/model/om/scripts /var/www/html/scripts

rm /var/www/html/lib/met_qa.php
ln -s /opt/model/om/php/src/met_qa.php /var/www/html/src/met_qa.php
rm /var/www/html/lib/db_functions.php
ln -s /opt/model/om/php/lib/db_functions.php /var/www/html/lib/db_functions.php
rm /var/www/html/lib/phpmath 
ln -s /opt/model/om/php/lib/phpmath /var/www/html/lib/phpmath
rm /var/www/html/lib/lib_hydrology.php
ln -s /opt/model/om/php/lib/lib_hydrology.php /var/www/html/lib/lib_hydrology.php
rm /var/www/html/lib/lib_batchmodel.php
ln -s /opt/model/om/php/lib/lib_batchmodel.php /var/www/html/lib/lib_batchmodel.php
rm /var/www/html/lib/lib_wooomm.php
ln -s /opt/model/om/php/lib/lib_wooomm.php /var/www/html/lib/lib_wooomm.php
rm /var/www/html/lib/lib_wooomm.USGS.php
ln -s /opt/model/om/php/lib/lib_wooomm.USGS.php /var/www/html/lib/lib_wooomm.USGS.php
rm /var/www/html/lib/lib_nhdplus.php
ln -s /opt/model/om/php/lib/lib_nhdplus.php /var/www/html/lib/lib_nhdplus.php
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

# OM
# We used to do dev separate from live, because the path is /opt/model/om-dev,  but now we have a 
# soft-link for opt/model/om to opt/model/om-dev so all is one. we can regenerate if need be later
# live
rm /var/www/html/om/nhd_make_vahydro
ln -s /opt/model/vahydro/sh/nhd_make_vahydro /var/www/html/om/nhd_make_vahydro
rm /var/www/html/om/xajax_modeling.common.php
ln -s /opt/model/om/php/src/xajax_modeling.common.php /var/www/html/om/xajax_modeling.common.php
rm /var/www/html/om/test_step.php
ln -s /opt/model/om/php/src/test_step.php /var/www/html/om/test_step.php
rm /var/www/html/om/set_element.php
ln -s  /opt/model/om/php/src/set_element.php /var/www/html/om/set_element.php
rm /var/www/html/om/import_element_json.php
ln -s  /opt/model/om/php/src/import_element_json.php /var/www/html/om/import_element_json.php
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
