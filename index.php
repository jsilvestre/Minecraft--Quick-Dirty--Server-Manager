<?php

session_start();

set_include_path(get_include_path() . PATH_SEPARATOR . './lib/phpseclib');

include('Net/SSH2.php');
include('Net/SFTP.php');
include('options.php');

$start = microtime(true);

$_SESSION['login'] = !empty($_SESSION['login']) ? $_SESSION['login'] === true ? true : false : false;
$error = "";

if(isset($_POST['login']))
{
    if(!empty($_POST['password']) && $_POST['password'] == 'bushisgay')
    {
        $_SESSION['login'] = true;
    }
    else
    {
        $error = "Vous devez spécifier le (bon) code.";
    }
}

if(!$_SESSION['login'])
{
    if(!empty($error))
    {
        echo '<p>'.$error.'</p>';
    }
?>
<form method="post" action="index.php">
    <input type="text" name="password" />
    <input type="submit" name="login" value="Login" />
</form>
<?php
}
else
{

$feedback = !empty($_GET['msg']) ? $_GET['msg'] : null;
$ndd = ''; // your server ip
$login = ''; // your login
$password = ''; // your password

$ssh = new Net_SSH2($ndd);
$sftp = new Net_SFTP($ndd);
if (!$ssh->login($login, $password) || !$sftp->login($login, $password)) {
    $feedback = "Impossible de se connecter au serveur.";
    $fatalError = true;
}

$history = array();

if(!isset($fatalError) || !$fatalError)
{
$list = $ssh->exec('ls');    

$list = nl2br($list);
$list = explode('<br />',$list);

$temp = array();
foreach($list as $value)
{
    if(!empty($value) && preg_match('#.*minecraft.*#',$value))
    {
        $temp[] = $value;
    }
    
}

$temp = array_reverse($temp);
$temp = explode('_',$temp[0]);

$version = $temp[1];
    
$status = $ssh->exec('./mc.sh status '.$version);
$status = preg_match('#.*not running.*#',$status) ? 'Offline' : 'Online';

if(!empty($_GET['action']) && $status == 'Online')
{   
    switch($_GET['action'])
    {
        case 'start':
            $feedback = $ssh->exec('./mc.sh start '.$version);
            break;
        case 'stop':
            $feedback = $ssh->exec('./mc.sh stop '.$version);
            break;
        case 'restart':
            $feedback = $ssh->exec('./mc.sh restart '.$version);        
            break;
        case 'backup':
            $feedback = $ssh->exec('./mc.sh backup '.$version);
            $temp = nl2br($feedback);
            $temp = explode('<br />',$temp);
            $filename = $temp[count($temp)-3];
            $filename = trim($filename);
        
            $path = 'minecraft_'.$version.'/backups/'.$filename;
            $local_path = 'backups/'.$filename;
            //$sftp->get($path,$local_path); // uncomment to retrieve the file in the web server
            $feedback = 'Backup done: '.$filename. ' in '.(microtime(true) - $start).' seconds';
            break;
    }
    
    header('Location: index.php?msg='.$feedback);
}
}

?>
<h1>Vous êtes connecté</h1>

<?php

if(isset($fatalError))
{
    echo "<p>".$feedback."</p>";
}
else
{
?>

<p>Le serveur est actuellement <?php echo $status; ?>.</p>

<?php

if(!empty($feedback))
{
    echo "<p>".$feedback."</p>";
}

?>

<h2>Actions possibles</h2>
<ul>
    <li><a href="index.php?action=start">Start</a></li>
    <li><a href="index.php?action=stop">Stop</a></li>
    <li><a href="index.php?action=restart">Restart</a></li>
    <li><a href="index.php?action=backup">Backup</a></li>
</ul>

<?php
}
?>

<?php

$options = new Options();
$options->loadOptions('server.properties');


?>

<!--<h2>Historique des backups</h2>
<ul>
    <?php
    foreach($history as $file)
    {    
    ?>
        <li><a href="backups/<?php echo $file; ?>"><?php echo $file; ?></a></li>
    <?php
    }
    ?>          
</ul>-->

<?php
}    
    session_write_close();
?>