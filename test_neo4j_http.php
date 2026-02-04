<?php
require __DIR__ . '/vendor/autoload.php';

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try HTTP
$uri = 'http://localhost:7474';
$user = 'neo4j';
$password = 'ANVIG_Graph_2025';

echo "Testing HTTP connection to Neo4j...\n";
echo "URI: $uri\n\n";

try {
    $client = ClientBuilder::create()
        ->withDriver('default', $uri, Authenticate::basic($user, $password))
        ->withDefaultDriver('default')
        ->build();
    
    echo "Client built successfully!\n";
    
    $result = $client->run('RETURN 1 as test');
    echo "Query executed!\n";
    
    foreach ($result as $record) {
        echo "Result: " . json_encode($record->toArray()) . "\n";
    }
    
    echo "\nSUCCESS: HTTP Connection working!\n";
} catch (Exception $e) {
    echo "ERROR: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
}
