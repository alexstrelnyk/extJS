<?php
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
		WHERE rownum < 10 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND l.name LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_loc_type':
		$sql = "select distinct(l.location2locationtype) type, l.locationid from CRAMER.location_o l 
		WHERE rownum < 10 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND l.location2locationtype LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_node':
		$sql = "SELECT n.name, n.nodeid FROM CRAMER.node_o n
		WHERE rownum < 10 ";
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
		WHERE rownum < 10 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND p.name LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_port_bandwidth':
		$sql = "SELECT distinct(p.port2bandwidth) bandwidth, p.portid FROM CRAMER.port_o p
		WHERE rownum < 10 ";
		if (isset($_REQUEST['query']) && $query = $_REQUEST['query']) {
			$sql .= "AND p.port2bandwidth LIKE '%" . $query . "%' ";
		}
		sendJSONFromSQL($con, $sql, false);
		break;
	case 'get_circuit_name':
		//exit('asd');
		break;
}
