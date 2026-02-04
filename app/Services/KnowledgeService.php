<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Knowledge Graph Service with Python Bridge
 * Bypasses broken laudis/neo4j-php-client using Python driver
 */
class KnowledgeService
{
    protected string $neo4jUri;
    protected string $neo4jUser;
    protected string $neo4jPassword;
    protected string $projectPath;
    protected string $venvPath;
    
    public function __construct()
    {
        $this->neo4jUri = env('NEO4J_URI', 'bolt://localhost:7687');
        $this->neo4jUser = env('NEO4J_USER', 'neo4j');
        $this->neo4jPassword = env('NEO4J_PASSWORD', env('NEO4J_PASS', 'password'));
        $this->projectPath = base_path();
        $this->venvPath = $this->projectPath . '/.venv';
    }
    
    public function runQuery(string $cypher, array $parameters = []): array
    {
        $env = [
            'NEO4J_URI' => $this->neo4jUri,
            'NEO4J_USER' => $this->neo4jUser,
            'NEO4J_PASSWORD' => $this->neo4jPassword,
        ];
        
        $paramsJson = json_encode($parameters);
        $cypherEscaped = escapeshellarg($cypher);
        
        $command = sprintf(
            'cd %s && . %s/bin/activate && python3 scripts/neo4j_bridge.py %s %s 2>&1',
            escapeshellarg($this->projectPath),
            escapeshellarg($this->venvPath),
            $cypherEscaped,
            escapeshellarg($paramsJson)
        );
        
        $result = Process::env($env)->run($command);
        
        if (!$result->successful()) {
            $error = $result->errorOutput() ?: $result->output();
            Log::error('Neo4j Python bridge execution failed', ['error' => $error, 'exit_code' => $result->exitCode()]);
            throw new RuntimeException("Neo4j query failed: {$error}");
        }
        
        $output = $result->output();
        $data = json_decode($output, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid JSON from Neo4j bridge', ['output' => $output]);
            throw new RuntimeException("Invalid response from Neo4j bridge: {$output}");
        }
        
        if (!($data['success'] ?? false)) {
            $error = $data['error'] ?? 'Unknown error';
            Log::error('Neo4j query error', ['error' => $error]);
            throw new RuntimeException("Neo4j error: {$error}");
        }
        
        return $data['records'] ?? [];
    }
    
    public function executeRawQuery(string $cypher, array $parameters = []): array
    {
        $env = [
            'NEO4J_URI' => $this->neo4jUri,
            'NEO4J_USER' => $this->neo4jUser,
            'NEO4J_PASSWORD' => $this->neo4jPassword,
        ];
        
        $paramsJson = json_encode($parameters);
        $cypherEscaped = escapeshellarg($cypher);
        
        $command = sprintf(
            'cd %s && . %s/bin/activate && python3 scripts/neo4j_bridge.py %s %s 2>&1',
            escapeshellarg($this->projectPath),
            escapeshellarg($this->venvPath),
            $cypherEscaped,
            escapeshellarg($paramsJson)
        );
        
        $result = Process::env($env)->run($command);
        
        if (!$result->successful()) {
            return ['success' => false, 'error' => $result->errorOutput() ?: $result->output(), 'records' => [], 'counters' => []];
        }
        
        $data = json_decode($result->output(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON', 'records' => [], 'counters' => []];
        }
        
        return $data;
    }
    
    public function indexEntity(string $label, array $properties, ?string $mergeKey = null): bool
    {
        $label = preg_replace('/[^a-zA-Z0-9_]/', '', $label);
        
        if ($mergeKey && isset($properties[$mergeKey])) {
            $cypher = sprintf('MERGE (n:%s {%s: $%s}) SET n += $props RETURN n', $label, $mergeKey, $mergeKey);
            $params = [$mergeKey => $properties[$mergeKey], 'props' => $properties];
        } else {
            $cypher = sprintf('CREATE (n:%s) SET n = $props RETURN n', $label);
            $params = ['props' => $properties];
        }
        
        try {
            $result = $this->executeRawQuery($cypher, $params);
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Entity indexing failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function relateEntities(string $fromLabel, array $fromProps, string $toLabel, array $toProps, string $relationType, array $relProps = []): bool
    {
        $fromLabel = preg_replace('/[^a-zA-Z0-9_]/', '', $fromLabel);
        $toLabel = preg_replace('/[^a-zA-Z0-9_]/', '', $toLabel);
        $relationType = preg_replace('/[^a-zA-Z0-9_]/', '', $relationType);
        
        $cypher = sprintf(
            'MATCH (a:%s {id: $from_id}), (b:%s {id: $to_id}) MERGE (a)-[r:%s]->(b) SET r += $rel_props RETURN r',
            $fromLabel, $toLabel, $relationType
        );
        
        $params = [
            'from_id' => $fromProps['id'] ?? null,
            'to_id' => $toProps['id'] ?? null,
            'rel_props' => $relProps,
        ];
        
        try {
            $result = $this->executeRawQuery($cypher, $params);
            return $result['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('Relationship creation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function relate(string $fromLabel, string $fromId, string $toLabel, string $toId, string $relationType, array $properties = []): bool
    {
        return $this->relateEntities(
            $fromLabel, ['id' => $fromId],
            $toLabel, ['id' => $toId],
            $relationType, $properties
        );
    }
    
    public function query(string $cypher, array $parameters = []): array
    {
        return $this->runQuery($cypher, $parameters);
    }
    
    public function getClient(): ?object
    {
        Log::warning('getClient() deprecated - using Python bridge');
        return null;
    }
}
