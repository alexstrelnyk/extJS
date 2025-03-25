<?
include_once $_SERVER["DOCUMENT_ROOT"] . '/common/gconnect.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/common/portal_func.php';

if (isset($_REQUEST['roles'])) {
	//var_dump($_SESSION);
	if (isset($_SESSION['roles']))
		echo json_encode(array('success' => true, 'data' => $_SESSION['roles']));
	else
		echo json_encode(array('success' => false));
}

function checkAccess()
{
	if (isset($_SESSION['ad_login'])) {
		if (isset($_SESSION['application'])) {
			if (!isset($_SESSION['roles'])) $_SESSION['roles'] = array();
			unset($_SESSION['roles'][$_SESSION['application']]);

			$con = ksdb_connect('msuPortal');
			#			$sql = "select r.* from users u 
			#		join user_role ur on u.id = ur.iduser
			#		join role r on ur.idrole = r.id
			#		join application a on a.id = r.idapp
			#		where lower(u.login) like lower('".str_replace('@kyivstar.ua','',$_SESSION['ad_login'])."'||'@%kyivstar.ua') and (lower(a.name) = lower('".$_SESSION['application']."')
			#                or (a.name = 'Cramer navigator' and r.name = 'Site profitability' and lower('".$_SESSION['application']."')='reports') )";
			$sql = "select r.* from users u 
		  join user_role ur on u.id = ur.iduser
		  join role r on ur.idrole = r.id
		  join application a on a.id = r.idapp
		  where lower(u.login) like lower('" . str_replace('@kyivstar.ua', '', $_SESSION['ad_login']) . "'||'@%kyivstar.ua') and lower(a.name) = lower('" . $_SESSION['application'] . "')";
			$q = $con->exec($sql);
			$_SESSION['roles'][$_SESSION['application']] = array();
			while ($r = $q->fetch()) {
				$_SESSION['roles'][$_SESSION['application']][$r['NAME']] = $r['NAME'];
			}
			if (count($_SESSION['roles'][$_SESSION['application']]) > 0) return true;
		}
	}
	printAccessError();
}

function checkRole($role)
{
	if (isset($_SESSION['roles'][$_SESSION['application']][$role])) return true;
	return false;
}

function getLogin()
{

	// authentication from ip
	#	$con = ksdb_connect('msuPortal');
	#	$sql = "select u.login from users u 
	#		  join user_ip ip on u.id = ip.iduser
	#		  where ip.ip='".$GLOBALS['_SERVER']['REMOTE_ADDR']."'";
	#	$q=$con->exec($sql);
	#	  while ($r=$q->fetch())
	#		{
	#			$ad_login=str_replace('@kyivstar.ua','',$r['login']);
	#			if (isset($ad_login)) { $_SESSION['ad_login']=$ad_login; };
	#			return $_SESSION['ad_login'];
	#		};
	///////////////////////////

	if (isset($_SERVER['REDIRECT_REMOTE_USER'])) {
		$_SESSION['ad_login'] = $_SERVER['REDIRECT_REMOTE_USER'];
	}
	if (isset($_SERVER['REMOTE_USER'])) {
		$_SESSION['ad_login'] = $_SERVER['REMOTE_USER'];
	}

	$_SESSION['ad_login'] = str_replace('@kyivstar.ua', '', $_SESSION['ad_login']);

	if (isset($_SESSION['ad_login'])) {
		saveAppAudit($_SESSION['application'], $_SESSION['ad_login']);
		return $_SESSION['ad_login'];
	} else {
		printAccessError();
	}
}


function printAccessError()
{
	include $_SERVER['DOCUMENT_ROOT'] . '/msu/errors/access_error.php';
	die();
}

function insertLogin()
{
	if (isset($_SESSION['ad_login'])) {
		$con = ksdb_connect('msuPortal');
		$sql = "insert into users (login)
select (case when (select login from users where lower(login) like lower('" . $_SESSION['ad_login'] . "'||'@%kyivstar.ua') and rownum<2) is null then concat('" . $_SESSION['ad_login'] . "','@kyivstar.ua') else null end)";
		$q = $con->exec($sql);
	}
}
