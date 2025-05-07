<?
//error_reporting(E_ALL);

include_once "../../../../../../common/gconnect.php";
include_once "../../../../../../common/getLogin.php";
include_once "../../../../../../common2/tv_json.php";
include_once "../../../../../../common2/func2.php";


$action = $_REQUEST['action'];
$xaction = $_REQUEST['xaction'];

getLogin();
checkAccess();
$role = (isset($_SESSION['roles']['Cramer navigator']['Standart user']) ? 1 : 0);

$con = ksdb_connect('cramer_admin');

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
		$sql = "select c.circuittypeid, c.name from CRAMER.CIRCUITTYPE_M c  
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND c.name LIKE '%" . $query . "%' ";
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
		$sql = "select b.circuittypebandwidthid, b.ctb2bandwidth from CRAMER.CIRCUITTYPEBANDWIDTH b
		WHERE rownum < 20 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND b.ctb2bandwidth LIKE '%" . $query . "%' ";
		}
		if (isset($_REQUEST['locid']) && $locid = $_REQUEST['locid']) {
			$sql .= "AND b.ctb2circuittype = " . $locid . " ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'create_circuit':
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
			$sql .= "i_startnodename   NUMBER := " . $query . "; ";
		}
		if (isset($_REQUEST['startPortName']) && $query = $_REQUEST['startPortName']) {
			$sql .= "i_startportname   NUMBER := " . $query . "; ";
		}
		if (isset($_REQUEST['endNodeName']) && $query = $_REQUEST['endNodeName']) {
			$sql .= "i_endnodename   NUMBER := " . $query . "; ";
		}
		if (isset($_REQUEST['endPortName']) && $query = $_REQUEST['endPortName']) {
			$sql .= "i_endportname   NUMBER := " . $query . "; ";
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

    DBMS_OUTPUT.put_line('Error Code: ' || o_errorcode);
    DBMS_OUTPUT.put_line('Error Text: ' || o_errortext);
    DBMS_OUTPUT.put_line('Circuit Name: ' || o_name);
END;
";
		//exit($sql);
		sendJSONFromSQL($con, $sql, false);
		break;
}
