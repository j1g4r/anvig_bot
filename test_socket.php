<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$port = 7687;
$timeout = 5;

echo "Testing raw socket connection to $host:$port...\n";

// Try IPv4
$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($socket) {
    echo "SUCCESS: Connected via fsockopen!\n";
    fclose($socket);
} else {
    echo "fsockopen FAILED: errno=$errno, errstr=$errstr\n";
}

// Try stream socket
$socketAddr = "tcp://127.0.0.1:$port";
echo "\nTrying stream_socket_client to $socketAddr...\n";
$stream = @stream_socket_client($socketAddr, $errno, $errstr, $timeout);
if ($stream) {
    echo "SUCCESS: Connected via stream_socket_client (IPv4)!\n";
    fclose($stream);
} else {
    echo "stream_socket_client FAILED: errno=$errno, errstr=$errstr\n";
}

// Try IPv6
$socketAddr6 = "tcp://[::1]:$port";
echo "\nTrying stream_socket_client to $socketAddr6 (IPv6)...\n";
$stream6 = @stream_socket_client($socketAddr6, $errno6, $errstr6, $timeout);
if ($stream6) {
    echo "SUCCESS: Connected via stream_socket_client (IPv6)!\n";
    fclose($stream6);
} else {
    echo "stream_socket_client (IPv6) FAILED: errno=$errno6, errstr=$errstr6\n";
}

echo "\nPHP stream transports available:\n";
var_dump(stream_get_transports());
