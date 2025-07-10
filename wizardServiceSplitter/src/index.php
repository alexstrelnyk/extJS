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

switch ($action) {
	case 'get_circuit':
		if (isset($_REQUEST['id']) && $cir_id = $_REQUEST['id']) {
			$ar['circuit'] = getSQLData($con, "select t.name from CRAMER.CIRCUIT_O t
				WHERE t.circuitid = " . $cir_id, true);
			$ar['values'] = getSQLData($con, "select 
			c.circuitid,
			c.name,
			c.objectid as service,
			l.name as location,
			l.locationid,
			n.name as node,
			n.nodeid,
			pp.name as port,
pp.portid			
			from circuit_o c
left join location_o l on l.locationid=c.circuit2startlocation
left join node_o n on n.nodeid=c.circuit2startnode
left join port_o p on p.portid=c.circuit2startport
left join port_o pp on pp.portid=p.parentport2port
where c.circuit2endnode=3265384
and c.circuit2circuittype=1900000039
and c.subtype='FTTB'
and rownum < 3");
			exit(json_encode(array('success' => true, 'data' => $ar)));
		} else {
			exit(json_encode(array('success' => false, 'data' => [])));
		}
		break;
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
	case 'get_port':
		$sql = "SELECT distinct(p.name), p.portid id FROM CRAMER.port_o p
		WHERE rownum < 20 ";
		if (isset($_REQUEST['NODEID']) && $nodeid = $_REQUEST['NODEID']) {
			$sql .= "AND p.PORT2NODE = " . $nodeid . " ";
		}
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND p.name LIKE '%" . $query . "%' ";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'save_ser_spl':
		$circuitTypeId = false;
		$package_name = false;
		$create_without_def = false;
		$fields = "";
		if (isset($_REQUEST['circuitTypeId']) && $circuitTypeId = $_REQUEST['circuitTypeId']) {
			$package_name_sql = "
	select t.circuittype2porttype from CRAMER.circuittype_m t 
	where t.CIRCUITTYPEID = $circuitTypeId";
			if ($circuit_row = getSQLData($con, $package_name_sql)) {
				if ($circuit_row['CIRCUITTYPE2PORTTYPE']) {
					$create_without_def = true;
				}
			}
		}

		$sql = "
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
	";
		if (isset($_REQUEST['name']) && $query = $_REQUEST['name']) {
			$sql .= "i_circuitname   VARCHAR2(200) := '" . $query . "'; 
			";
		}
		if (isset($_REQUEST['LOCATION']) && $query = $_REQUEST['LOCATION']) {
			$sql .= "i_startlocationid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['circuitId']) && $query = $_REQUEST['circuitId']) {
			$sql .= "i_circuitid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['startNodeId']) && $query = $_REQUEST['startNodeId']) {
			$sql .= "i_startnodeid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['startPortId']) && $query = $_REQUEST['startPortId']) {
			$sql .= "i_startportid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['endLocId']) && $query = $_REQUEST['endLocId']) {
			$sql .= "i_endlocationid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['endNodeId']) && $query = $_REQUEST['endNodeId']) {
			$sql .= "i_endnodeid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['endPortId']) && $query = $_REQUEST['endPortId']) {
			$sql .= "i_endportid   NUMBER := " . $query . "; 
			";
		}
		if (!$create_without_def && isset($_REQUEST['circuitdef']) && $query = $_REQUEST['circuitdef']) {
			$sql .= "i_circuitdefid   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['bandwidthId']) && $query = $_REQUEST['bandwidthId']) {
			$sql .= "i_bandwidthid   NUMBER := " . $query . "; 
			";
		}
		if ($circuitTypeId) {
			$sql .= "i_circuittypeid   NUMBER := " . $circuitTypeId . "; 
			";
		}

		if ($create_without_def) {
			$package_name = "SETDATACIRCUITDETAILS";
			$fields = "
o_errorcode, 
o_errortext, 
i_circuitid, 
i_circuitname, 
i_circuittypeid, 
i_startlocid, 
i_endlocid, 
i_startnodeid, 
i_endnodeid, 
i_startportid, 
i_endportid";
		} else {
			$package_name = "SETCIRCUITDETAILS";
			$fields = "
o_errorcode, 
o_errortext, 
i_circuitid, 
i_startlocationid, 
i_startnodeid, 
i_startportid, 
i_endlocationid, 
i_endnodeid, 
i_endportid, 
i_circuitname, 
i_circuittypeid, 
i_bandwidthid, 
i_circuitdefid
";
		}

		$sql .= "
	
BEGIN

  CRAMER.getsession();
  
  PKGCIRCUIT.$package_name(
    $fields
  );

  IF o_errorcode != 0 THEN
    RAISE_APPLICATION_ERROR(-20001, 'Package Error: ' || o_errortext);
  END IF;

  :CIRID := i_circuitid;
  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;

END;

";

		//	exit($sql);

		$pcon = $db_cfg['cramer_admin'];

		$con = oci_connect($pcon['login'], $pcon['pass'], $pcon['param']);
		$st = oci_parse($con, $sql);
		oci_bind_by_name($st, ':CIRID', $circuit_id, 10);
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

		if (empty($circuit_id)) {
			echo json_encode(['success' => false, 'message' => 'Error: New circuit ID is empty.']);
			oci_rollback($con);
			exit;
		}

		oci_commit($con);
		echo json_encode(['success' => true, 'data' => ['circuit_id' => $circuit_id]]);

		break;
}
