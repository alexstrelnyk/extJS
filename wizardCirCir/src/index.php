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
			$ar['uses'] = getSQLData($con, "select ce.usedby2circuit id, c.name from CRAMER.CIRCUITCIRCUIT_o ce
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
		$sql = "select ce.usedby2circuit id, c.name from CRAMER.CIRCUITCIRCUIT_o ce
JOIN CRAMER.CIRCUIT_O c ON c.circuitid = ce.uses2circuit
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND (ce.uses2circuit LIKE '%" . $query . "%' OR c.name LIKE '%" . $query . "%')";
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
	case 'add_uses':
		$sql = "
		
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
	";
		if ($cir_id = $_REQUEST['cir_id']) {
			$sql .= "i_usescircuitid   NUMBER := " . $cir_id . "; ";
		}
		if ($id = $_REQUEST['id']) {
			//		$sql .= "i_usedbycircuitid   NUMBER := ".$id."; ";
		}
		$sql .= "i_usedbycircuitid   NUMBER := 69705418; ";
		$sql .= "i_circuitnumber   NUMBER := 1; ";
		$sql .= "i_routesequence   NUMBER := 1; ";
		$sql .= "
BEGIN

  CRAMER.getsession();

    PKGCIRCUIT.addcircuit2circuit(
o_errorcode, 
o_errortext, 
i_usedbycircuitid, 
i_usescircuitid, 
i_circuitnumber, 
i_routesequence 
    );

    DBMS_OUTPUT.put_line('Error Code: ' || o_errorcode);
    DBMS_OUTPUT.put_line('Error Text: ' || o_errortext);
	
  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;
END;
";

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
		echo json_encode(['success' => true, 'data' => ['errorcode' => $errorcode, 'errortext' => $errortext,]]);
		break;
	case 'del_uses':
		$sql = "
		
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
	";
		if ($cir_id = $_REQUEST['cir_id']) {
			$sql .= "i_usescircuitid   NUMBER := " . $cir_id . "; ";
		}
		if ($id = $_REQUEST['id']) {
			$sql .= "i_usedbycircuitid   NUMBER := " . $id . "; ";
		}
		//	$sql .= "i_usedbycircuitid   NUMBER := 69705418; ";
		//	$sql .= "i_usescircuitid   NUMBER := 70414789; ";
		$sql .= "i_circuitnumber   NUMBER := 1; ";
		$sql .= "i_routesequence   NUMBER := 1; ";
		$sql .= "
BEGIN

  CRAMER.getsession();

    PKGCIRCUIT.removecircuitfromcircuit(
o_errorcode, 
o_errortext, 
i_usedbycircuitid, 
i_usescircuitid, 
i_routesequence
    );

    DBMS_OUTPUT.put_line('Error Code: ' || o_errorcode);
    DBMS_OUTPUT.put_line('Error Text: ' || o_errortext);
	
  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;
END;
";
		//		echo __FILE__.' '.__LINE__.'<pre>';print_r($sql).'</pre>';die;

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
		echo json_encode(['success' => true, 'data' => ['errorcode' => $errorcode, 'errortext' => $errortext,]]);
		break;
	case 'add_service':
		$sql = "
		
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
	";
		if ($cir_id = $_REQUEST['cir_id']) {
			$sql .= "i_objectid   NUMBER := " . $cir_id . "; ";
		}
		if ($id = $_REQUEST['id']) {
			$sql .= "i_serviceid   NUMBER := " . $id . "; ";
		}
		$sql .= "i_dimobjectid   NUMBER := 3; ";
		$sql .= "i_relationshipid   NUMBER := 1800000001; ";
		$sql .= "i_sequence   NUMBER := 1; ";
		$sql .= "
BEGIN

  CRAMER.getsession();

    pkgservice.addobject2service(
o_errorcode, 
o_errortext, 
i_serviceid, 
i_dimobjectid, 
i_objectid, 
i_relationshipid, 
i_sequence
    );

    DBMS_OUTPUT.put_line('Error Code: ' || o_errorcode);
    DBMS_OUTPUT.put_line('Error Text: ' || o_errortext);
	
  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;
END;
";
		//	echo __FILE__.' '.__LINE__.'<pre>';print_r($sql).'</pre>';die;

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
		echo json_encode(['success' => true, 'data' => ['errorcode' => $errorcode, 'errortext' => $errortext,]]);
		break;
	case 'add_link':
		$sql = "
		
		DECLARE
    o_errorcode            NUMBER;
    o_errortext            VARCHAR2(4000);
    i_validatetypes            VARCHAR2(4000);
    I_ROUTEDIRECTION            VARCHAR2(4000);
    I_LOADBALANCERATIO            VARCHAR2(4000);
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
	
  :ERRCODE := o_errorcode;
  :ERRTEXT := o_errortext;
END;
";
		//	echo __FILE__.' '.__LINE__.'<pre>';print_r($sql).'</pre>';die;

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
		echo json_encode(['success' => true, 'data' => ['errorcode' => $errorcode, 'errortext' => $errortext,]]);
		break;
}
