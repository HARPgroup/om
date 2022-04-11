<?

require_once ('common.php');
require_once ('person.inc.php');
require_once ('include/xajaxgrid.inc.php');

function createGrid($start = 0, $limit = 1,$filter = null, $content = null, $order = null){

	if($content == null){
		$numRows =& Person::getNumRows();
		$arreglo =& Person::getAllRecords($start,$limit,$order);
	}else{
		$numRows =& Person::getNumRows($filter, $content);
		$arreglo =& Person::getRecordsFiltered($start, $limit, $filter, $content, $order);	
	}
	if($filter != null)
		$_SESSION['filter'] = $filter;
	
	// Editable zone
	
	$headers = array();
	$headers[] = "Last Name";
	$headers[] = "First Name";
	$headers[] = "E-mail";
	$headers[] = "Origin";
	
	$attribsHeader = array();
	$attribsHeader[] = 'width="20%"';
	$attribsHeader[] = 'width="20%"';
	$attribsHeader[] = 'width="20%"';
	$attribsHeader[] = 'width="30%"';
	
	$attribsCols = array();
	
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	$attribsCols[] = 'style="text-align: left"';
	
	$eventHeader = array();
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","lastname");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","firstname");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","email");return false;\'';
	$eventHeader[]= 'onClick=\'xajax_showGrid(0,'.$limit.',"'.$filter.'","'.$content.'","origin");return false;\'';
	
	$fieldsFromSearch = array();
	$fieldsFromSearch[] = 'lastname';
	$fieldsFromSearch[] = 'firstname';
	$fieldsFromSearch[] = 'email';
	$fieldsFromSearch[] = 'origin';
	
	$fieldsFromSearchShowAs = array();
	$fieldsFromSearchShowAs[] = "Last Name";
	$fieldsFromSearchShowAs[] = "First Name";
	$fieldsFromSearchShowAs[] = "E-Mail";
	$fieldsFromSearchShowAs[] = "Origin";

	$table = new ScrollTable(5,$start,$limit,$filter,$numRows,$content,$order);
	$table->setHeader('title',$headers,$attribsHeader,$eventHeader);
	$table->setAttribsCols($attribsCols);
	$table->addRowSearch("alumno",$fieldsFromSearch,$fieldsFromSearchShowAs);
	
	while ($arreglo->fetchInto($row)) {
	// Change here by the name of fields of its database table
		$rowc = array();
    		$rowc[] = $row['id'];
    		$rowc[] = '<a href="?" onClick="xajax_show('.$row['id'].');return false">'.$row['lastname'].'</a>';
    		$rowc[] = $row['firstname'];
    		$rowc[] = $row['email'];
    		$rowc[] = $row['origin'];
    	
    		$table->addRow("grupo",$rowc);
    	
 	}
 	
 	// End Editable Zone
 	
 	$html = $table->render();
 	return $html;
}

function showGrid($start = 0, $limit = 1,$filter = null, $content = null, $order = null){

	$html = createGrid($start, $limit,$filter, $content, $order);
	$objResponse = new xajaxResponse();
	$objResponse->addAssign("grid", "innerHTML", $html);
	
	return $objResponse->getXML();
}

function add($table_DB){
   // Edit zone
	$html = Table::Top("Record Added");  // <-- Set the title for your form.
   $html .= Person::formAdd();  // <-- Change by your method
   // End edit zone
   $html .= Table::Footer();
	$objResponse = new xajaxResponse();
	$objResponse->addAssign("formDiv", "style.visibility", "visible");
	$objResponse->addAssign("formDiv", "innerHTML", $html);
	
	return $objResponse->getXML();
}

function edit($id = null, $table_DB = null){
	// Edit zone
	$html = Table::Top("Record Edited"); // <-- Set the title for your form.
   	$html .= Person::formEdit($id); // <-- Change by your method
   	$html .= Table::Footer();
   	// End edit zone
	$objResponse = new xajaxResponse();
	$objResponse->addAssign("formDiv", "style.visibility", "visible");
	$objResponse->addAssign("formDiv", "innerHTML", $html);
	return $objResponse->getXML();
}

function delete($id = null, $table_DB = null){
	Person::deleteRecord($id);
	$html = createGrid(0,ROWSXPAGE);
	$objResponse = new xajaxResponse();
	$objResponse->addAssign("grid", "innerHTML", $html);
	$objResponse->addAssign("msgZone", "innerHTML", "Record Deleted"); // <-- Change by your leyend
	return $objResponse->getXML();
}

function show($id = null){
	if($id != null){
	$html = Table::Top("Show Record"); // <-- Set the title for your form.
   	$html .= Person::showRecord($id); // <-- Change by your method
   	$html .= Table::Footer();
   	$objResponse = new xajaxResponse();
   	$objResponse->addAssign("formDiv", "style.visibility", "visible");
		$objResponse->addAssign("formDiv", "innerHTML", $html);
		
		return $objResponse->getXML();
	}
}

function save($f){
	$objResponse = new xajaxResponse();
	$message = Person::checkAllData($f,1);
	if(!$message){
		$respOk = Person::insertNewRecord($f);
		if($respOk){
			$html = createGrid(0,ROWSXPAGE);
			$objResponse->addAssign("grid", "innerHTML", $html);
			$objResponse->addAssign("msgZone", "innerHTML", "A record has been added");
			$objResponse->addAssign("formDiv", "style.visibility", "hidden");
		}else{
			$objResponse->addAssign("msgZone", "innerHTML", "The record could not be added");
		}
	}else{
		$objResponse->addAlert($message);
	}
	return $objResponse->getXML();
	
}

function update($f){
	$objResponse = new xajaxResponse();
	$message = Person::checkAllData($f);
	if(!$message){
		$respOk = Person::updateRecord($f);
		if($respOk){
			$html = createGrid(0,ROWSXPAGE);
			$objResponse->addAssign("grid", "innerHTML", $html);
			$objResponse->addAssign("msgZone", "innerHTML", "A record has been updated");
			$objResponse->addAssign("formDiv", "style.visibility", "hidden");
		}else{
			$objResponse->addAssign("msgZone", "innerHTML", "The record could not be updated");
		}
	}else{
		$objResponse->addAlert($message);
	}
	
	return $objResponse->getXML();
}



$xajax->processRequests();

?>
