<?php
use Transmission\RPC;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$program = array_shift($argv);

if ($argc < 5) {
    die('Usage : ' . basename($argv[0]) . " <server:port> <user> <password> <command> [params]\n");
}

$server = 'http://' . array_shift($argv) . '/transmission/rpc';
$username = array_shift($argv);
$password = array_shift($argv);
$command = array_shift($argv);
$params = $argv;

try {
    $rpc = new RPC($server, $username, $password);
    
    switch ($command) {
        case 'add':
            if (count($params) != 1) {
                throw new RuntimeException("Invalid number of params");
            }
            $result = $rpc->request('torrent-add', array(
            	'metainfo' => base64_encode(file_get_contents($params[0]))
            ));
            var_dump($result);
            break;
            
        case 'sstats':
            $result = $rpc->sstats();
            var_dump($result);
            break;
            
        default:
            throw new Exception("Commande inconnue");
    }
} catch (Exception $e) {
    echo "Error : " . $e->getMessage() . "\n";
}
