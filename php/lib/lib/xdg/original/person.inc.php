<?

require_once 'DB.php';
require_once "PEAR.php";

define('SQLC', "mysql://root@localhost/xajaxGrid");
$GLOBALS['db'] =& DB::connect(SQLC);
$GLOBALS['db']->setFetchMode(DB_FETCHMODE_ASSOC);

/** \brief Clase para el manejo de los personas
*
* En esta clase se definen los metodos para el manejo de los datos de la base de datos concernientes a
* los personas.
*
* @author	Jesus Velazquez <jjvema@yahoo.com>
* @version	1.0
* @date		20 de Junio de 2006
*/

class Person extends PEAR
{

	/**
	*  Obtiene todos los registros de la tabla paginados.
	*
	*  @param $start	(int)	Inicio del rango de la p&aacute;gina de datos en la consulta SQL.
	*	@param $limit	(int)	L&iacute;mite del rango de la p&aacute;gina de datos en la consultal SQL.
	*	@param $order 	(string) Campo por el cual se aplicar&aacute; el orden en la consulta SQL.
	*	@return $res 	(object) Objeto que contiene el arreglo del resultado de la consulta SQL.
	*/
	function &getAllRecords($start, $limit, $order = null){
		global $db;
	
		if($order == null){
			$sql = "SELECT * FROM person LIMIT $start, $limit";
		}else{
			$sql = "SELECT * FROM person ORDER BY $order LIMIT $start, $limit";
		}
		
		$res =& $db->query($sql);
		return $res;
	}
	
	/**
	*  Obtiene todos registros de la tabla paginados y aplicando un filtro
	*
	*  @param $start		(int) 		Es el inicio de la p&aacute;gina de datos en la consulta SQL
	*	@param $limit		(int) 		Es el limite de los datos p&aacute;ginados en la consultal SQL.
	*	@param $filter		(string)	Nombre del campo para aplicar el filtro en la consulta SQL
	*	@param $content 	(string)	Contenido a filtrar en la conslta SQL.
	*	@param $order		(string) 	Campo por el cual se aplicar&aacute; el orden en la consulta SQL.
	*	@return $res		(object)	Objeto que contiene el arreglo del resultado de la consulta SQL.
	*/

	function &getRecordsFiltered($start, $limit, $filter = null, $content = null, $order = null){
		global $db;
		
		$sql = 	"SELECT * FROM person "
			."WHERE ".$filter." like '%".$content."%' "
			."ORDER BY ".$order." "
			."LIMIT $start, $limit";

		$res =& $db->query($sql);
		return $res;
	}
	
	/**
	*  Devuelte el numero de registros de acuerdo a los par&aacute;metros del filtro
	*
	*	@param $filter	(string)	Nombre del campo para aplicar el filtro en la consulta SQL
	*	@param $order	(string)	Campo por el cual se aplicar&aacute; el orden en la consulta SQL.
	*	@return $row['numrows']	(int) 	N&uacute;mero de registros (l&iacute;neas)
	*/
	
	function &getNumRows($filter = null, $content = null){
		global $db;
		
		$sql = "SELECT COUNT(*) AS numRows FROM person";
		
		if(($filter != null) and ($content != null)){
			$sql = 	"SELECT COUNT(*) AS numRows "
				."FROM person "
				."WHERE ".$filter." like '%$content%'";
		}
		
		$res =& $db->query($sql);
		$res->fetchInto($rows);
		return $rows['numRows'];
		
	}
	
	/**
	*  Devuelte el registro de acuerdo al $id pasado.
	*
	*	@param $id	(int)	Identificador del registro para hacer la b&uacute;squeda en la consulta SQL.
	*	@return $row	(array)	Arreglo que contiene los datos del registro resultante de la consulta SQL.
	*/
	
	function &getRecordByID($id){
		global $db;
		
		$sql = 	"SELECT * "
			."FROM person "
			."WHERE id = $id";
		
		$res =& $db->query($sql);
		$res->fetchInto($row);
		
		return $row;
	}
	
	/**
	*  Inserta un nuevo registro en la tabla.
	*
	*	@param $f	(array)		Arreglo que contiene los datos del formulario pasado.
	*	@return $res	(object) 	Devuelve el objeto con la respuesta de la sentencia SQL ejecutada del INSERT.
	*/
	
	function insertNewRecord($f){
		global $db;
		
		$sql= 	"INSERT INTO person SET "
			."lastname='".$f['lastname']."', "
			."firstname='".$f['firstname']."', "
			."email='".$f['email']."', "
			."origin='".$f['origin']."'";
		
		$res =& $db->query($sql);
		if(DB::isError($ret)) return PEAR::raiseError($res);
		return $res;
	}
	
	/**
	*  Actualiza un registro de la tabla.
	*
	*	@param $f	(array)		Arreglo que contiene los datos del formulario pasado.
	*	@return $res	(object)	Devuelve el objeto con la respuesta de la sentencia SQL ejecutada del UPDATE.
	*/
	
	function updateRecord($f){
		global $db;
		
		$sql= 	"UPDATE person SET "
			."lastname='".$f['lastname']."', "
			."firstname='".$f['firstname']."', "
			."email='".$f['email']."', "
			."origin='".$f['origin']."' "
			."WHERE id='".$f['id']."'";

		$res =& $db->query($sql);
		exec("echo \"$sql\" >> /tmp/debug.log");
		if(DB::isError($ret)) return PEAR::raiseError($res);
		return $res;
	}
	
	/**
	*  Borra un registro de la tabla.
	*
	*	@param $id		(int)	Identificador del registro a ser borrado.
	*	@return $res	(object) Devuelve el objeto con la respuesta de la sentencia SQL ejecutada del DELETE.
	*/
	
	function deleteRecord($id){
		global $db;
	
		$sql = "DELETE FROM person WHERE id = $id";
		
		$res =& $db->query($sql);
		if(DB::isError($ret)) return PEAR::raiseError($res);
		return $res;
	}
	
	/**
	*  Imprime la forma para agregar un nuevo registro sobre el DIV identificado por "formDiv".
	*
	*	@param ninguno
	*	@return $html	(string) Devuelve una cadena de caracteres que contiene la forma para insertar 
	*							un nuevo registro.
	*/
	
	function formAdd(){
	$html = '
			<!-- No edit the next line -->
			<form method="post" name="f" id="f">
			
			<table border="0" width="100%">
			<tr>
				<td nowrap align="left">Last Name*</td>
				<td align="left"><input type="text" id="lastname" name="lastname" size="25"></td>
			</tr>
			<tr>
				<td nowrap align="left">First Name</td>
				<td align="left"><input type="text" id="firstname" name="firstname" size="25"></td>
			</tr>
			<tr>
				<td nowrap align="left">E-Mail</td>
				<td align="left"><input type="text" id="email" name="email" size="35"></td>
			</tr>
			<tr>
				<td nowrap align="left">Origin</td>
				<td align="left"><input type="text" id="origin" name="origin" size="35"></td>
			</tr>
			<tr>
				<td colspan="2" align="center"><button id="submitButton" onClick=\'xajax_save(xajax.getFormValues("f"));return false;\'>Aceptar</button></td>
			</tr>
			</table>
			</form>
			* Obligatory fields
			';
		
		return $html;
	}
	
	/**
	*  Imprime la forma para editar un nuevo registro sobre el DIV identificado por "formDiv".
	*
	*	@param $id		(int)		Identificador del registro a ser editado.
	*	@return $html	(string) Devuelve una cadena de caracteres que contiene la forma con los datos 
	*									a extraidos de la base de datos para ser editados 
	*/
	
	function formEdit($id){
		
		$person =& Person::getRecordByID($id);
		$html = '
				<!-- No edit the next line -->
				<form method="post" name="f" id="f">
				<input type="hidden" id="id"  name="id" value="'.$person['id'].'">
				<table border="0" width="100%">
				<tr>
					<td nowrap align="left">Last Name*</td>
					<td align="left"><input type="text" id="lastname" name="lastname" size="25" value="'.$person['lastname'].'"></td>
				</tr>
				<tr>
					<td nowrap align="left">First Name*</td>
					<td align="left"><input type="text" id="firstname" name="firstname" size="25" value="'.$person['firstname'].'"></td>
				</tr>
				<tr>
                                        <td nowrap align="left">E-Mail*</td>
                                        <td align="left"><input type="text" id="email" name="email" size="35" value="'.$person['email'].'"></td>
                                </tr>
				<tr>
                                        <td nowrap align="left">Origin</td>
                                        <td align="left"><input type="text" id="origin" name="origin" size="35" value="'.$person['origin'].'"></td>
                                </tr>
				<tr>
					<td colspan="2" align="center"><button id="submitButton" onClick=\'xajax_update(xajax.getFormValues("f"));return false;\'>Aceptar</button></td>
				</tr>
				</table>
				</form>
				* Obligatory fields
				';
		
		return $html;
	}
	
	/**
	*  Muestra todos los datos de un registro sobre el DIV identificado por "formDiv".
	*
	*	@param $id		(int)		Identificador del registro a ser mostrado.
	*	@return $html	(string) Devuelve una cadena de caracteres que contiene una tabla con los datos 
	*									a extraidos de la base de datos para ser mostrados 
	*/
	function showRecord($id){
			$person =& person::getRecordByID($id);
		$html = '
				<table border="0" width="100%" cellpading="1">
				<tr>
					<td nowrap align="left" width="10%">Last Name:</td>
					<td align="left">'.$person['lastname'].'</td>
				</tr>
				<tr>
					<td nowrap align="left">First Name:</td>
					<td align="left">'.$person['firstname'].'</td>
				</tr>
				<tr>
					<td nowrap align="left">E-Mail:</td>
					<td align="left">'.$person['email'].'</td>
				</tr>
				<tr>
                                        <td nowrap align="left">Origin:</td>
                                        <td align="left">'.$person['origin'].'</td>
                                </tr>
				</table>';

		return $html;

	}
	
	/**
	*  Verifica si los datos de la forma enviados son correctos de acuerdo a cada validaci&oacute;n en particular.
	*
	*  En este metodo es necesario que sea revisado para hacer las validaciones correspondientes a cada una de las
	*  entradas del formulario.
	*
	*	@param $f	(array)		Arreglo que contiene los datos del formularios procesado.
	*	@param $new	(boolean)	Si recibe el valor de 1 significa que la acci&oacute;n es insertar un nuevo registro,
	* 									de lo	contrario significa que esta editando el registro, por tanto no revisa si la
	*									clave es	repetida.
	*	@return $msg	(string)	Devuelve 0 si todos los datos estan correctos, de lo contrario devuelve el mensaje
	*									correspondiente a la validaci&oacute;n.
	*/
	function checkAllData($f,$new = 0){
		if(empty($f['lastname'])) return "The field LastName does not have to be null";
		if(empty($f['firstname'])) return "The field FirstName does not have to be null";
		if(empty($f['email'])) return "The field Email does not have to be null";
	 	return 0;
	}
}
?>
