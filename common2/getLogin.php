<?
include_once $_SERVER["DOCUMENT_ROOT"] . "/common/gconnect.php";
include_once $_SERVER["DOCUMENT_ROOT"] . '/common/portal_func.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/common2/tv_json.php';
//error_reporting(E_ALL);

//if ($GLOBALS['_SERVER']['REMOTE_ADDR']=='10.34.2.21')
//{
//    var_dump($_SERVER);
//}

if ((isset($_REQUEST['login'])) && (isset($_REQUEST['pass'])) && (isset($_REQUEST['domain']))) {

	$ldapHost = "adkyivmsc.kyivstar.ua";
	#$ldapHost = "kv-dc-02.kyivstar.ua";
	#$ldapPort = "389";
	$ldapPort = "636";

	$username = strtolower($_REQUEST['login']);
	$password = $_REQUEST['pass'];
	$domain = $_REQUEST['domain'];
	$err = '';

	if ($domain == 'kyivstar.ua')
		$suffix = "@" . $domain;
	else $suffix = "@" . $domain . ".kyivstar.ua";

	#$ldapLink =ldap_connect($ldapHost, $ldapPort)
	$ldapLink = ldap_connect("ldaps://" . $ldapHost . ":" . $ldapPort)
		or showLogin("Unavailable connection to AD domain controller. Retry later or contact with administrator (Igor.Tomashivskiy).", $username, $domain);
	ldap_set_option($ldapLink, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldapLink, LDAP_OPT_REFERRALS, 0);

	$ldapbind = ldap_bind($ldapLink, $username . $suffix, $password);
	if (($ldapbind) && ($password != '')) {
		$url = $_SESSION['ad_url'];
		$_SESSION['ad_login'] = $username;
		//saveUser($username.$suffix,$password);
		saveUser($username . $suffix, '');
		$host = $_SERVER['HTTP_HOST'];
		header("Location:http://$host$url");
		die();
	} else {

		showLogin("Bad login or password :" . ldap_error($ldapLink), $username, $domain);
	}
}

if (isset($_REQUEST['roles'])) {
	//var_dump($_SESSION);
	if (isset($_SESSION['roles']))
		echo json_encode(array('success' => true, 'data' => $_SESSION['roles']));
	else
		echo json_encode(array('success' => false));
}

function getRole()
{
	if (isset($_SESSION['application'])) {
		if (!isset($_SESSION['roles'])) $_SESSION['roles'] = array();
		//var_dump($_SESSION);
		unset($_SESSION['roles'][$_SESSION['application']]);
		if (!isset($_SESSION['roles'][$_SESSION['application']])) {
			$con = ksdb_connect('msuPortal');
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
		}
		if (count($_SESSION['roles'][$_SESSION['application']]) > 0) return true;
	}
}

function checkAccess($url, $by_db = false)
{
	//Just for me
	/*if ($GLOBALS['_SERVER']['REMOTE_ADDR']=='10.44.65.171')
  {
    //var_dump($GLOBALS);
    $_SESSION['ad_login']='vitaliy.timkov';
    //return;
  }*/


	if (isset($_SESSION['ad_login'])) {
		if ($by_db) {
			if (isset($_SESSION['application'])) {
				if (!isset($_SESSION['roles'])) $_SESSION['roles'] = array();
				//var_dump($_SESSION);
				unset($_SESSION['roles'][$_SESSION['application']]);
				if (!isset($_SESSION['roles'][$_SESSION['application']])) {
					$con = ksdb_connect('msuPortal');
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
				}
				if (count($_SESSION['roles'][$_SESSION['application']]) > 0) return true;
			}
		} else {
			$user = $_SESSION['ad_login'];
			$path = explode('/', $url);

			$filename = '';
			for ($i = 0; $i < count($path) - 1; $i++) {
				$filename = "./" . str_repeat("../", $i) . ".ad_users";
				if (file_exists($filename)) break;
				$filename = '';
			}
			if ($filename == '') return true;

			$fh = fopen($filename, "r");
			while (!feof($fh)) {
				if (strtolower(trim(fgets($fh))) == strtolower($user)) {
					fclose($fh);
					return true;
				}
			}
			fclose($fh);
		}

		$temp = ''; //print_r($con,true);
		saveUser($_SESSION['ad_login'] . "@kyivstar.ua", '');
		echo "<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
	<link rel='stylesheet' type='text/css' href='/js/extJS/resources/css/ext-all.css'>

	<script type='text/javascript' src='/js/extJS/adapter/ext/ext-base.js'></script>
	<script type='text/javascript' src='/js/extJS/ext-all-debug.js'></script>
	
        <script type='text/javascript' src='/common2/wLogin.js'></script>
        <title>KS: Autentification</title>
    </head>
    <body>
	 $temp
    </body>
	<script type='text/javascript'>
	    Ext.onReady(function()
	    {
		Ext.Msg.show(
		 {
		     title: 'Critical error',
		     msg: 'You have not enought rights to access this aplication! (Login:" . $_SESSION['ad_login'] . ") Access order in OIM.',
		     buttons: Ext.Msg.OK,
		     icon: Ext.MessageBox.ERROR
		 });
	    })	    
	</script>

</html>";
		die();
	} else {
		getLogin($url);
	}
}

function checkRole($role)
{
	if (isset($_SESSION['roles'][$_SESSION['application']][$role])) return true;
	return false;
}

function getLogin($url)
{
	/* if ($GLOBALS['_SERVER']['REMOTE_ADDR']=='10.44.65.171')
  {
    $_SESSION['ad_login']='vitaliy.timkov';
    return 'vitaliy.timkov';
  }    */

	// authentication from ip
	//	$con = ksdb_connect('msuPortal');
	//	$sql = "select u.login from users u 
	//		  join user_ip ip on u.id = ip.iduser
	//		  where ip.ip='".$GLOBALS['_SERVER']['REMOTE_ADDR']."'";
	//	$q=$con->exec( $sql);
	//	  while ($r=$q->fetch())
	//		{
	//			$ad_login=str_replace('@kyivstar.ua','',$r['login']);
	//			if (isset($ad_login)) { $_SESSION['ad_login']=$ad_login; };
	//			return $_SESSION['ad_login'];
	//		};
	///////////////////////////

	if (isset($_SESSION['ad_login'])) {
		saveAudit($_SESSION['ad_login']);
		return $_SESSION['ad_login'];
	} else {
		$_SESSION['ad_url'] = $url;
		showLogin('', '', '');
	}
}

function showLogin($err = '', $u = '', $d = '')
{

	$errt = '';
	if ($d == '') {
		$d = 'kyivstar.ua';
	}
	if ($err != '') {
		$errt = "Ext.Msg.show(
		 {
		     title: 'Critical error',
		     msg: '$err',
		     buttons: Ext.Msg.OK,
		     icon: Ext.MessageBox.ERROR
		 }); ";
	}
	echo "<html>
    <head>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
	<link rel='stylesheet' type='text/css' href='/js/extJS/resources/css/ext-all.css'>

	<script type='text/javascript' src='/js/extJS/adapter/ext/ext-base.js'></script>
	<script type='text/javascript' src='/js/extJS/ext-all-debug.js'></script>
	
        <script type='text/javascript' src='/common2/wLogin.js'></script>
        <title>KS: Autentification</title>
    </head>
    <body>
    </body>
	<script type='text/javascript'>
	    Ext.onReady(function()
	    {
		var win = new wLogin({},'$u', '$d');
		win.show();
		$errt
	    })	    
	</script>

</html>";
	exit;
}

function saveAudit($login)
{
	$app = '';
	if (isset($_SESSION['application'])) $app = $_SESSION['application'];
	saveAppAudit($app, $login);
}

function saveUser($login, $pass)
{
	$con = ksdb_connect('msuPortal');
	$con2 = ksdb_connect('msuPortal');
	$r = $con->exec("select id, pass, email from users where lower(login) = lower('$login')");
	if ($row = $r->fetch()) {
		if ($row['PASS'] != $pass) {
			$sql = "update users set pass = '$pass' where id = " . $row['ID'];
			$con2->exec($sql);
		}
		if ($row['email'] == '') {
			//				$ldapHost = "adkyivmsc.kyivstar.ua";
			//				$ldapPort = "3268";
			$ldapHost = "kv-dc-02.kyivstar.ua";
			$ldapPort = "389";

			$ds = ldap_connect($ldapHost, $ldapPort);
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
			ldap_bind($ds, $login, $pass);
			$dn = "dc=kyivstar,dc=UA";
			#				$filter="(&(userprincipalname=".$r['login'].")(!(objectclass=computer))(objectclass=user))";
			$filter = "(&(userprincipalname=" . $login . ")(!(objectclass=computer))(objectclass=user))";
			$sr = ldap_search($ds, $dn, $filter);
			$inf = ldap_get_entries($ds, $sr);
			if ($inf['count'] == 1) {
				if (isset($inf[0]['mail'])) {
					if ($inf[0]['mail'][0] != 'no value') {
						$con2->exec("update users set email = '" . $inf[0]['mail'][0] . "' where id = " . $row['ID']);
					}
				}
			}
		}
	} else {
		$sql = "INSERT INTO users(login, pass) VALUES(lower('$login'), '$pass')";
		$con2->exec($sql);
	}
}
