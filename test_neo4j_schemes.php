<?php
require __DIR__ . '/vendor/autoload.php';

use Laudis\Neo4j\Authentication\Authenticate;
use Laudis\Neo4j\ClientBuilder;

error_reporting(E_ALL);
ini_set('display_errors', 1);

$schemes = ['bolt', 'neo4j', 'bolt+s', 'neo4j+s'];
$user = 'neo4j';
$password = 'ANVIG_Graph_2025';

foreach ($schemes as $scheme) {
    $uri = "{$scheme}://localhost:7687";
    echo "\n========================================\n";
    echo "Testing: $uri\n";
    echo "========================================\n";
    
    flush();
    
    try {
        $client = ClientBuilder::create()
            ->withDriver('default', $uri, Authenticate::basic($user, $password))
            ->withDefaultDriver('default')
            ->build();
        
        echo "Client built successfully!\n";
        
        $result = $client->run('RETURN 1 as test');
        echo "Query executed! Result: " . $result->first()->get('test') . "\n";
        echo "SUCCESS!\n";
        break;
    } catch (Exception $e) {
        echo "FAILED: " . get_class($e) . "\n";
        echo "Message: " . $e->getMessage() . "\n";
    }
}
