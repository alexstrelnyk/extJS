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

function getSQLData($con, $sql)
{
	$q = $con->exec($sql);
	if (!$q) throw new Exception($q->error());
	$ar = array();
	while ($r = $q->fetch($q)) {
		$ar = $r;
	}

	return $ar;
}

switch ($action) {
	case 'get_loc':
		$sql = "select distinct(l.name), l.locationid from CRAMER.location_o l 
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND l.name LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_loc_type':
		$sql = "select t.circuittypeid,d.circuitdefid,t.name||' '||d.name as name from circuittype_m t
join circuitdef_m d on d.circuitdef2circuittype=t.circuittypeid
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND (t.name LIKE '%" . $query . "%' OR d.name LIKE '%" . $query . "%') ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_node':
		$sql = "SELECT n.name, n.nodeid FROM CRAMER.node_o n
		WHERE rownum < 20 ";
		if (isset($_REQUEST['locid']) && $locid = $_REQUEST['locid']) {
			$sql .= "AND n.node2location = " . $locid . " ";
		}
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND n.name LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_port':
		$sql = "SELECT distinct(p.name), p.portid FROM CRAMER.port_o p
		WHERE rownum < 20 ";
		if (isset($_REQUEST['nodeid']) && $locid = $_REQUEST['nodeid']) {
			$sql .= "AND p.PORT2NODE = " . $locid . " ";
		}
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND p.name LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_port_bandwidth':
		$sql = "select bb.bandwidthid, bb.name from cramer.bandwidth_m bb
join CRAMER.CIRCUITTYPEBANDWIDTH b on b.ctb2bandwidth=bb.bandwidthid
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND b.ctb2bandwidth LIKE '%" . $query . "%' ";
		}
		if (isset($_REQUEST['locid']) && $locid = $_REQUEST['locid']) {
			$sql .= "AND b.ctb2circuittype = " . $locid . " ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'generate_name':
		$sql = "
		
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
    o_name                 VARCHAR2(4000);
	";

		if (isset($_REQUEST['startPortId']) && $query = $_REQUEST['startPortId']) {
			$sql .= "i_circuitstartportid   NUMBER := " . $query . "; ";
		}
		if (isset($_REQUEST['endPortId']) && $query = $_REQUEST['endPortId']) {
			$sql .= "i_circuitendportid   NUMBER := " . $query . "; ";
		}
		if (isset($_REQUEST['startNodeName']) && $query = $_REQUEST['startNodeName']) {
			$sql .= "i_startnodename   VARCHAR2(50) := '" . $query . "'; ";
		}
		if (isset($_REQUEST['startPortName']) && $query = $_REQUEST['startPortName']) {
			$sql .= "i_startportname   VARCHAR2(50) := '" . $query . "'; ";
		}
		if (isset($_REQUEST['endNodeName']) && $query = $_REQUEST['endNodeName']) {
			$sql .= "i_endnodename   VARCHAR2(50) := '" . $query . "'; ";
		}
		if (isset($_REQUEST['endPortName']) && $query = $_REQUEST['endPortName']) {
			$sql .= "i_endportname   VARCHAR2(50) := '" . $query . "'; ";
		}
		if (isset($_REQUEST['circuitTypeId']) && $query = $_REQUEST['circuitTypeId']) {
			$sql .= "i_circuittype   NUMBER := " . $query . "; ";
		}
		$sql .= "
BEGIN

  CRAMER.getsession();

    pkgactivatecable.createcircuitname(
        o_errorcode,
        o_errortext,
        o_name,
        i_circuitstartportid,
        i_circuitendportid,
        i_startnodename,
        i_startportname,
        i_endnodename,
        i_endportname,
        i_circuittype
		

    );

  --  DBMS_OUTPUT.put_line('Error Code: ' || o_errorcode);
  --  DBMS_OUTPUT.put_line('Error Text: ' || o_errortext);
  --  DBMS_OUTPUT.put_line('Circuit Name: ' || o_name);
	
	:CNAME:=o_name;
END;
";

		$pcon = $db_cfg['cramer_admin'];

		$con = oci_connect($pcon['login'], $pcon['pass'], $pcon['param']);

		$st = oci_parse($con, $sql);
		oci_bind_by_name($st, ':CNAME', $circuit_name, 50);

		try {
			oci_execute($st);
		} catch (Exception $e) {
			echo json_encode(array('success' => false, 'message' => 'Error divide ' . $e->getMessage()));
		};

		if (oci_error()) {
			$e = oci_error();
			echo json_encode(array('success' => false, 'message' => $e['message']));
			oci_rollback($con);
		} else {
			if (empty($circuit_name)) {
				echo json_encode(array('success' => false, 'message' => 'Error, new circuit name is empty.' . $e['message']));
				oci_rollback($con);
			} else {
				oci_commit($con);
				echo json_encode(array('success' => true, 'data' => array('circuit_name' => $circuit_name)));
			}
		}
		break;
	case 'create_circuit':
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
    o_circuitid            NUMBER;
	";
		if (isset($_REQUEST['name']) && $query = $_REQUEST['name']) {
			$sql .= "i_name   VARCHAR2(100) := '" . $query . "'; 
			";
		}
		if (isset($_REQUEST['startLocId']) && $query = $_REQUEST['startLocId']) {
			$sql .= "i_startlocationid   NUMBER := " . $query . "; 
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
			$sql .= "i_circuitdef   NUMBER := " . $query . "; 
			";
		}
		if (isset($_REQUEST['bandwidthId']) && $query = $_REQUEST['bandwidthId']) {
			$sql .= "i_bandwidth   NUMBER := " . $query . "; 
			";
		}
		if ($circuitTypeId) {
			$sql .= "i_circuittype   NUMBER := " . $circuitTypeId . "; 
			";
		}

		if ($create_without_def) {
			$package_name = "CREATEDATACIRCUIT";
			$fields = "
o_errorcode, 
o_errortext, 
o_circuitid, 
o_startlogportid, 
o_endlogportid, 
i_circuitname, 
i_circuittypeid, 
i_startlocid, 
i_endlocid, 
i_startnodeid, 
i_endnodeid, 
i_startportid, 
i_endportid, 
i_bandwidthid, 
i_bandwidthkbps, 
i_direction";
		} else {
			$package_name = "CREATECIRCUIT";
			$fields = "
o_errorcode, 
o_errortext, 
o_circuitid, 
i_name, 
i_startlocationid, 
i_startnodeid, 
i_startportid, 
i_endlocationid, 
i_endnodeid, 
i_endportid, 
i_circuitdef, 
i_bandwidth
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

  :CIRID := o_circuitid;
  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;

END;

";
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
