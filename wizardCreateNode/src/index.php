<?
error_reporting(E_ALL);

include_once "../../../../../../common/gconnect.php";
include_once "../../../../../../common/getLogin.php";
include_once "../../../../../../common2/tv_json.php";
include_once "../../../../../../common2/func2.php";
include_once "../../../../../../cfg/db.php";


$action = $_REQUEST['action'];
$xaction = $_REQUEST['xaction'];

getLogin();
checkAccess();
$role = (isset($_SESSION['roles']['Cramer navigator']['Standart user']) ? 1 : 0);

$con = ksdb_connect('cramer_admin');

function getSQLData($con, $sql, $get_single = false)
{
	$q = $con->exec($sql);
	if (!$q) throw new Exception($q->error());
	$ar = array();
	while ($r = $q->fetch($q)) {
		if ($get_single) {
			$ar = $r;
			break;
		} else {
			$ar[] = $r;
		}
	}

	return $ar;
}

function addAttributes($db_cfg, $obj_id, $name, $value)
{
	//	echo __FILE__.' '.__LINE__.'<pre>';print_r($obj_id.$name.$value).'</pre>';die;
	$sql = "
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
	";
	$sql .= "i_dimobject   NUMBER := 1; 
			";
	$sql .= "i_objectid   NUMBER := " . $obj_id . "; 
			";
	$sql .= "i_attribute   VARCHAR2(200) := '" . $name . "'; 
			";
	$sql .= "i_value   VARCHAR2(200) := '" . $value . "'; 
			";

	$sql .= "

BEGIN

  CRAMER.getsession();
  
  PKGGeneral.SetObjectAttribute(
	o_errorcode, 
	o_errortext, 
	i_dimobject, 
	i_objectid, 
	i_attribute, 
	i_value
  );

  IF o_errorcode != 0 THEN
    RAISE_APPLICATION_ERROR(-20001, 'Package Error: ' || o_errortext);
  END IF;

  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;

END;

";

	//	exit($sql);

	//	echo __FILE__.' '.__LINE__.'<pre>';print_r($db_cfg).'</pre>';die;

	$pcon = $db_cfg['cramer_admin'];

	$con = oci_connect($pcon['login'], $pcon['pass'], $pcon['param']);
	$st = oci_parse($con, $sql);

	oci_bind_by_name($st, ':ERRCODE', $errorcode, 10);
	oci_bind_by_name($st, ':ERRTEXT', $errortext, 4000);

	if (!oci_execute($st)) {
		$e = oci_error($st);
		echo json_encode(['success' => false, 'message' => 'Oracle Error: ' . $e['message']]);
		oci_rollback($con);
		exit;
	}

	if ($errorcode != 0) {
		echo json_encode(['success' => false, 'message' => 'Procedure Error: ' . $errortext]);
		oci_rollback($con);
		exit;
	}

	oci_commit($con);
	//	exit;
}

switch ($action) {
	case 'get_location':
		$sql = "select distinct(l.name), l.locationid id from CRAMER.location_o l 
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND l.name LIKE '%" . $query . "%' ";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_node':
		$sql = "SELECT n.name, n.nodeid id FROM CRAMER.node_o n
		WHERE rownum < 20 ";
		if (isset($_REQUEST['LOCATIONID']) && $locid = $_REQUEST['LOCATIONID']) {
			$sql .= "AND n.node2location = " . $locid . " ";
		}
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND n.name LIKE '%" . $query . "%' ";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_node_types':
		$sql = "SELECT nt.nodetypeid id, nt.name from CRAMER.NODETYPE_M nt 
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND nt.name LIKE '%" . $query . "%' ";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_subtypes':
		$sql = "select DISTINCT(n.subtype) name from CRAMER.node_o n
		WHERE rownum < 200 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND n.subtype LIKE '%" . $query . "%' ";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_node_defs':
		$sql = "SELECT nd.nodedefid id, nd.name from CRAMER.NODEDEF_M nd 
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND nd.name LIKE '%" . $query . "%' ";
		}
		if (isset($_REQUEST['node_type']) && $query = $_REQUEST['node_type']) {
			$sql .= "AND nd.nodedef2nodetype = " . $query;
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'save_node':
		//	addAttributes($db_cfg, '3458805', 'NOTES', 'test comment');
		$sql = "
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
    o_nodeid            NUMBER;
	";
		if (isset($_REQUEST['NAME']) && $query = $_REQUEST['NAME']) {
			$sql .= "i_name   VARCHAR2(200) := '" . $query . "'; 
			";
		}
		if (isset($_REQUEST['NODEDEF']) && $query = $_REQUEST['NODEDEF']) {
			$sql .= "i_node2nodedef   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['LNAME']) && $query = $_REQUEST['LNAME']) {
			$sql .= "i_node2location   NUMBER := " . $query . "; 
			";
		}

		$sql .= "
	
BEGIN

  CRAMER.getsession();
  
  pkgnode.createnode(
	o_errorcode, 
	o_errortext, 
	o_nodeid, 
	i_name, 
	i_node2nodedef, 
	i_node2location
  );

  IF o_errorcode != 0 THEN
    RAISE_APPLICATION_ERROR(-20001, 'Package Error: ' || o_errortext);
  END IF;

  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;
  :NODEID := o_nodeid;

END;

";

		//	exit($sql);

		$pcon = $db_cfg['cramer_admin'];

		$con = oci_connect($pcon['login'], $pcon['pass'], $pcon['param']);
		$st = oci_parse($con, $sql);

		oci_bind_by_name($st, ':NODEID', $node_id, 10);
		oci_bind_by_name($st, ':ERRCODE', $errorcode, 10);
		oci_bind_by_name($st, ':ERRTEXT', $errortext, 4000);

		if (!oci_execute($st)) {
			$e = oci_error($st);
			echo json_encode(['success' => false, 'message' => 'Oracle Error: ' . $e['message']]);
			oci_rollback($con);
			exit;
		}

		if ($errorcode != 0) {
			echo json_encode(['success' => false, 'message' => 'Procedure Error: ' . $errortext]);
			oci_rollback($con);
			exit;
		}

		addAttributes($db_cfg, $node_id, 'NOTES', $_REQUEST['COMMENTS']);
		//	exit();

		oci_commit($con);
		echo json_encode(['success' => true, 'data' => []]);

		break;
}
