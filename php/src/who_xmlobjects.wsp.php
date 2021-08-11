<?php

if (!isset($who_xmlobjects)) {
   $who_xmlobjects = array();
}
if (!isset($serializer)) {
   error_log("Creating Serializer Object<br>");
   $serializer = new XML_Serializer();
}
include_once('lib_wooomm.wsp.php');
error_log("Creating USGSArima<br>");
error_reporting(E_ALL);


error_log("Creating waterSupplyModelNode<br>");
#print("Creating Object<br>");
$obj = new waterSupplyModelNode;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['waterSupplyModelNode']['xml'] = $xml;
   $who_xmlobjects['waterSupplyModelNode']['type'] = '3'; 
   $who_xmlobjects['waterSupplyModelNode']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['waterSupplyModelNode']['name'] = 'Water Supply Model Element';
   $who_xmlobjects['waterSupplyModelNode']['description'] = 'An object that can be used to assemble and run individual hydrologic model components in a concerted fashion.  Includes some pre-determined properties such as Qin, Qout and storage to facilitate linakges';
}

#print("Creating Object<br>");
$obj = new wsp_LUBasedProjection;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_LUBasedProjection']['xml'] = $xml;
   $who_xmlobjects['wsp_LUBasedProjection']['type'] = '1'; 
   $who_xmlobjects['wsp_LUBasedProjection']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_LUBasedProjection']['name'] = 'Demand Projection: Land Use';
   $who_xmlobjects['wsp_LUBasedProjection']['description'] = 'Object to create a simple land-use based demand projection.';
}

error_log("Creating wsp_conservation<br>");
#print("Creating Object<br>");
$obj = new wsp_conservation;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_conservation']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_conservation']['type'] = '2'; 
   $who_xmlobjects['wsp_conservation']['toolgroup'] = 9;
   $who_xmlobjects['wsp_conservation']['name'] = 'wsp_conservation';
   $who_xmlobjects['wsp_conservation']['parent'] = array('wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser');
   $who_xmlobjects['wsp_conservation']['parentprops'] = array('status_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_conservation']['description'] = 'Defines Flow-By for this object.';
}


error_log("Creating wsp_1tierflowby<br>");
#print("Creating Object<br>");
$obj = new wsp_1tierflowby;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_1tierflowby']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_1tierflowby']['type'] = '2'; 
   $who_xmlobjects['wsp_1tierflowby']['toolgroup'] = 9;
   $who_xmlobjects['wsp_1tierflowby']['name'] = 'wsp_1tierflowby';
   $who_xmlobjects['wsp_1tierflowby']['parent'] = array('hydroImpoundment','wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser', 'waterSupplyModelNode');
   $who_xmlobjects['wsp_1tierflowby']['parentprops'] = array('tier_var'=>'publicvars','cfb_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_1tierflowby']['description'] = 'Defines Flow-By for this object.';
}


error_log("Creating wsp_flowby<br>");
#print("Creating Object<br>");
$obj = new wsp_flowby;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_flowby']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_flowby']['type'] = '2'; 
   $who_xmlobjects['wsp_flowby']['toolgroup'] = 9;
   $who_xmlobjects['wsp_flowby']['name'] = 'wsp_flowby';
   $who_xmlobjects['wsp_flowby']['parent'] = array('hydroImpoundment','wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser', 'waterSupplyModelNode');
   $who_xmlobjects['wsp_flowby']['parentprops'] = array('cfb_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_flowby']['description'] = 'Defines Flow-By for this object.';
}

#print("Creating Object<br>");
$obj = new droughtMonitor;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['droughtMonitor']['xml'] = $xml;
   $who_xmlobjects['droughtMonitor']['type'] = '1'; 
   $who_xmlobjects['droughtMonitor']['toolgroup'] = 1; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['droughtMonitor']['name'] = 'Drought Monitor Object';
   $who_xmlobjects['droughtMonitor']['description'] = 'Gathers and Analyzes Drought Monitoring Info.';
}



error_log("Creating wsp_PopBasedProjection_VAWC<br>");
#print("Creating Object<br>");
$obj = new wsp_PopBasedProjection_VAWC;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['xml'] = $xml;
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['type'] = '2'; 
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['parentprops'] = array('yearvar'=>'publicvars');
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['parent'] = array('waterSupplyModelNode','waterSupplyElement','wsp_waterUser');
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['name'] = 'Population Projection: VAWC';
   $who_xmlobjects['wsp_PopBasedProjection_VAWC']['description'] = 'Creates a population projection for a given FIPS in the Commonwealth of Virginia - Weldon Cooper methods';
}

error_log("Creating waterSupplyElement<br>");
#print("Creating Object<br>");
$obj = new waterSupplyElement;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['waterSupplyElement']['xml'] = $xml;
   $who_xmlobjects['waterSupplyElement']['type'] = '3'; 
   $who_xmlobjects['waterSupplyElement']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['waterSupplyElement']['name'] = 'Water Supply Model Node';
   $who_xmlobjects['waterSupplyElement']['description'] = 'An object that can be used to assemble and run individual hydrologic model components in a concerted fashion.  Includes some pre-determined properties such as Qin, Qout and storage to facilitate linakges';
}


#print("Creating Object<br>");
$obj = new wsp_waterUser;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_waterUser']['xml'] = $xml;
   $who_xmlobjects['wsp_waterUser']['type'] = '1'; 
   $who_xmlobjects['wsp_waterUser']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_waterUser']['name'] = 'Water User Class';
   $who_xmlobjects['wsp_waterUser']['description'] = 'Object to hold water user data, and for simulating water use.  Types include Public Water Supply, Commercial, Manufacturing, Agricultural, Irrigation, etc.';
}


#print("Creating Object<br>");
$obj = new wsp_vpdesvwuds;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_vpdesvwuds']['xml'] = $xml;
   $who_xmlobjects['wsp_vpdesvwuds']['type'] = '1'; 
   $who_xmlobjects['wsp_vpdesvwuds']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_vpdesvwuds']['name'] = 'Water Withdrawal/Discharge Class';
   $who_xmlobjects['wsp_vpdesvwuds']['description'] = 'Object to hold water withdrawal and discharge data queried from the VWUDS and VPDES databases, and for simulating water use.  Types include Public Water Supply, Commercial, Manufacturing, Agricultural, Irrigation, etc. ';
}

#print("Creating Object<br>");
$obj = new wsp_VWUDSData;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_VWUDSData']['xml'] = $xml;
   $who_xmlobjects['wsp_VWUDSData']['type'] = '1'; 
   $who_xmlobjects['wsp_VWUDSData']['toolgroup'] = 9;
   $who_xmlobjects['wsp_VWUDSData']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['wsp_VWUDSData']['parent'] = array('dataConnectionObject','XMLDataConnection');
   $who_xmlobjects['wsp_VWUDSData']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_VWUDSData']['name'] = 'VWUDS Water Use Data Connection';
   $who_xmlobjects['wsp_VWUDSData']['description'] = 'Object to hold water use data from VWUDS database';
}

?>
