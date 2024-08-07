<?php


include('./config.php');

$scenarioid = 2;

$actiontype = 1; # 1 - data list, 2 - get location list, 3 - get location data, 4 - get data
if (isset($_GET['actiontype'])) {
   $actiontype = $_GET['actiontype'];
}

 if (!isset($_GET['output_type'])) {
    $output_type = 'xml';
} else {
    $output_type = $_GET['output_type'];
}

 if (!isset($_GET['userid'])) {
    $userid = '';
} else {
    $userid = $_GET['userid'];
}

if (!isset($_GET['delim'])) {
   $delim = "\t";
} else {
   $delim = $_GET['delim'];
}

if (!isset($_GET['debug'])) {
   $debug = 0;
} else {
   $debug = $_GET['debug'];
}

if (!isset($_GET['geomtype'])) {
   $geomtype = 'wkt';
} else {
   $geomtype = strtolower($_GET['geomtype']);
}
// get VWUDS data 
// what query options should we provide:
// type - withdrawal, release, transfer
// use_type - AGR, IRR, PWS, PH, PF, PN, ...
// geography - BBOX, FIPS, HUC, ?


// info generated:
// use types, from waterusetype
// 
// annual max by category
// historical monthly mean percent of annual by use_type (row - type, column - month)
// update other components, such as the summary data, and the category multipliers

// vwuds_monthly_data
//  the_geom | geometry              |
//  userid   | character varying(12) |
//  mpid     | character varying(32) |
//  lat_dd   | double precision      |
//  lon_dd   | double precision      |
//  thisdate | date                  |
//  wd_mg    | double precision      |
//  w_type   | character varying(2)  |
//  w_action | character varying(2)  |
//  cat_mp   | character varying(3)  |
//  wd_mgd   | double precision      |

// vwuds_max_action
//     Column     |          Type          | Modifiers
//----------------+------------------------+-----------
// the_geom       | geometry               |
// userid         | character varying(16)  |
// action         | character varying(2)   |
// mpid           | character varying(15)  |
// cat_mp         | character varying(3)   |
// type           | character varying(2)   |
// source         | character varying(128) |
// avg_annual     | double precision       |
// max_annual     | double precision       |
// year_of_max    | text                   |
// max_maxday     | double precision       |
// avg_maxday     | double precision       |
// year_of_maxday | text                   |


// waterusetype


$id1 = '';
if (isset($_GET['id1'])) {
   $id1 = $_GET['id1'];
}
$id2 = '';
if (isset($_GET['id2'])) {
   $id2 = $_GET['id2'];
}
$the_geom = '';
if (isset($_GET['the_geom'])) {
   $the_geom = $_GET['the_geom'];
}
$startdate = '';
if (isset($_GET['startdate'])) {
   $startdate = $_GET['startdate'];
}
$enddate = '';
if (isset($_GET['enddate'])) {
   $enddate = $_GET['enddate'];
}
$timestep = '';
if (isset($_GET['timestep'])) {
   $timestep = $_GET['timestep'];
}
$authenticated = 0;
$passcodes = array('vwuds010203get'=>'facility_read', 'drupal_read'=>'public_test');
if (isset($_GET['passcode'])) {
   if (in_array($_GET['passcode'], array_keys($passcodes))) {
      $authlevel = $passcodes[$_GET['passcode']];
      $authenticated = 1;
   }
}
//error_reporting(E_ALL);
error_log("Inputs:" . print_r($_GET,1));

require_once("$libpath/feedcreator/feedcreator.class.php");
// make sure the cache is cleared
// shouldn't do this, as I think this is actually used by magpie, NOT feedcreator
//shell_exec("rm ../rsscache/* -f");
$rss = new UniversalFeedCreator();
//$rss->useCached();
$rss->title = "Test news";
$rss->link = "http://test.com/news";
$rss->syndicationURL = "http://deq1.bse.vt.edu/".$PHP_SELF;

switch ($actiontype) {
   case 1:
   // time series variables
   // this is static for now, but we could use a query like in case 4 to see exactly what the server HAS for a given area
      // this could form the basis of our WFS retrieval system.

      $cols = "userid,mpid,lat_dd,lon_dd,thisdate,wd_mg,w_type,w_action,cat_mp,wd_mgd";
      $item = new FeedItem();
      $proplist = array();
      $proplist['all_columns'] = $cols;
      $item->additionalElements = $proplist;
      $rss->addItem($item);
      $xml = $rss->createFeed("2.0");
   break;

   case 2:
   // use types and descriptors - consumptive factors
   // this is static for now, but we could use a query like in case 4 to see exactly what the server HAS for a given area
      // this could form the basis of our WFS retrieval system.

      // waterusetype
      //  typeid   
      //  typename
      //  typeabbrev 
      //  consumption 
      //  max_day_mos 
      //$cols = array('SURO','IFWO','AGWO');
      $vwuds_listobject->querystring = "  select typeabbrev, typename, consumption, max_day_mos ";
      $vwuds_listobject->querystring .= " from waterusetype ";
      $vwuds_listobject->querystring .= " order by typeabbrev ";
      $vwuds_listobject->performQuery();
   
      $j = 0;
      // itemized feed
      foreach($vwuds_listobject->queryrecords as $data) {
         $j++;
         $item = new FeedItem();
         $proplist = $data;
         $item->additionalElements = $proplist;
         $rss->addItem($item);
      }
      
      $xml = $rss->createFeed("2.0");
   break;

   case 3:
   // Historic Monthly use Percentages
   
      $vwuds_listobject->querystring = "  select a.typeabbrev, ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"JANUARY\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"JANUARY\") ";
      $vwuds_listobject->querystring .= "    END as \"jan\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"FEBRUARY\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"FEBRUARY\") ";
      $vwuds_listobject->querystring .= "    END as \"feb\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"MARCH\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"MARCH\") ";
      $vwuds_listobject->querystring .= "    END as \"mar\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"APRIL\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"APRIL\") ";
      $vwuds_listobject->querystring .= "    END as \"apr\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"MAY\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"MAY\") ";
      $vwuds_listobject->querystring .= "    END as \"may\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"JUNE\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"JUNE\") ";
      $vwuds_listobject->querystring .= "    END as \"jun\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"JULY\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"JULY\") ";
      $vwuds_listobject->querystring .= "    END as \"jul\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"AUGUST\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"AUGUST\") ";
      $vwuds_listobject->querystring .= "    END as \"aug\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"SEPTEMBER\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"SEPTEMBER\") ";
      $vwuds_listobject->querystring .= "    END as \"sep\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"OCTOBER\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"OCTOBER\") ";
      $vwuds_listobject->querystring .= "    END as \"oct\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"NOVEMBER\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"NOVEMBER\") ";
      $vwuds_listobject->querystring .= "    END as \"nov\", ";
      $vwuds_listobject->querystring .= "    CASE ";
      $vwuds_listobject->querystring .= "       WHEN sum(b.\"DECEMBER\") is null THEN 0.0 ";
      $vwuds_listobject->querystring .= "       ELSE sum(b.\"DECEMBER\") ";
      $vwuds_listobject->querystring .= "    END as \"dec\" ";
      $vwuds_listobject->querystring .= " from waterusetype as a left outer join vwuds_annual_mp_data as b ";
      $vwuds_listobject->querystring .= " on (a.typeabbrev = b.\"CAT_MP\" ";
      if (strlen($the_geom) > 0) {
         $vwuds_listobject->querystring .= "    and within(b.the_geom, geomFromText('$the_geom',4326)) ";
      }
      $vwuds_listobject->querystring .= " )";
      if (strlen($id1) > 0) {
         // by putting this outside of the "ON" clause, we override the left join, only returning these specific uses
         $vwuds_listobject->querystring .= "    WHERE b.\"USERID\" = '$id1' ";
      }
      $vwuds_listobject->querystring .= " group by typeabbrev ";
      $vwuds_listobject->querystring .= " order by typeabbrev ";
      error_log($vwuds_listobject->querystring);
      $vwuds_listobject->performQuery();
   
      $j = 0;
      //$item = new FeedItem();
      //$proplist = array('query'=>$vwuds_listobject->querystring . "<br>\n");
      //$item->additionalElements = $proplist;
      //$rss->addItem($item);
      // itemized feed
      $recdata = $vwuds_listobject->queryrecords;
      //error_log("Retrieved " . count($recdata) . " records <br>\n");
      foreach($recdata as $data) {
         $j++;
         $item = new FeedItem();
         $proplist = $data;
         $item->additionalElements = $proplist;
         $rss->addItem($item);
      }
      
      $xml = $rss->createFeed("2.0");
   break;

   case 4:
   // Historic Annual use amounts
   
      $basetable = 'tmp_vwuds_ann' . rand(1,1000);
      $dec_points = 4;// need to query this to allow for 4 sig-figs, for now, we assume 4 decimal points will do
      $src_table_sql = "  select a.typeabbrev, 'thisyear' || a.thisyear as yearcol, ";
      $src_table_sql .= "    CASE ";
      $src_table_sql .= "       WHEN sum(b.\"ANNUAL\") is null THEN 0.0 ";
      $src_table_sql .= "       ELSE round((sum(b.\"ANNUAL\")/365.0)::numeric,$dec_points) ";
      $src_table_sql .= "    END as \"total_mg\" ";
      $src_table_sql .= " from (";
      $src_table_sql .= "    select a.typeabbrev, b.thisyear ";
      $src_table_sql .= "    from waterusetype as a, (";
      $src_table_sql .= "       select \"YEAR\" as thisyear from vwuds_annual_mp_data group by thisyear";
      $src_table_sql .= "    ) as b";
      $src_table_sql .= "  ) as a ";
      $src_table_sql .= "  left outer join vwuds_annual_mp_data as b ";

      $src_table_sql .= " on (a.typeabbrev = b.\"CAT_MP\" AND a.thisyear = b.\"YEAR\" ";
      if (strlen($the_geom) > 0) {
         $src_table_sql .= "    and within(b.the_geom, geomFromText('$the_geom',4326)) ";
      }
      $src_table_sql .= " )";
      if (strlen($id1) > 0) {
         // by putting this outside of the "ON" clause, we override the left join, only returning these specific uses
         $src_table_sql .= "    WHERE b.\"USERID\" = '$id1' ";
      }
      $src_table_sql .= " group by typeabbrev, yearcol ";
      $src_table_sql .= " order by typeabbrev ";

      $basetable_sql = "  create temp table $basetable as  ";
      $basetable_sql .= $src_table_sql;
      $vwuds_listobject->querystring = $basetable_sql;
      error_log($vwuds_listobject->querystring);
      $vwuds_listobject->performQuery();

      $groupcols = 'typeabbrev';
      $crosscol = 'yearcol';
      $valcol = 'total_mg';
      $crosstab_query = doGenericCrossTab ($vwuds_listobject, $basetable, $groupcols, $crosscol, $valcol, 1, 0);
      //error_log("$crosstab_query<br>");
      $vwuds_listobject->querystring = $crosstab_query;
      $vwuds_listobject->performQuery();
   
      $j = 0;
      $recdata = $vwuds_listobject->queryrecords;
      //error_log("Retrieved " . count($recdata) . " records <br>\n");
      foreach($recdata as $data) {
         $j++;
         $item = new FeedItem();
         $proplist = $data;
         $item->additionalElements = $proplist;
         $rss->addItem($item);
      }
      
      $xml = $rss->createFeed("2.0");
   break;

   case 5:
   // Maximum Historic Annual use amounts in last 5 years - for use by current
   
      $basetable = 'tmp_vwuds_curr' . rand(1,1000);
      $thisyear = date('Y');
      $current_span = 5; // how far back to look at for max current withdrawal
      $dec_points = 4;// need to query this to allow for 4 sig-figs, for now, we assume 4 decimal points will do
      $src_table_sql = "  select typeabbrev, max(total_mg) from ( ";
      $src_table_sql .= "     select a.typeabbrev, a.thisyear as yearcol, ";
      $src_table_sql .= "       CASE ";
      $src_table_sql .= "          WHEN sum(b.\"ANNUAL\") is null THEN 0.0 ";
      $src_table_sql .= "          ELSE round((sum(b.\"ANNUAL\")/365.0)::numeric,$dec_points) ";
      $src_table_sql .= "       END as \"total_mg\" ";
      $src_table_sql .= "    from (";
      $src_table_sql .= "       select a.typeabbrev, b.thisyear ";
      $src_table_sql .= "       from waterusetype as a, (";
      $src_table_sql .= "          select \"YEAR\" as thisyear from vwuds_annual_mp_data group by thisyear";
      $src_table_sql .= "       ) as b";
      $src_table_sql .= "     ) as a ";
      $src_table_sql .= "     left outer join vwuds_annual_mp_data as b ";

      $src_table_sql .= "    on (a.typeabbrev = b.\"CAT_MP\" AND a.thisyear = b.\"YEAR\" ";
      if (strlen($the_geom) > 0) {
         $src_table_sql .= "       and within(b.the_geom, geomFromText('$the_geom',4326)) ";
      }
      $src_table_sql .= "    )";
      if (strlen($id1) > 0) {
         // by putting this outside of the "ON" clause, we override the left join, only returning these specific uses
         $src_table_sql .= "       WHERE b.\"USERID\" = '$id1' ";
      }
      $src_table_sql .= "    group by typeabbrev, yearcol ";
      $src_table_sql .= "    order by typeabbrev ";
      $src_table_sql .= " ) as foo ";
      $src_table_sql .= " WHERE yearcol::integer >= ($thisyear - $current_span) ";
      $src_table_sql .= " group by typeabbrev ";
      $src_table_sql .= " order by typeabbrev ";

      $vwuds_listobject->querystring = $src_table_sql;
      error_log($vwuds_listobject->querystring);
      $vwuds_listobject->performQuery();
   
      $j = 0;
      $recdata = $vwuds_listobject->queryrecords;
      //error_log("Retrieved " . count($recdata) . " records <br>\n");
      foreach($recdata as $data) {
         $j++;
         $item = new FeedItem();
         $proplist = $data;
         $item->additionalElements = $proplist;
         $rss->addItem($item);
      }
      
      $xml = $rss->createFeed("2.0");
   break;
   
   case 6:
   // facility record
   // check the authentication, if authenticated, proceed, if not, return empty
   // get the appropriate set of records, return in csv form
   if ($debug) {
      error_log("Requested csv dump of facilities table <br>");
   }
   if ($authenticated) {
      // select the appropriate data set
      switch ($authlevel) {
         case 'facility_read':
            $vwuds_listobject->querystring = " select ownname || ' ' || facility || ' ' || system ";
            $vwuds_listobject->querystring .= "   || ' (' || userid || ')' as title, userid, facility, system, ";
            $vwuds_listobject->querystring .= "   email, ";
            if ($userid <> '') {
               // uses for debugging
               //$vwuds_listobject->querystring .= " now() as import_time, ";
            }
            switch ($geomtype) {
               case 'wkt':
               $vwuds_listobject->querystring .= " ST_asText(setsrid(multi(the_geom),4326)) as the_geom  ";
               break;
               
               case 'kml':
               $vwuds_listobject->querystring .= " asKML(setsrid(multi(the_geom),4326)) as the_geom ";
               break;
               
               case 'dd':
               $vwuds_listobject->querystring .= " lat_flt as latitude, lon_flt as longitude  ";
               break;
               
               default:
               $vwuds_listobject->querystring .= " lat_flt as latitude, lon_flt as longitude  ";
               break;
            }
            $vwuds_listobject->querystring .= " from facilities ";
            if ($userid <> '') {
               $vwuds_listobject->querystring .= " where userid in ( '" . join("','", explode(",",$userid)) . "') ";
            }
            //$vwuds_listobject->querystring .= " limit 1000";
            // screen for small subset while testing
            //$vwuds_listobject->querystring .= "WHERE ownname ilike '%halifax%' ";
         break;
      }
      $vwuds_listobject->performQuery();
      //if ($debug) { 
         error_log("Query returned $vwuds_listobject->numrows records: $vwuds_listobject->querystring ");
      //}
      $csv = '';
      if ($vwuds_listobject->numrows > 0) {
         $header = join($delim, array_keys($vwuds_listobject->queryrecords[0]));
         $csv .= "$header\r\n";
         foreach ($vwuds_listobject->queryrecords as $thisrec) {
            $line = join($delim, array_values($thisrec));
            $csv .= "$line\r\n";
            $item = new FeedItem();
            $item->additionalElements = $thisrec;
            $rss->addItem($item);
         }
         $xml = $rss->createFeed("2.0");
      } else {
         $csv .= "Query returned 0 records: $vwuds_listobject->querystring ";
      }
   }
   break;
   
   case 7:
   // Measuring Point record
   // check the authentication, if authenticated, proceed, if not, return empty
   // get the appropriate set of records, return in csv form
   if ($debug) {
      error_log("Requested csv dump of facilities table <br>");
   }
   if ($authenticated) {
      // select the appropriate data set
      switch ($authlevel) {
         case 'facility_read':
            $vwuds_listobject->querystring = " select \"ACTION\" || ' from ' || \"SOURCE\" || ' (' || \"MPID\" || ' ' || \"USERID\" || ')' as title, \"MPID\", \"USERID\", \"ACTION\", \"TYPE\", \"SUBTYPE\", \"CAT_MP\", \"DEQ_WELL\" as deq_well, ";
            if ($userid <> '') {
               // uses for debugging
               //$vwuds_listobject->querystring .= " now() as import_time, ";
            }
            switch ($geomtype) {
               case 'wkt':
               $vwuds_listobject->querystring .= " ST_asText(the_geom) as the_geom  ";
               break;
               
               case 'kml':
               $vwuds_listobject->querystring .= " asKML(the_geom) as the_geom ";
               break;
               
               default:
               $vwuds_listobject->querystring .= " ST_asText(the_geom) as the_geom  ";
               break;
            }
            
            $vwuds_listobject->querystring .= " from vwuds_measuring_point ";
            $vwuds_listobject->querystring .= "WHERE (1 = 1) ";
            if ($userid <> '') {
               $vwuds_listobject->querystring .= " AND \"USERID\" in ( '" . join("','", explode(",",$userid)) . "') ";
            }
            // screen for small subset while testing
            //$vwuds_listobject->querystring .= "AND the_geom && (select extent(the_geom) from va_counties where name = 'Albemarle') ";
            //$vwuds_listobject->querystring .= " AND ownerid =1 ";
            //$vwuds_listobject->querystring .= " AND the_geom is not null ";
            // specify a numerical limit to the results returned
            //$vwuds_listobject->querystring .= " limit 10";
         break;
         case 'public_test':
            $vwuds_listobject->querystring = " select \"ACTION\" || ' from ' || \"SOURCE\" || ' (' || \"MPID\" || ' ' || \"USERID\" || ')' as title, \"MPID\", \"USERID\", \"ACTION\", \"TYPE\", \"SUBTYPE\", \"CAT_MP\", ";
            $vwuds_listobject->querystring .= " ST_asText(the_geom) as the_geom  ";
            //$vwuds_listobject->querystring .= " asKML(the_geom) as the_geom ";
            $vwuds_listobject->querystring .= " from vwuds_measuring_point ";
            $vwuds_listobject->querystring .= "WHERE (1 = 1) ";
            $vwuds_listobject->querystring .= " AND \"USERID\" in ( '5883') ";
            // screen for small subset while testing
            $vwuds_listobject->querystring .= "AND the_geom && (select extent(the_geom) from va_counties where name = 'Albemarle') ";
            //$vwuds_listobject->querystring .= " AND ownerid =1 ";
            $vwuds_listobject->querystring .= " AND the_geom is not null ";
            // specify a numerical limit to the results returned
            $vwuds_listobject->querystring .= " limit 10";
         break;
      }
      $vwuds_listobject->performQuery();
      if ($debug) { 
         $csv .= "Query returned $vwuds_listobject->numrows records: $vwuds_listobject->querystring ";
      } else {
         $csv = '';
         if ($vwuds_listobject->numrows > 0) {
            $header = join($delim, array_keys($vwuds_listobject->queryrecords[0]));
            $csv .= "$header\r\n";
            foreach ($vwuds_listobject->queryrecords as $thisrec) {
               $line = join($delim, array_values($thisrec));
               $csv .= "$line\r\n";
               $item = new FeedItem();
               $item->additionalElements = $thisrec;
               $rss->addItem($item);
            }
            $xml = $rss->createFeed("2.0");
         } else {
            $csv .= "Query returned 0 records ";
         }
      }
   }
   break;
}

switch ($output_type) {

   case 'csv':
   print($csv);
   break;
   
   default;
   error_log("Returning XML Feed");
   print("$xml");
   break;
}

?>