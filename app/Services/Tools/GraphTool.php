<?php

namespace App\Services\Tools;

use App\Services\KnowledgeService;

class GraphTool implements ToolInterface
{
    protected KnowledgeService $knowledge;

    public function __construct(KnowledgeService $knowledge)
    {
        $this->knowledge = $knowledge;
    }

    public function name(): string
    {
        return 'graph_knowledge';
    }

    public function description(): string
    {
        return 'Manage and query the persistent Knowledge Graph. Use this to remember relationships between entities, concepts, and projects.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['upsert_node', 'relate', 'query'],
                    'description' => 'The graph operation to perform.',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Node label (e.g. Person, Project, Concept).',
                ],
                'properties' => [
                    'type' => 'object',
                    'description' => 'Properties for the node. Must include "name".',
                ],
                'from_name' => [
                    'type' => 'string',
                    'description' => 'Name of the source node.',
                ],
                'from_label' => [
                    'type' => 'string',
                    'description' => 'Label of the source node.',
                ],
                'to_name' => [
                    'type' => 'string',
                    'description' => 'Name of the destination node.',
                ],
                'to_label' => [
                    'type' => 'string',
                    'description' => 'Label of the destination node.',
                ],
                'relationship' => [
                    'type' => 'string',
                    'description' => 'Relationship type (e.g. WORKS_ON, OWNS, RELATED_TO).',
                ],
                'cypher' => [
                    'type' => 'string',
                    'description' => 'Raw Cypher query (for "query" action).',
                ],
            ],
            'required' => ['action'],
        ];
    }

    public function execute(array $input): string
    {
        try {
            switch ($input['action']) {
                case 'upsert_node':
                    $this->knowledge->indexEntity($input['label'] ?? 'Entity', $input['properties'] ?? []);
                    return "Node [" . ($input['label'] ?? 'Entity') . "] (" . ($input['properties']['name'] ?? 'Unknown') . ") indexed.";

                case 'relate':
                    $this->knowledge->relate(
                        $input['from_label'] ?? 'Entity',
                        $input['from_name'] ?? 'Unknown',
                        $input['to_label'] ?? 'Entity',
                        $input['to_name'] ?? 'Unknown',
                        $input['relationship'] ?? 'RELATED_TO'
                    );
                    return "Relationship [{$input['from_name']}]-[:" . strtoupper($input['relationship'] ?? 'RELATED_TO') . "]->[{$input['to_name']}] created.";

                case 'query':
                    $result = $this->knowledge->query($input['cypher'] ?? 'MATCH (n) RETURN n LIMIT 5');
                    $output = [];
                    foreach ($result as $record) {
                        $output[] = $record->toArray();
                    }
                    return json_encode($output, JSON_PRETTY_PRINT);

                default:
                    return "Invalid action.";
            }
        } catch (\Exception $e) {
            return "Graph Error: " . $e->getMessage();
        }
    }
}
