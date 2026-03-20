<?php


function getWSPWithdrawalByWKT($listobject, $wktshape, $debug) {
   
   # gets maximum capacity of a measuring point
   # the table vwuds_measuring_point has a column "CAPACITY" which should hold this 
   # value in MGD, however, as of 9/25/2007, this value is null in all cases
   # so, we default to querying the annual_data table to estimate this value
   # we estimate the value based on the maximum reported daily withdrawal (MAXDAY) in MGD
   # or the largest single month (in MG) divided by the number of days in that month
   # whichever is largest
   $output = array();
   $output['message'] = '';
   // both GW & SW
   $listobject->querystring = " select CASE ";
   $listobject->querystring .= "    WHEN current_mgy is null THEN 0.0 ";
   $listobject->querystring .= "    ELSE current_mgy ";
   $listobject->querystring .= "    END as current_mgy , ";
   $listobject->querystring .= "    CASE ";
   $listobject->querystring .= "       WHEN current_max_mgd is null THEN 0.0 ";
   $listobject->querystring .= "       ELSE current_max_mgd ";
   $listobject->querystring .= "    END as current_max_mgd, ";
   $listobject->querystring .= "    CASE ";
   $listobject->querystring .= "       WHEN proj_use_mgy is null THEN 0.0 ";
   $listobject->querystring .= "       ELSE proj_use_mgy  ";
   $listobject->querystring .= "    END as proj_use_mgy, ";
   $listobject->querystring .= "    CASE ";
   $listobject->querystring .= "       WHEN sw_bg_mgd is null THEN 0.0 ";
   $listobject->querystring .= "       WHEN sw_bg_mgd = 0.0 THEN 0.0 ";
   $listobject->querystring .= "       WHEN sw_bg_mgd > 0.0 ";
   $listobject->querystring .= "       THEN ( sw_bg_mgd / ( sw_bg_mgd + gw_bg_mgd ))";
   $listobject->querystring .= "       ELSE 1.0 ";
   $listobject->querystring .= "    END as fraction_sw, ";
   $listobject->querystring .= "    CASE ";
   $listobject->querystring .= "       WHEN proj_tfr_mgy is null THEN 0.0 ";
   $listobject->querystring .= "       ELSE proj_tfr_mgy  ";
   $listobject->querystring .= "    END as proj_tfr_mgy, ";
   $listobject->querystring .= "    CASE ";
   $listobject->querystring .= "       WHEN current_tfr_mgy is null THEN 0.0 ";
   $listobject->querystring .= "       ELSE current_tfr_mgy ";
   $listobject->querystring .= "    END as current_tfr_mgy";
   $listobject->querystring .= " from (";
   $listobject->querystring .= " select sum(current_mgy) as current_mgy,";
   $listobject->querystring .= "    sum(current_max_mgd) as current_max_mgd, ";
   $listobject->querystring .= "    sum(sw_bg_mgd ) as sw_bg_mgd, ";
   $listobject->querystring .= "    sum(gw_bg_mgd ) as gw_bg_mgd, ";
   $listobject->querystring .= "    sum(proj_tfr_mgy ) as proj_tfr_mgy, ";
   $listobject->querystring .= "    sum(current_tfr_mgy ) as current_tfr_mgy, ";
   $listobject->querystring .= "    sum(proj_use_mgy ) as proj_use_mgy  ";
   $listobject->querystring .= "       from wsp_sysloc_cache ";
   $listobject->querystring .= "    where st_contains( st_geomFromText('$wktshape', 4326), the_geom)  ";
   $listobject->querystring .= " ) as foo ";
  
   $output['query'] = $listobject->querystring;
   $listobject->performQuery();
   $output = array_merge($output, $listobject->queryrecords[0]);
   $cmgy = $listobject->getRecordValue(1,'current_mgy');
   $pmgy = $listobject->getRecordValue(1,'proj_use_mgy');
   $swfrac = $listobject->getRecordValue(1,'fraction_sw');
   $output['total_wd_mgd'] = $cmgy / 365.0;
   $output['total_current_mgd'] = $cmgy / 365.0;
   $output['total_proj_mgd'] = $pmgy / 365.0;
   $output['sw_current_mgd'] = $swfrac * $cmgy / 365.0;
   $output['sw_proj_mgd'] = $swfrac * $pmgy / 365.0;
   $output['sw_fraction'] = $swfrac;
   $er = $output;
   $er['query'] = '';
   error_log(print_r($er,1));
   return $output;
}


?>