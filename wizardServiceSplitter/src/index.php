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
			$ar['values'] = getSQLData($con, "select c.circuitid,c.name,c.objectid as service,l.name as location,n.name as node,pp.name as port from circuit_o c
left join location_o l on l.locationid=c.circuit2startlocation
left join node_o n on n.nodeid=c.circuit2startnode
left join port_o p on p.portid=c.circuit2startport
left join port_o pp on pp.portid=p.parentport2port
where c.circuit2endnode=3265384
and c.circuit2circuittype=1900000039 and c.subtype='FTTB'
and rownum < 20");
			exit(json_encode(array('success' => true, 'data' => $ar)));
		} else {
			exit(json_encode(array('success' => false, 'data' => [])));
		}
		break;
}
