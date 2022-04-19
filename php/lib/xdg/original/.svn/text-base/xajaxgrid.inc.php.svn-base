<?
/**
 * xhtml.inc.php :: Main XajaxGrid class file
 *
 * XajaxGrid version 0.0.1
 * copyright (c) 2006 by Jesus Velazquez ( jjvema@yahoo.com )
 * http://geocities.com/jjvema/
 *
 * XajaxGrid is an open source PHP class library for easily creating a grid data
 * on web-based Ajax Applications. Using XajaxGrid.
 *
 * xajax is released under the terms of the LGPL license
 * http://www.gnu.org/copyleft/lesser.html#SEC3
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @package XajaxGrid
 * @version $Id$
 * @copyright Copyright (c) 2006  by Jesus Velazquez
 * @license http://www.gnu.org/copyleft/lesser.html#SEC3 LGPL License
 */


/** \brief Class to generate a table dynamically
 *
 * The ScrollTable class generate dynamically a table
 * 
 * @package XajaxGrid
 */ 
 
class ScrollTable{
	/**
	 * @var int Number of columns for the table.
	 */
	var $n_cols;
	/**
	 * @var string Row of table to display the search form.
	 */
	var $search;
	/**
	 * @var string Content of the table top
	 */
	var $top;
	/**
	 * @var string Content of the table headers
	 */
	var $header;
	/**
	 * @var string Content of each table row
	 */
	var $rows;
	/**
	 * @var string Content of table footer
	 */
	var $footer;
	/**
	 * @var string Style for table row
	 */
	var $rowStyle;
	/**
	 * @var string Attributes for each table column
	 */
	var $colAttrib;
	/**
	 * @var string It contains the limit of records to show in the SQL sentence executed
	 */
	var $limit;
	/**
	 * @var string It contains the initial record to show in the SQL sentence executed
	 */
	var $start;
	/**
	 * @var string It contains the filter of the SQL sentence executed
	 */
	var $filter;
	/**
	 * @var string It contains the total number of rows to show
	 */
	var $numRowsToShow;
	/**
	 * @var string It contains the total number of rows of the SQL sentence executed
	 */
	var $numRows;
	/**
	 * @var string It is the content to search in a SQL sentence
	 */
	var $content;
	/**
	 * @var string It is the field to organize the data of the table
	 */
	var $order;


	/**
	 * Constructor.
	 * 
	 * @param integer $cols: Amount of columns of the table
	 * @param integer $start: initial record to show
	 * @param integer $limit: final record to show
	 * @param string  $filter: field name of the database table.
	 * @param integer $numRows: number of total rows of the search
	 * @param string  $content: content to search
	 * @param string  $order: field to organize the data of the table
	 */
	function ScrollTable($cols, $start = 0, $limit, $filter = null, $numRows = 0, $content = null, $order = null){
		$this->n_cols = $cols;
		$this->limit = $limit;
		$this->numRows = $numRows;
		$this->numRowsToShow = 5;
		$this->start = $start;
		$this->top = '<table class="adminlist" border="1">';
		$this->rowStyle = "row1";
		$this->filter = $filter;
		$this->content = $content;
		$this->order = $order;
		$this->setFooter();
	}
		
		
	/**
	* Set a header Table with attributes on the variable "header" of the class.
	*
	* @param string 	$class		the clas style
	* @param array  	$options		array that contain the labels for the headers.
	* @param array 	$attribs		array that contain the attributes for the headers.
	* @param array		$events		array that contain the events on this labels.
	* @return none
	*
	*/

	function setHeader($class,$headers,$attribs,$events){
		$ind = 0;
		$this->header = '
		<tr>';
		foreach($headers as $value){
			$this->header .= '
			<th '.$attribs[$ind].' class="'.$class.'">
				<a href="?" '.$events[$ind].'>'.$value.'</a>
			</th>';
			
			$ind++;
		}
		$this->header .= '	
			<th style="text-align: center" class="'.$class.'" width="5%">
				Editar
			</th>
			<th style="text-align: center" class="'.$class.'" width="5%">
				Borrar
			</th>
		</tr>';
	}
	
	/**
	* Set the attributes for the table columns.
	*
	* @param array 	$attribsCols		array that contain the attributes for the headers.
	* @return none
	*
	*/
	
	function setAttribsCols($attribsCols){
		$this->colAttrib = $attribsCols;
	}
	
	/**
	* Add each row generates dynamically from database records obtained
	*
	* @param string 	$table		Table name of data base
	* @param array 	$arr			Array with the data extracted in the SQL Sentence
	* @return none
	*
	*/
	
	function addRow($table,$arr){
	
	   $row = '<tr class="'.$this->rowStyle.'" >';
		$ind = 0; 
		
	   foreach ($arr as $key => $value) {
	   	if($key != 'id')
   			$row .= '<td '.$this->colAttrib[$ind].'>'.$value.'</td>';
   		$ind++;
		}
		
		$row .= '<td align="center" width="5%">
						<a href="?" onClick="xajax_edit('.$arr[0].',\''.$table.'\');return false;"><img src="images/edit.png" border="0"></a>
					</td>
					<td align="center" width="5%">
						<a href="?" onClick="if (confirm(\'Are you sure?\'))  xajax_delete(\''.$arr[0].'\',\''.$table.'\');return false;"><img src="images/trash.png" border="0"></a>
					</td>';
		$row .= "</tr>";
		$this->rows .= $row;
		
		if($this->rowStyle == "row0") $this->rowStyle = "row1"; else $this->rowStyle = "row0";
		
	}	
	
	/**
	* Add the line with the search form and the button to add a new record
	*
	* @param string 	$table		Table name of data base
	* @param array 	$fieldsFromSearch 	Its contains the values from "SELECT" search form.
	* @param array		$fieldsFromSearchShowAs	 Its contains the labels show in the "SELECT" search form.
	* @return none
	*
	*/

	function addRowSearch($table,$fieldsFromSearch,$fieldsFromSearchShowAs){
		$ind = 0;
		$this->search = '
			<table width="100%" border="0">
			<tr>
			<td colspan="2" nowrap><div id="msgZone" class="msgZone">&nbsp;</div></td>
			</tr>
			<tr>
				<td align="left" width="10%"><button id="submitButton" onClick="xajax_add(\''.$table.'\');return false;">Add record</button></td>
				<td align="right" width="30%" nowrap>
				Search : &nbsp;<input type="text" size="30" id="searchContent" name="searchContent">
				&nbsp;&nbsp;By &nbsp;
					<select id="searchField" name="searchField">
						<option value"0"> - Select field - </option>';
					foreach ($fieldsFromSearchShowAs as $value) {
						$this->search .= '<option value="'.$fieldsFromSearch[$ind].'">'.$value.'</option>';
						$ind++;
					}	
		$this->search .= '
					</select>
				&nbsp;&nbsp;<button id="submitButton" onClick="xajax_showGrid(0,'.$this->numRowsToShow.',document.getElementById(\'searchField\').value,document.getElementById(\'searchContent\').value,document.getElementById(\'searchField\').value);return false;">Continue</button>
				</td>
				
			</tr>
		</table>';
	}


	/**
	* Add the footer of the table (Grid), that its contains the record information such as number of records, previos, next and final,  totals records, etc. Each one with its link when it is posible.
	*
	*/

	function setFooter(){
		$next_rows = $this->start + $this->limit;
		$previos_rows = $this->start - $this->limit;
		if($next_rows>$this->numRows) $next_rows = $this->numRows;
		if($previos_rows<0)$previos_rows = 0;
		if($this->numRows < 1) $this->start = -1;
		$this->footer = '</table>';
		$this->footer .= '
		<table class="adminlist">
			<tr>
				<th colspan="'.$this->n_cols.'">
					<span class="pagenav">';
					if($this->start>0){
						$this->footer .= '<a href="?" onClick=\'xajax_showGrid(0,'.$this->limit.',"'.$this->filter.'","'.$this->content.'","'.$this->order.'");return false;\'><< First</a>';
					}else{
						$this->footer .= '<< First';
					}
					$this->footer .= '</span>
					<span class="pagenav">';
					
					if($this->start >0){
					$this->footer .= '
						<a href="?" onClick=\'xajax_showGrid('.$previos_rows.','.$this->limit.',"'.$this->filter.'","'.$this->content.'","'.$this->order.'");return false;\'>< Previous</a>';
					}else{
						$this->footer .= '< Previous';
					}
					$this->footer .= '
					</span>
					<span class="pagenav">';
					
					$this->footer .= ' [ ' . ($this->start+1) . ' al ' . $next_rows .' de '. $this->numRows .' ] ';
					
					$this->footer .= '
					</span>
					<span class="pagenav">';
					
					if($next_rows < $this->numRows){
						$this->footer .= '<a href="?" onClick=\'xajax_showGrid('.$next_rows.','.$this->limit.',"'.$this->filter.'","'.$this->content.'","'.$this->order.'");return false;\'>Next ></a>';
					}else{
						$this->footer .= 'Next >';
					}
					
					$this->footer .= ' </span>
					<span class="pagenav">';
					
					if($next_rows < $this->numRows){
					$this->footer .= '<a href="?" onClick=\'xajax_showGrid('.($this->numRows - $this->limit).','.$this->limit.',"'.$this->filter.'","'.$this->content.'","'.$this->order.'");return false;\'>Last >></a>';
					}else{
					$this->footer .= 'Last >></span>';
					}
				$this->footer .= '
				</th>
			</tr>
		</table>';
		$this->footer .= $this->search = '
			<table width="100%" border="0">
			<tr>
				<td>XajaxGrid V 1.0</td>
				<td align="right" >
				<button id="submitButton" onClick="xajax_showGrid(0,'.MAXROWSXPAGE.');return false;">Show All</button>
				</td>
			</tr>
		</table>';
		
	}

	/**
	* It combines the variables $this->search . $this->top . $this->header . $this->rows . $this->footer headedfooter to create the table with the data.
	*
	*/

	function render(){
		$table = $this->search . $this->top . $this->header . $this->rows . $this->footer;
		
		return $table;
	}
	
}

/**
 * Class Table for general intentions
 * 
 * @package XajaxGrid
 */ 
class Table {

	/**
	* Headers of table
	* @param string 	$tableTitle		Title of table
	* @return string
	*/
	function Top($tableTitle = "tableTitle"){
		$table = '
			<table width="98%" border="0" align="center"><tr><td align="right"><img src="images/close.png" onClick=\'javascript:document.getElementById("formDiv").style.visibility="hidden";return false;\' alt="Cerrar ventana"></td></tr></table>
			<table class="adminlist" border="1" width="50%">
			<tr ><th  class="adminlist" colspan="2">'.$tableTitle.'</th></tr>
			<tr class="row0"><td>';

		return $table;
	}

	/**
	* Footer of table
	* @return string
	*/
	function Footer(){
		$table = '
			</td></tr>
			</table><br>';
		return $table;
	}
}


?>
