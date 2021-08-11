<?php

$who_xmlobjects = array();
error_log("Creating Serializer Object<br>");
$serializer = new XML_Serializer();
error_log("Creating USGSGageObject<br>");
$obj = new USGSGageObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['USGSGageObject']['xml'] = $xml;
   $who_xmlobjects['USGSGageObject']['type'] = '4'; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['USGSGageObject']['toolgroup'] = 8;
   $who_xmlobjects['USGSGageObject']['geomtype'] = 3;
   $who_xmlobjects['USGSGageObject']['name'] = 'USGS Gage Time Series';
   # properties to return to child object for use in select lists, etc.
   $who_xmlobjects['USGSGageObject']['description'] = 'Historical stream flow, groundwater, or reservoir data, including quantity and quality, retrieved from the USGS NWIS systems.';
}

error_log("Creating USGSSyntheticRecord<br>");
$obj = new USGSSyntheticRecord;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['USGSSyntheticRecord']['xml'] = $xml;
   $who_xmlobjects['USGSSyntheticRecord']['type'] = '1'; 
   $who_xmlobjects['USGSSyntheticRecord']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['USGSSyntheticRecord']['name'] = 'USGS Synthetic Flow Record';
   # properties to return to child object for use in select lists, etc.
   $who_xmlobjects['USGSSyntheticRecord']['description'] = 'Synthetic flow calclated from a continous gage based on power function.';
}

error_log("Creating NOAADataObject<br>");

# NOAADataObject
$obj = new NOAADataObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['NOAADataObject']['xml'] = $xml;
   $who_xmlobjects['NOAADataObject']['type'] = '4'; 
   $who_xmlobjects['NOAADataObject']['toolgroup'] = 8;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['NOAADataObject']['name'] = 'NOAA Data Object';
   # properties to return to child object for use in select lists, etc.
   $who_xmlobjects['NOAADataObject']['description'] = 'Generic component for retrieving NOAA data for predicted meteorological values, stream predictions, and historical meteorological data.';
}


#print("Creating Object<br>");
error_log("Creating timeSeriesFile<br>");
$obj = new timeSeriesFile;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['timeSeriesFile']['xml'] = $xml;
   $who_xmlobjects['timeSeriesFile']['type'] = '1'; 
   $who_xmlobjects['timeSeriesFile']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['timeSeriesFile']['name'] = 'Time Series File';
   $who_xmlobjects['timeSeriesFile']['description'] = 'Generic File-based Time Series Input.';
}


error_log("Creating HSPFContainer<br>");
#print("Creating Object<br>");
$obj = new HSPFContainer;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['HSPFContainer']['xml'] = $xml;
   $who_xmlobjects['HSPFContainer']['type'] = '1'; 
   $who_xmlobjects['HSPFContainer']['toolgroup'] = 2;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['HSPFContainer']['name'] = 'HSPF Container';
   $who_xmlobjects['HSPFContainer']['description'] = 'Wrapper around an HSPF model file, has the ability to parse model output files (plotgen), write model input files (mustins), and initiate a model execution if needed.';
}

error_log("Creating CBPModelContainer<br>");
#print("Creating Object<br>");
$obj = new CBPModelContainer;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['CBPModelContainer']['xml'] = $xml;
   $who_xmlobjects['CBPModelContainer']['type'] = '3'; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['CBPModelContainer']['toolgroup'] = 2;
   $who_xmlobjects['CBPModelContainer']['name'] = 'CBP HSPF Model Container';
   $who_xmlobjects['CBPModelContainer']['description'] = 'Wrapper around an CBP HSPF model file(s), will load all modeling segments above the specified outlet river UCI, and create sub-components for each.';
}

error_log("Creating CBPDataConnection<br>");
#print("Creating Object<br>");
$obj = new CBPDataConnection;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['CBPDataConnection']['xml'] = $xml;
   $who_xmlobjects['CBPDataConnection']['type'] = '1'; 
   $who_xmlobjects['CBPDataConnection']['toolgroup'] = 2;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['CBPDataConnection']['name'] = 'CBP Riverseg Data';
   $who_xmlobjects['CBPDataConnection']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['CBPDataConnection']['parent'] = array('dataConnectionObject');
   $who_xmlobjects['CBPDataConnection']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['CBPDataConnection']['description'] = 'Widget used top access remotely available CBP model outputs for use as a time series input to a WOOOMM simulation.';
}

error_log("Creating CBPLandDataConnection<br>");
#print("Creating Object<br>");
$obj = new CBPLandDataConnection;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['CBPLandDataConnection']['xml'] = $xml;
   $who_xmlobjects['CBPLandDataConnection']['type'] = '1'; 
   $who_xmlobjects['CBPLandDataConnection']['toolgroup'] = 2;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['CBPLandDataConnection']['name'] = 'CBP Landseg Data';
   $who_xmlobjects['CBPLandDataConnection']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['CBPLandDataConnection']['parent'] = array('dataConnectionObject');
   $who_xmlobjects['CBPLandDataConnection']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['CBPLandDataConnection']['description'] = 'Widget used top access remotely available CBP model outputs for use as a time series input to a WOOOMM simulation.';
}



error_log("Creating CBPLandDataConnectionFile<br>");
#print("Creating Object<br>");
$obj = new CBPLandDataConnectionFile;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['CBPLandDataConnectionFile']['xml'] = $xml;
   $who_xmlobjects['CBPLandDataConnectionFile']['type'] = '1'; 
   $who_xmlobjects['CBPLandDataConnectionFile']['toolgroup'] = 2;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['CBPLandDataConnectionFile']['name'] = 'CBP6 Landseg Data';
   $who_xmlobjects['CBPLandDataConnectionFile']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['CBPLandDataConnectionFile']['parent'] = array('dataConnectionObject');
   $who_xmlobjects['CBPLandDataConnectionFile']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['CBPLandDataConnectionFile']['description'] = 'Widget used to access Phase 6 CBP model outputs for use as a time series input to a WOOOMM simulation.';
}

#print("Creating Object<br>");
$obj = new CBPDataInsert;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['CBPDataInsert']['xml'] = $xml;
   $who_xmlobjects['CBPDataInsert']['type'] = '2'; 
   $who_xmlobjects['CBPDataInsert']['toolgroup'] = 2; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   # set this if it should only show under certain types of objects
   $who_xmlobjects['CBPDataInsert']['name'] = 'Model Data Insert';
   # can only be a child of the dataConnectionObject
   $who_xmlobjects['CBPDataInsert']['parent'] = array('CBPDataConnection');
   $who_xmlobjects['CBPDataInsert']['parentprops'] = array('col_name'=>'privatevars');
   $who_xmlobjects['CBPDataInsert']['description'] = 'Allows users to store data in the CBP model data output table.';
}


#print("Creating Object<br>");
$obj = new CBPLandDataConnection_sub;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['CBPLandDataConnection_sub']['xml'] = $xml;
   $who_xmlobjects['CBPLandDataConnection_sub']['type'] = '2'; 
   $who_xmlobjects['CBPLandDataConnection_sub']['toolgroup'] = 2; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   # set this if it should only show under certain types of objects
   $who_xmlobjects['CBPLandDataConnection_sub']['name'] = 'CBP Land Use Runoff Data';
   # can only be a child of the dataConnectionObject
   $who_xmlobjects['CBPLandDataConnection_sub']['parent'] = array('waterSupplyElement','waterSupplyModelNode');
   $who_xmlobjects['CBPLandDataConnection_sub']['parentprops'] = array('col_name'=>'privatevars', 'lat_dd'=>'publicvars', 'lon_dd'=>'publicvars');
   $who_xmlobjects['CBPLandDataConnection_sub']['description'] = 'Accesses runoff data from CBP watershed model.';
}

error_log("Creating HSPFPlotgen<br>");
$obj = new HSPFPlotgen;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['HSPFPlotgen']['xml'] = $xml;
   $who_xmlobjects['HSPFPlotgen']['type'] = '2'; 
   $who_xmlobjects['HSPFPlotgen']['toolgroup'] = 2;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['HSPFPlotgen']['name'] = 'HSPF Plotgen Time Series';
   $who_xmlobjects['HSPFPlotgen']['parent'] = array('HSPFContainer');
   # properties to return to child object for use in select lists, etc.
   $who_xmlobjects['HSPFPlotgen']['parentprops'] = array('plotgenoutput'=>'plotgen');
   $who_xmlobjects['HSPFPlotgen']['description'] = 'Access data from a Plotgen on the parent HSPF Container.';
}

error_log("Creating WDMDSNaccessor<br>");
$obj = new WDMDSNaccessor;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['WDMDSNaccessor']['xml'] = $xml;
   $who_xmlobjects['WDMDSNaccessor']['type'] = '2'; 
   $who_xmlobjects['WDMDSNaccessor']['toolgroup'] = 2;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['WDMDSNaccessor']['name'] = 'HSPF WDM DSN';
   $who_xmlobjects['WDMDSNaccessor']['parent'] = array('HSPFContainer');
   # properties to return to child object for use in select lists, etc.
   $who_xmlobjects['WDMDSNaccessor']['parentprops'] = array('wdmoutput'=>'wdm');
   $who_xmlobjects['WDMDSNaccessor']['description'] = 'Access data from a WDM on the parent HSPF Container.';
}

error_log("Creating reverseFlowObject<br>");
#print("Creating Object<br>");
$obj = new reverseFlowObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['reverseFlowObject']['xml'] = $xml;
   $who_xmlobjects['reverseFlowObject']['type'] = '2'; 
   $who_xmlobjects['reverseFlowObject']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['reverseFlowObject']['parentprops'] = array('Qvar'=>'publicvars');
   $who_xmlobjects['reverseFlowObject']['name'] = 'Reach Reverse Flow Input';
   $who_xmlobjects['reverseFlowObject']['parent'] = array('channelObject','USGSChannelGeomObject', 'USGSGageObject', 'hydroObject', 'waterSupplyElement', 'waterSupplyModelNode');
   $who_xmlobjects['reverseFlowObject']['description'] = 'Takes one or more flow time series as input, area weights AND temporally shifts (to obtain the flow that must have entered the input reaches in order to produce the observed outflow), to produce a synthetic hydrograph for an ungaged area.';
}

error_log("Creating USGSRecharge<br>");
#print("Creating Object<br>");
$obj = new USGSRecharge;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['USGSRecharge']['xml'] = $xml;
   $who_xmlobjects['USGSRecharge']['type'] = '2'; 
   $who_xmlobjects['USGSRecharge']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['USGSRecharge']['parentprops'] = array('q_var'=>'publicvars');
   $who_xmlobjects['USGSRecharge']['name'] = 'Recharge Flow Baseflow Predictor';
   $who_xmlobjects['USGSRecharge']['parent'] = array('channelObject','USGSChannelGeomObject', 'USGSGageObject', 'hydroObject', 'waterSupplyElement', 'waterSupplyModelNode', 'hydroImpoundment');
   $who_xmlobjects['USGSRecharge']['description'] = 'Takes one or more flow time series as input, area weights AND temporally shifts (to obtain the flow that must have entered the input reaches in order to produce the observed outflow), to produce a synthetic hydrograph for an ungaged area.';
}

error_log("Creating flowTransformer<br>");
#print("Creating Object<br>");
$obj = new flowTransformer;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['flowTransformer']['xml'] = $xml;
   $who_xmlobjects['flowTransformer']['type'] = '1'; 
   $who_xmlobjects['flowTransformer']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['flowTransformer']['name'] = 'Synthetic Hydrograph';
   $who_xmlobjects['flowTransformer']['description'] = 'Takes one or more flow time series as input, and area weights flows to produce a synthetic hydrograph for an ungaged area.';
}

error_log("Creating HabitatSuitabilityObject<br>");
#print("Creating Object<br>");
$obj = new HabitatSuitabilityObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['HabitatSuitabilityObject']['xml'] = $xml;
   $who_xmlobjects['HabitatSuitabilityObject']['type'] = '1'; 
   $who_xmlobjects['HabitatSuitabilityObject']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['HabitatSuitabilityObject']['name'] = 'Habitat Suitability Object';
   $who_xmlobjects['HabitatSuitabilityObject']['description'] = 'Container for habitat suitability object, lookup tables, and equations must be added in order to use this.';
}

error_log("Creating HabitatSuitabilityObject_NWRC<br>");
#print("Creating Object<br>");
$obj = new HabitatSuitabilityObject_NWRC;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['HabitatSuitabilityObject_NWRC']['xml'] = $xml;
   $who_xmlobjects['HabitatSuitabilityObject_NWRC']['type'] = '1'; 
   $who_xmlobjects['HabitatSuitabilityObject_NWRC']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['HabitatSuitabilityObject_NWRC']['name'] = 'National Wetlands Research Center - Habitat Suitability Object';
   $who_xmlobjects['HabitatSuitabilityObject_NWRC']['description'] = 'Container for habitat suitability object, lookup tables, and equations must be added in order to use this.';
}

error_log("Creating HSI_NWRC_species<br>");
#print("Creating Object<br>");
$obj = new HSI_NWRC_species;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['HSI_NWRC_species']['xml'] = $xml;
   $who_xmlobjects['HSI_NWRC_species']['type'] = '2'; 
   $who_xmlobjects['HSI_NWRC_species']['parent'] = array('HabitatSuitabilityObject_NWRC');
   $who_xmlobjects['HSI_NWRC_species']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['HSI_NWRC_species']['name'] = 'National Wetlands Research Center - Habitat Suitability Object';
   $who_xmlobjects['HSI_NWRC_species']['description'] = 'Container for habitat suitability object, lookup tables, and equations must be added in order to use this.';
}

error_log("Creating HSI_NWRC_american_shad<br>");
#print("Creating Object<br>");
$obj = new HSI_NWRC_american_shad;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['HSI_NWRC_american_shad']['xml'] = $xml;
   $who_xmlobjects['HSI_NWRC_american_shad']['type'] = '2'; 
   $who_xmlobjects['HSI_NWRC_american_shad']['parent'] = array('HabitatSuitabilityObject_NWRC');
   $who_xmlobjects['HSI_NWRC_american_shad']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['HSI_NWRC_american_shad']['name'] = 'HSI American Shad';
   $who_xmlobjects['HSI_NWRC_american_shad']['description'] = 'NWRC American Shad HSI.';
}


error_log("Creating PopulationGenerationObject<br>");
#print("Creating Object<br>");
$obj = new PopulationGenerationObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['PopulationGenerationObject']['xml'] = $xml;
   $who_xmlobjects['PopulationGenerationObject']['type'] = '1'; 
   $who_xmlobjects['PopulationGenerationObject']['toolgroup'] = 3;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['PopulationGenerationObject']['name'] = 'Generation-based Population Object';
   $who_xmlobjects['PopulationGenerationObject']['description'] = 'A dynamic generation based population component for use with year-class type analyses.  Will spawn child object each time it reproduces.';
}


error_log("Creating surfaceObject<br>");
#print("Creating Object<br>");
$obj = new surfaceObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['surfaceObject']['xml'] = $xml;
   $who_xmlobjects['surfaceObject']['type'] = '1'; 
   $who_xmlobjects['surfaceObject']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['surfaceObject']['name'] = 'Surface Object';
   $who_xmlobjects['surfaceObject']['description'] = 'Simulated infiltration and runoff when given rainfall, and soil properties as input.';
}

error_log("Creating storageObject<br>");
#print("Creating Object<br>");
$obj = new storageObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['storageObject']['xml'] = $xml;
   $who_xmlobjects['storageObject']['type'] = '1'; 
   $who_xmlobjects['storageObject']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['storageObject']['name'] = 'Lake/Reservoir';
   $who_xmlobjects['storageObject']['description'] = 'Storage object, for routing withdrawals to simulate complex river/reservoir system.';
}

error_log("Creating channelObject<br>");
#print("Creating Object<br>");
$obj = new channelObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['channelObject']['xml'] = $xml;
   $who_xmlobjects['channelObject']['type'] = '1';  # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['channelObject']['toolgroup'] = 1;
   $who_xmlobjects['channelObject']['name'] = 'Channel Object';
   $who_xmlobjects['channelObject']['description'] = 'Used to model streamflow in a section of reach, can be connected to other objects to simulate the effects of withdrawals on water levels and aquatic life.';
}

error_log("Creating hydroImpoundment<br>");
#print("Creating Object<br>");
$obj = new hydroImpoundment;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['hydroImpoundment']['xml'] = $xml;
   $who_xmlobjects['hydroImpoundment']['type'] = '1'; 
   $who_xmlobjects['hydroImpoundment']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['hydroImpoundment']['name'] = 'Impoundment (Lake/Reservoir/Pond)';
   $who_xmlobjects['hydroImpoundment']['description'] = 'Storage object, for routing withdrawals to simulate complex river/reservoir system.';
}

error_log("Creating USGSChannelGeomObject<br>");
#print("Creating Object<br>");
$obj = new USGSChannelGeomObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['USGSChannelGeomObject']['xml'] = $xml;
   $who_xmlobjects['USGSChannelGeomObject']['type'] = '1'; 
   $who_xmlobjects['USGSChannelGeomObject']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['USGSChannelGeomObject']['name'] = 'USGS Physiography-based Channel Object';
   $who_xmlobjects['USGSChannelGeomObject']['description'] = 'Uses a relationship between physiographic province and drainage area to compute hydraulic characteristics, Z and base width.';
}

error_log("Creating USGSChannelGeomObject_sub<br>");
#print("Creating Object<br>");
$obj = new USGSChannelGeomObject_sub;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['USGSChannelGeomObject_sub']['xml'] = $xml;
   $who_xmlobjects['USGSChannelGeomObject_sub']['type'] = '2'; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['USGSChannelGeomObject_sub']['toolgroup'] = 1;
   $who_xmlobjects['USGSChannelGeomObject_sub']['name'] = 'USGS Physiography-based Channel Object';
   $who_xmlobjects['USGSChannelGeomObject_sub']['parent'] =  array('channelObject','USGSChannelGeomObject', 'USGSGageObject', 'hydroObject', 'waterSupplyElement', 'waterSupplyModelNode');
   $who_xmlobjects['USGSChannelGeomObject_sub']['parentprops'] = array('r_var'=>'publicvars','q_var'=>'publicvars','w_var'=>'publicvars');
   $who_xmlobjects['USGSChannelGeomObject_sub']['description'] = 'Uses a relationship between physiographic province and drainage area to compute hydraulic characteristics, Z and base width.';
}

error_log("Creating pumpObject<br>");
#print("Creating Object<br>");
$obj = new pumpObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['pumpObject']['xml'] = $xml;
   $who_xmlobjects['pumpObject']['type'] = '1'; 
   $who_xmlobjects['pumpObject']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['pumpObject']['name'] = 'Water Withdrawal';
   $who_xmlobjects['pumpObject']['description'] = 'Rule-based withdrawal from a stream or reservoir.';
}


error_log("Creating blankShell<br>");
#print("Creating Object<br>");
$obj = new blankShell;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['blankShell']['xml'] = $xml;
   $who_xmlobjects['blankShell']['type'] = '1'; 
   $who_xmlobjects['blankShell']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['blankShell']['name'] = 'Shell Object ';
   $who_xmlobjects['blankShell']['description'] = 'Generic modeling object, with no internal methods.  All methods must be defined by the user.';
}

error_log("Creating Equation<br>");
#print("Creating Object<br>");
$obj = new Equation;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['Equation']['xml'] = $xml;
   $who_xmlobjects['Equation']['type'] = '2';  # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['Equation']['toolgroup'] = 7;
   $who_xmlobjects['Equation']['name'] = 'Equation';
   $who_xmlobjects['Equation']['description'] = 'Performs mathematical evaluation for models (such as Habitat Suitability), evaluates using algebraic order of operations, including parentheses, possesses the basic arithmetic operators [+ - / *], and the pow(base,exponent) function.';
}

error_log("Creating dataConnectionSubObject<br>");
#print("Creating Object<br>");
$obj = new dataConnectionSubObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['dataConnectionSubObject']['xml'] = $xml;
   $who_xmlobjects['dataConnectionSubObject']['type'] = '2';  # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['dataConnectionSubObject']['toolgroup'] = 7;
   $who_xmlobjects['dataConnectionSubObject']['name'] = 'dataConnectionSubObject';
   $who_xmlobjects['dataConnectionSubObject']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['dataConnectionSubObject']['description'] = 'Performs remote database access.';
}

error_log("Creating textField<br>");
#print("Creating Object<br>");
$obj = new textField;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['textField']['xml'] = $xml;
   $who_xmlobjects['textField']['type'] = '2'; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['textField']['toolgroup'] = 6;
   $who_xmlobjects['textField']['name'] = 'textField';
   $who_xmlobjects['textField']['description'] = 'Generic string storage field';
}

error_log("Creating dataMatrix<br>");
#print("Creating Object<br>");
$obj = new dataMatrix;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['dataMatrix']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['dataMatrix']['type'] = '2'; 
   $who_xmlobjects['dataMatrix']['toolgroup'] = 6;
   $who_xmlobjects['dataMatrix']['name'] = 'dataMatrix';
   $who_xmlobjects['dataMatrix']['parentprops'] = array('keycol1'=>'publicvars', 'keycol2'=>'publicvars');
   $who_xmlobjects['dataMatrix']['description'] = 'Permits the use of multiple dimensional data.';
}

error_log("Creating runVariableStorageObject<br>");
#print("Creating Object<br>");
$obj = new runVariableStorageObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['runVariableStorageObject']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['runVariableStorageObject']['type'] = '2'; 
   $who_xmlobjects['runVariableStorageObject']['toolgroup'] = 6;
   $who_xmlobjects['runVariableStorageObject']['name'] = 'runVariableStorageObject';
   $who_xmlobjects['runVariableStorageObject']['parentprops'] = array('dataname'=>'publicvars');
   $who_xmlobjects['runVariableStorageObject']['description'] = 'Stashes data in scen_model_run_data.';
}

error_log("Creating Statistic<br>");
#print("Creating Object<br>");
$obj = new Statistic;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['Statistic']['xml'] = $xml;
   $who_xmlobjects['Statistic']['type'] = '2'; 
   $who_xmlobjects['Statistic']['toolgroup'] = 6;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['Statistic']['name'] = 'Statistic';
   $who_xmlobjects['Statistic']['parentprops'] = array('vars'=>'publicvars');
   $who_xmlobjects['Statistic']['description'] = 'Performs statistical evaluation for of a set of numbers, added in csv notation.';
}


error_log("Creating modelContainer<br>");
#print("Creating Object<br>");
$obj = new modelContainer;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['modelContainer']['xml'] = $xml;
   $who_xmlobjects['modelContainer']['type'] = '3'; 
   $who_xmlobjects['modelContainer']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['modelContainer']['name'] = 'Model Container';
   $who_xmlobjects['modelContainer']['description'] = 'An object that can be used to assemble and run individual model components in a concerted fashion.';
}


error_log("Creating hydroContainer<br>");
#print("Creating Object<br>");
$obj = new hydroContainer;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['hydroContainer']['xml'] = $xml;
   $who_xmlobjects['hydroContainer']['type'] = '3'; 
   $who_xmlobjects['hydroContainer']['toolgroup'] = 1;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['hydroContainer']['name'] = 'Hydrology Model Container';
   $who_xmlobjects['hydroContainer']['description'] = 'An object that can be used to assemble and run individual hydrologic model components in a concerted fashion.  Includes some pre-determined properties such as Qin, Qout and storage to facilitate linakges';
}

error_log("Creating lookupObject<br>");
#print("Creating Object<br>");
$obj = new lookupObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['lookupObject']['xml'] = $xml;
   $who_xmlobjects['lookupObject']['type'] = '2'; 
   $who_xmlobjects['lookupObject']['toolgroup'] = 7;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['lookupObject']['name'] = 'Lookup Object';
   $who_xmlobjects['lookupObject']['parentprops'] = array('input'=>'publicvars');
   $who_xmlobjects['lookupObject']['description'] = 'Performs a lookup into one or more tables for a single variable input (+ time).  If time inputs are specified, the lookup is turned on/off based on the value fo the simulation time.  Interpolation for a non-exact match can be enabled or disabled, and can be linear, next closest value, or previous closest value.';
}

error_log("Creating reportObject<br>");
#print("Creating Object<br>");
$obj = new reportObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['reportObject']['xml'] = $xml;
   $who_xmlobjects['reportObject']['type'] = '1'; 
   $who_xmlobjects['reportObject']['toolgroup'] = 5;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['reportObject']['name'] = 'Report Object';
   $who_xmlobjects['reportObject']['description'] = 'Pulls information from other modeling objects to assemble reports and graphs.';
}

error_log("Creating graphObject<br>");
#print("Creating Object<br>");
$obj = new graphObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['graphObject']['xml'] = $xml;
   $who_xmlobjects['graphObject']['type'] = '1'; 
   $who_xmlobjects['graphObject']['toolgroup'] = 5;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['graphObject']['name'] = 'Graph Object';
   $who_xmlobjects['graphObject']['description'] = 'Pulls information from other modeling objects to assemble reports and graphs.';
}

#print("Creating Object<br>");
$obj = new giniGraph;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['giniGraph']['xml'] = $xml;
   $who_xmlobjects['giniGraph']['type'] = '1'; 
   $who_xmlobjects['giniGraph']['toolgroup'] = 5;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['giniGraph']['name'] = 'Gini Graph Object';
   $who_xmlobjects['giniGraph']['description'] = 'Assembles and Calculates Gini coefficients for each graph component.';
}

#print("Creating Object<br>");
$obj = new flowDurationGraph;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['flowDurationGraph']['xml'] = $xml;
   $who_xmlobjects['flowDurationGraph']['type'] = '1'; 
   $who_xmlobjects['flowDurationGraph']['toolgroup'] = 5;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['flowDurationGraph']['name'] = 'Flow Duration Graph Object';
   $who_xmlobjects['flowDurationGraph']['description'] = 'Assembles and Calculates Flow Duration Curves for each graph component.';
}


#print("Creating Object<br>");
$obj = new graphComponent;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['graphComponent']['xml'] = $xml;
   $who_xmlobjects['graphComponent']['type'] = '2'; 
   $who_xmlobjects['graphComponent']['toolgroup'] = 5;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['graphComponent']['parent'] = array('graphObject','giniGraph','flowDurationGraph'); # set this if it should only show under certain types of objects
   $who_xmlobjects['graphComponent']['name'] = 'Graph Component';
   $who_xmlobjects['graphComponent']['parentprops'] = array('xcol'=>'publicvars', 'ycol'=>'publicvars');
   $who_xmlobjects['graphComponent']['description'] = 'Pulls information from other modeling objects to assemble reports and graphs.';
}

#print("Creating Object<br>");
$obj = new queryWizardComponent;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['queryWizardComponent']['xml'] = $xml;
   $who_xmlobjects['queryWizardComponent']['type'] = '2'; 
   $who_xmlobjects['queryWizardComponent']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['queryWizardComponent']['name'] = 'Query Wizard Component';
   $who_xmlobjects['queryWizardComponent']['parentprops'] = array('qcols'=>'publicvars','wcols'=>'publicvars','ocols'=>'publicvars');
   $who_xmlobjects['queryWizardComponent']['description'] = 'Pulls information from other modeling objects to assemble reports and permits query access.';
}

#print("Creating Object<br>");
$obj = new broadCastObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['broadCastObject']['xml'] = $xml;
   $who_xmlobjects['broadCastObject']['type'] = '2'; 
   $who_xmlobjects['broadCastObject']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['broadCastObject']['name'] = 'Broadcast Component';
   $who_xmlobjects['broadCastObject']['parentprops'] = array('broadcast_varname'=>'publicvars','local_varname'=>'publicvars');
   $who_xmlobjects['broadCastObject']['description'] = 'Pulls information from other modeling objects to assemble reports and permits query access.';
}

#print("Creating Object<br>");
$obj = new dataConnectionObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['dataConnectionObject']['xml'] = $xml;
   $who_xmlobjects['dataConnectionObject']['type'] = '1'; 
   $who_xmlobjects['dataConnectionObject']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['dataConnectionObject']['name'] = 'Data Connection Object';
   $who_xmlobjects['dataConnectionObject']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['dataConnectionObject']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['dataConnectionObject']['description'] = 'Pulls information from other modeling objects to assemble reports and permits query access.';
}

#print("Creating Object<br>");
$obj = new noaaGriddedPrecip;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['noaaGriddedPrecip']['xml'] = $xml;
   $who_xmlobjects['noaaGriddedPrecip']['type'] = '1'; 
   $who_xmlobjects['noaaGriddedPrecip']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['noaaGriddedPrecip']['name'] = 'NOAA Gridded Precip Accessor';
   $who_xmlobjects['noaaGriddedPrecip']['parent'] = array('dataConnectionObject');
   $who_xmlobjects['noaaGriddedPrecip']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['noaaGriddedPrecip']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['noaaGriddedPrecip']['description'] = 'Allows user to connect and query a gridded precipitation data store.';
}

#print("Creating Object<br>");
$obj = new genericLandSurface;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['genericLandSurface']['xml'] = $xml;
   $who_xmlobjects['genericLandSurface']['type'] = '1'; 
   $who_xmlobjects['genericLandSurface']['toolgroup'] = 10;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['genericLandSurface']['name'] = 'Land Surface Object';
   $who_xmlobjects['genericLandSurface']['parentprops'] = array();
   $who_xmlobjects['genericLandSurface']['localprops'] = array();
   $who_xmlobjects['genericLandSurface']['description'] = 'Simple model of surface object for impervious or simple pervious land simulation.';
}

#print("Creating Object<br>");
$obj = new genericDwelling;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['genericDwelling']['xml'] = $xml;
   $who_xmlobjects['genericDwelling']['type'] = '2'; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['genericDwelling']['toolgroup'] = 10;
   $who_xmlobjects['genericDwelling']['name'] = 'Dwelling';
   //$who_xmlobjects['genericDwelling']['parent'] = array('hydroContainer'); # set this if it should only show under certain types of objects
   $who_xmlobjects['genericDwelling']['parentprops'] = array();
   $who_xmlobjects['genericDwelling']['localprops'] = array();
   $who_xmlobjects['genericDwelling']['description'] = 'Simple model of dwelling, with occupants and water uses.';
}

error_log("Creating hydroTank<br>");
#print("Creating Object<br>");
$obj = new hydroTank;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['hydroTank']['xml'] = $xml;
   $who_xmlobjects['hydroTank']['type'] = '2'; 
   $who_xmlobjects['hydroTank']['toolgroup'] = 10;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['hydroTank']['name'] = 'Cistern / Storage Tank';
   $who_xmlobjects['hydroTank']['parentprops'] = array('Qin'=>'publicvars','precip'=>'publicvars','demand'=>'publicvars','pan_evap'=>'publicvars');
   $who_xmlobjects['hydroTank']['description'] = 'Storage tank, small units (Gallons, GPM, etc.).  Can be open-air (precip and evap) or closed (no precip/evap).';
}


#print("Creating Object<br>");
$obj = new XMLDataConnection;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['XMLDataConnection']['xml'] = $xml;
   $who_xmlobjects['XMLDataConnection']['type'] = '1'; 
   $who_xmlobjects['XMLDataConnection']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['XMLDataConnection']['name'] = 'XML Data Connection Object';
   $who_xmlobjects['XMLDataConnection']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['XMLDataConnection']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['XMLDataConnection']['description'] = 'Pulls information from Remote XML data sources such as RSS and permits query access.';
}

#print("Creating Object<br>");
$obj = new RSSDataConnection;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['RSSDataConnection']['xml'] = $xml;
   $who_xmlobjects['RSSDataConnection']['type'] = '1'; 
   $who_xmlobjects['RSSDataConnection']['toolgroup'] = 4;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp# set this if it should only show under certain types of objects
   $who_xmlobjects['RSSDataConnection']['name'] = 'XML Data Connection Object';
   $who_xmlobjects['RSSDataConnection']['parentprops'] = array('tablecols'=>'publicvars','lon_col'=>'publicvars');
   $who_xmlobjects['RSSDataConnection']['localprops'] = array('lon_col'=>'privatevars','lat_col'=>'privatevars','datecolumn'=>'privatevars','yearcolumn'=>'privatevars','monthcolumn'=>'privatevars','daycolumn'=>'privatevars');
   $who_xmlobjects['RSSDataConnection']['description'] = 'Pulls information from Remote RSS data sources and permits query access.';
}

#print("Creating Object<br>");
$obj = new dataConnectionTransform;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['dataConnectionTransform']['xml'] = $xml;
   $who_xmlobjects['dataConnectionTransform']['type'] = '2'; 
   $who_xmlobjects['dataConnectionTransform']['toolgroup'] = 4; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   # set this if it should only show under certain types of objects
   $who_xmlobjects['dataConnectionTransform']['name'] = 'Query Column Transformation';
   # can only be a child of the dataConnectionObject
   $who_xmlobjects['dataConnectionTransform']['parent'] = array('dataConnectionObject');
   $who_xmlobjects['dataConnectionTransform']['parentprops'] = array('col_name'=>'privatevars');
   $who_xmlobjects['dataConnectionTransform']['description'] = 'Creates a summary of query data visible to other components.  This operation is performed prior to model run.';
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
} else {
   error_log("Could not Instantiate Class wsp_PopBasedProjection_VAWC ");
}



error_log("Creating dynamicWaterUsers<br>");
#print("Creating Object<br>");
$obj = new dynamicWaterUsers;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['dynamicWaterUsers']['xml'] = $xml;
   $who_xmlobjects['dynamicWaterUsers']['type'] = '2'; 
   $who_xmlobjects['dynamicWaterUsers']['parentprops'] = array('yearvar'=>'publicvars');
   $who_xmlobjects['dynamicWaterUsers']['parent'] = array('waterSupplyModelNode','waterSupplyElement','wsp_waterUser');
   $who_xmlobjects['dynamicWaterUsers']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['dynamicWaterUsers']['name'] = 'Population Projection: VAWC';
   $who_xmlobjects['dynamicWaterUsers']['description'] = 'Creates a population projection for a given FIPS in the Commonwealth of Virginia - Weldon Cooper methods';
} else {
   error_log("Could not Instantiate Class dynamicWaterUsers ");
}




error_log("Creating hydroGrid<br>");
#print("Creating Object<br>");
$obj = new hydroGrid;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['hydroGrid']['xml'] = $xml;
   $who_xmlobjects['hydroGrid']['type'] = '2'; 
   $who_xmlobjects['hydroGrid']['parentprops'] = array('yearvar'=>'publicvars');
   $who_xmlobjects['hydroGrid']['parent'] = array('waterSupplyModelNode','waterSupplyElement','wsp_waterUser', 'hydroImpoundment');
   $who_xmlobjects['hydroGrid']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['hydroGrid']['name'] = 'Population Projection: VAWC';
   $who_xmlobjects['hydroGrid']['description'] = 'Creates a population projection for a given FIPS in the Commonwealth of Virginia - Weldon Cooper methods';
} else {
   error_log("Could not Instantiate Class hydroGrid ");
}


error_log("Creating vwudsUserGroup<br>");
#print("Creating Object<br>");
$obj = new vwudsUserGroup;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['vwudsUserGroup']['xml'] = $xml;
   $who_xmlobjects['vwudsUserGroup']['type'] = '2'; 
   $who_xmlobjects['vwudsUserGroup']['parentprops'] = array('yearvar'=>'publicvars');
   $who_xmlobjects['vwudsUserGroup']['parent'] = array('waterSupplyModelNode','waterSupplyElement','wsp_waterUser');
   $who_xmlobjects['vwudsUserGroup']['toolgroup'] = 9;# type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['vwudsUserGroup']['name'] = 'Water User Query (non-spatial)';
   $who_xmlobjects['vwudsUserGroup']['description'] = 'Creates a connection to a group of existing VWUDS withdrawal objects in the specified model domain.';
} else {
   error_log("Could not Instantiate Class vwudsUserGroup ");
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
   $who_xmlobjects['wsp_flowby']['parent'] = array('hydroImpoundment','wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser', 'waterSupplyElement','waterSupplyModelNode');
   $who_xmlobjects['wsp_flowby']['parentprops'] = array('cfb_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_flowby']['description'] = 'Defines Flow-By for this object.';
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
   $who_xmlobjects['wsp_1tierflowby']['parent'] = array('hydroImpoundment','wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser', 'waterSupplyElement','waterSupplyModelNode');
   $who_xmlobjects['wsp_1tierflowby']['parentprops'] = array('tier_var'=>'publicvars','cfb_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_1tierflowby']['description'] = 'Defines Flow-By for this object.';
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
   $who_xmlobjects['wsp_conservation']['parent'] = array('wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser', 'hydroImpoundment');
   $who_xmlobjects['wsp_conservation']['parentprops'] = array('status_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_conservation']['description'] = 'Defines Flow-By for this object.';
}


error_log("Creating wsp_demand<br>");
#print("Creating Object<br>");
$obj = new wsp_demand;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['wsp_demand']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['wsp_demand']['type'] = '2'; 
   $who_xmlobjects['wsp_demand']['toolgroup'] = 9;
   $who_xmlobjects['wsp_demand']['name'] = 'wsp_demand';
   $who_xmlobjects['wsp_demand']['parent'] = array('wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser', 'hydroImpoundment', 'waterSupplyElement');
   $who_xmlobjects['wsp_demand']['parentprops'] = array('status_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['wsp_demand']['description'] = 'Defines Demand for this object.';
}

error_log("Creating cova_watershedContainerLink<br>");
#print("Creating Object<br>");
$obj = new cova_watershedContainerLink;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['cova_watershedContainerLink']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['cova_watershedContainerLink']['type'] = '2'; 
   $who_xmlobjects['cova_watershedContainerLink']['toolgroup'] = 9;
   $who_xmlobjects['cova_watershedContainerLink']['name'] = 'cova_watershedContainerLink';
   $who_xmlobjects['cova_watershedContainerLink']['parent'] = array('waterSupplyElement', 'waterSupplyModelNode');
   $who_xmlobjects['cova_watershedContainerLink']['parentprops'] = array('status_var'=>'publicvars','custom_cons_var'=>'publicvars');
   $who_xmlobjects['cova_watershedContainerLink']['description'] = 'Defines Demand for this object.';
}

error_log("Creating hydroImpSmall<br>");
#print("Creating Object<br>");
$obj = new hydroImpSmall;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['hydroImpSmall']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['hydroImpSmall']['type'] = '2'; 
   $who_xmlobjects['hydroImpSmall']['toolgroup'] = 9;
   $who_xmlobjects['hydroImpSmall']['name'] = 'hydroImpSmall';
   $who_xmlobjects['hydroImpSmall']['parent'] = array('wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser','waterSupplyElement', 'hydroImpoundment','waterSupplyModelNode');
   $who_xmlobjects['hydroImpSmall']['parentprops'] = array('release'=>'publicvars','Qin'=>'publicvars','et_in'=>'publicvars', 'precip_in'=>'publicvars', 'demand'=>'publicvars', 'refill'=>'publicvars');
   $who_xmlobjects['hydroImpSmall']['description'] = 'Creates a compact storage entity on this object.';
}


error_log("Creating USGSGageSubComp<br>");
#print("Creating Object<br>");
$obj = new USGSGageSubComp;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['USGSGageSubComp']['xml'] = $xml;
   # type 1 - stand-alone object, 2 - sub-component only, 
   #3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['USGSGageSubComp']['type'] = '2'; 
   $who_xmlobjects['USGSGageSubComp']['toolgroup'] = 9;
   $who_xmlobjects['USGSGageSubComp']['name'] = 'USGSGageSubComp';
   $who_xmlobjects['USGSGageSubComp']['parent'] = array('wsp_vpdesvwuds','wsp_VWUDSData','wsp_waterUser','waterSupplyElement','waterSupplyModelNode');
   $who_xmlobjects['USGSGageSubComp']['description'] = 'Creates a USGS Gage Sub-Object.';
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

error_log("Creating stockComponent<br>");
#print("Creating Object<br>");
$obj = new stockComponent;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['stockComponent']['xml'] = $xml;
   $who_xmlobjects['stockComponent']['type'] = '2'; 
   $who_xmlobjects['stockComponent']['toolgroup'] = 3; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['stockComponent']['name'] = 'Stock Component';
   $who_xmlobjects['stockComponent']['parentprops'] = array('inflows'=>'publicvars','outflows'=>'publicvars');
   $who_xmlobjects['stockComponent']['description'] = 'Simple modeling of stocks and flows.';
}

error_log("Creating withdrawalRuleObject<br>");
#print("Creating Object<br>");
$obj = new withdrawalRuleObject;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['withdrawalRuleObject']['xml'] = $xml;
   $who_xmlobjects['withdrawalRuleObject']['type'] = '1'; 
   $who_xmlobjects['withdrawalRuleObject']['toolgroup'] = 9; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['withdrawalRuleObject']['name'] = 'Rules: Direct Withdrawal';
   $who_xmlobjects['withdrawalRuleObject']['parentprops'] = array('inflows'=>'publicvars','outflows'=>'publicvars');
   $who_xmlobjects['withdrawalRuleObject']['description'] = 'Simple modeling of stocks and flows.';
}


error_log("Creating matrixAccessor<br>");
#print("Creating Object<br>");
$obj = new matrixAccessor;
// perform serialization
$result = $serializer->serialize($obj);
#print("Printing Result<br>");
// check result code and display XML if success
if($result === true)
{
   $xml = $serializer->getSerializedData();
   $who_xmlobjects['matrixAccessor']['xml'] = $xml;
   $who_xmlobjects['matrixAccessor']['type'] = '2'; # type 1 - stand-alone object, 2 - sub-component only, 3 - model container (runnable), 4 - both stand-alone and sub-comp
   $who_xmlobjects['matrixAccessor']['toolgroup'] = 6; 
   $who_xmlobjects['matrixAccessor']['name'] = 'Data Matrix Accessor';
   $who_xmlobjects['matrixAccessor']['parentprops'] = array('targetmatrix'=>'publicvars');
   $who_xmlobjects['matrixAccessor']['description'] = 'Widget to access matrix table lookup.';
}


?>
