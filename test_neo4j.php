<?php
require __DIR__ . '/vendor/autoload.php';

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;

$uri = 'bolt://localhost:7687';
$user = 'neo4j';
$password = 'ANVIG_Graph_2025';

echo "Testing Neo4j connection...\n";
echo "URI: $uri\n";
echo "User: $user\n";
echo "Password: ***\n\n";

try {
    $client = ClientBuilder::create()
        ->withDriver('default', $uri, Authenticate::basic($user, $password))
        ->build();
    
    echo "Client built successfully!\n";
    
    $result = $client->run('RETURN 1 as test');
    echo "Query executed!\n";
    
    foreach ($result as $record) {
        echo "Result: " . json_encode($record->toArray()) . "\n";
    }
    
    echo "\nSUCCESS: Connection working!\n";
} catch (Exception $e) {
    echo "ERROR: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
