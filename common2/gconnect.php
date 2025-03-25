<?
include_once $_SERVER["DOCUMENT_ROOT"] . '/common/portal_func.php';

function getOSSUsers()
{
  include $_SERVER["DOCUMENT_ROOT"] . '/cfg/host.php';
  return $host_cfg;
}

function getLDAP_DN($alias)
{
  $dn = array(
    'shrek_dhcpvoip' => 'cn=voip,cn=Users,cn=DHCP Service Config,dc=dhcp-voip,dc=sw,dc=tech,dc=local',
    'shrek_asterisk' => "dc=asterisk, dc=sw, dc=tech, dc=local",
    'shrek_radius' => "ou=pppoe,ou=users,dc=radius,dc=sw,dc=tech,dc=local"
  );
  return $dn[$alias];
}

function ksdb_connect($name, $login = '', $pass = '')
{
  include $_SERVER["DOCUMENT_ROOT"] . '/cfg/db.php';
  $conP = $db_cfg;
  if (isset($conP[$name])) {
    $param = $conP[$name]['param'];

    if ($login == '') {
      $login = $conP[$name]['login'];
      $pass = $conP[$name]['pass'];
    }
    if (isset($conP[$name]['driver'])) {
      if ($conP[$name]['driver'] == 'oci') {
        include_once $_SERVER["DOCUMENT_ROOT"] . '/common/db/oci.inc';
        return new KsOciConnection($login, $pass, $param);
      } elseif ($conP[$name]['driver'] == 'ext_dblib') {
        include_once $_SERVER["DOCUMENT_ROOT"] . '/common/db/ext_dblib.inc';
        return new KsExtDBLibConnection($login, $pass, $param);
      }
    }

    include_once $_SERVER["DOCUMENT_ROOT"] . '/common/db/pdo.inc';
    return new KsPdoConnection($login, $pass, $param);
  } else throw new Exception($name . ' connection - not configured!!!');
}

function connectFTP($name, $port = 21, $login = '', $pass = '')
{
  $con_cfg = getOSSUsers();

  if (isset($con_cfg[$name])) {
    $p = 21;
    $l = $con_cfg[$name]['login'];
    $pwd = $con_cfg[$name]['pass'];
    $ip = $con_cfg[$name]['ip'];

    if ($port <> 21) $p = $port;
    if ($login <> '') $l = $login;
    if ($pass <> '') $pwd = $pass;
    $con = ftp_connect($ip, $port);
    if ($con) {
      if (ftp_login($con, $l, $pwd))
        return $con;
      else
        return false;
    }
  }
}

function connectSFTP($name, $port = 22, $login = '', $pass = '')
{
  $con_cfg = getOSSUsers();
  if (isset($con_cfg[$name])) {
    $l = $con_cfg[$name]['login'];
    $pwd = $con_cfg[$name]['pass'];
    $ip = $con_cfg[$name]['ip'];


    if ($login <> '') $l = $login;
    if ($pass <> '') $pwd = $pass;
    if (!class_exists('Net_SFTP')) {
      require_once('net/sftp.php');
    }

    $sftp = new Net_SFTP($ip);
    if ($sftp->login($l, $pwd)) {
      return $sftp;
    }
    return false;
  }
}

function connectLDAP($alias)
{
  $con = array(
    'shrek' => array(
      //        'ip' => "127.0.0.1:1389",
      'ip' => "10.4.0.56",
      //        'ip' => "10.4.0.57",
      'login' => 'cn=shrek-web, dc=users, dc=sw, dc=tech, dc=local',
      'pass' => 'LoggerAdmin'
    )
  );
  $ds = ldap_connect($con[$alias]['ip']);
  ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
  ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
  $lb = ldap_bind($ds, $con[$alias]['login'], $con[$alias]['pass']);
  return $ds;
}

function ssh_exec($name, $cmd)
{
  $con_cfg = getOSSUsers();
  if (isset($con_cfg[$name])) {
    $login = $con_cfg[$name]['login'];
    $ip = $con_cfg[$name]['ip'];
    $pass = $con_cfg[$name]['pass'];


    $ssh = ssh2_connect($ip, 22);
    if (!$ssh) return "Error : connect failed with host $ip!";
    $fp = ssh2_fingerprint($ssh, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX);
    if (!$fp) return "Error : finger print failed for $ip!";

    $login_res = ssh2_auth_password($ssh, $login, $pass);
    if (!$login_res) return "Error : login failed for host $ip with user $login!";
    $st = ssh2_exec($ssh, $cmd);
    if (!$st) return "Error : can't open command result stream!";
    $err_st = ssh2_fetch_stream($st, SSH2_STREAM_STDERR);
    stream_set_blocking($st, true);
    $res = '';
    while ($line = fgets($st)) {
      $res .= $line;
    }
    if ($res == '')
      $res = stream_get_contents($err_st);
    return $res;
  }
  return "Error : connection name not found!";
}
