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
			$ar['used'] = getSQLData($con, "select c.circuitid id, c.name from CRAMER.CIRCUITCIRCUIT_o ce
				JOIN CRAMER.CIRCUIT_O c ON c.circuitid = ce.usedby2circuit
				WHERE ce.usedby2circuit = " . $cir_id);
			$ar['uses'] = getSQLData($con, "select c.circuitid id, c.name from CRAMER.CIRCUITCIRCUIT_o ce
				JOIN CRAMER.CIRCUIT_O c ON c.circuitid = ce.uses2circuit
				WHERE ce.uses2circuit = " . $cir_id);
			$ar['services'] = getSQLData($con, "SELECT s.SERVICEID id, s.name FROM CRAMER.serviceobject_o so
				JOIN CRAMER.service_o s ON s.serviceid = so.serviceobject2service
				WHERE so.serviceobject2object = " . $cir_id);
			$ar['links'] = getSQLData($con, "SELECT  l.linkid id, l.name FROM CRAMER.linkcircuit_o lc
				JOIN CRAMER.link_o l ON l.linkid = lc.linkcircuit2link
				WHERE lc.linkcircuit2circuit = " . $cir_id);

			exit(json_encode(array('success' => true, 'data' => $ar)));
		} else {
			exit(json_encode(array('success' => false, 'data' => [])));
		}
		break;
	case 'get_used':
		$sql = "select c.circuitid id, c.name from CRAMER.CIRCUIT_O c
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND (c.circuitid LIKE '%" . $query . "%' OR c.name LIKE '%" . $query . "%')";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_uses':
		$sql = "select c.circuitid id, c.name from CRAMER.CIRCUIT_O c
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND (c.circuitid LIKE '%" . $query . "%' OR c.name LIKE '%" . $query . "%')";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_service':
		$sql = "select s.serviceid id, s.name from CRAMER.service_o s
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND (s.serviceid LIKE '%" . $query . "%' OR s.name LIKE '%" . $query . "%')";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_link':
		$sql = "select l.linkid id, l.name from CRAMER.link_o l
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND (l.linkid LIKE '%" . $query . "%' OR l.name LIKE '%" . $query . "%')";
		}

		sendJSONFromSQL($con, $sql, false);
		break;
	case 'add_link':
		$sql = "
		
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
	";
		if ($cir_id = $_REQUEST['cir_id']) {
			$sql .= "i_circuitid   NUMBER := " . $cir_id . "; ";
		}
		if ($id = $_REQUEST['id']) {
			$sql .= "i_linkid   NUMBER := " . $id . "; ";
		}
		$sql .= "i_linkseqnumber   NUMBER := 1; ";
		$sql .= "i_routesequence   NUMBER := 1; ";
		$sql .= "
BEGIN

  CRAMER.getsession();

    PKGCIRCUIT.addlink2circuit(
        o_errorcode, 
o_errortext, 
i_circuitid, 
i_linkid, 
i_linkseqnumber, 
i_routesequence, 
i_validatetypes, 
i_routedirection, 
i_loadbalanceratio
		

    );

    DBMS_OUTPUT.put_line('Error Code: ' || o_errorcode);
    DBMS_OUTPUT.put_line('Error Text: ' || o_errortext);
	
END;
";
		//	echo __FILE__.' '.__LINE__.'<pre>';print_r($sql).'</pre>';die;

		$pcon = $db_cfg['cramer_admin'];

		$con = oci_connect($pcon['login'], $pcon['pass'], $pcon['param']);

		$st = oci_parse($con, $sql);

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
			oci_commit($con);
			echo json_encode(array('success' => true, 'data' => array('circuit_name' => $circuit_name)));
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
