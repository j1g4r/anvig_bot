<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    /**
     * Execute a workflow.
     */
    public function execute(Workflow $workflow): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'logs' => [],
        ]);

        try {
            $trigger = $workflow->getTriggerNode();
            if (!$trigger) {
                throw new \Exception('No trigger node found');
            }

            $this->log($run, "Starting workflow: {$workflow->name}");

            // Get next nodes from trigger
            $nextNodes = $this->getNextNodes($workflow, $trigger->node_id);
            $context = [];

            foreach ($nextNodes as $nodeData) {
                $this->executeNode($workflow, $nodeData['node'], $run, $context, $nodeData['handle']);
            }

            $run->update(['status' => 'completed']);
            $workflow->update(['last_run_at' => now()]);
            $this->log($run, "Workflow completed successfully");

        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            $this->log($run, "Error: {$e->getMessage()}");
        }

        return $run;
    }

    /**
     * Execute a single node.
     */
    protected function executeNode(Workflow $workflow, WorkflowNode $node, WorkflowRun $run, array &$context, ?string $handle = null): void
    {
        $this->log($run, "Executing node: {$node->action_type}");

        $result = match ($node->action_type) {
            'shell' => $this->executeShell($node, $context),
            'jerry' => $this->executeJerry($node, $context),
            'http' => $this->executeHttp($node, $context),
            'email' => $this->executeEmail($node, $context),
            'if_else' => $this->executeCondition($node, $context),
            'set_variable' => $this->executeSetVariable($node, $context),
            default => null,
        };

        $context[$node->node_id] = $result;
        $this->log($run, "Node result: " . substr(json_encode($result), 0, 200));

        // Handle condition branching
        if ($node->action_type === 'if_else') {
            $branchHandle = $result ? 'true' : 'false';
            $nextNodes = $this->getNextNodes($workflow, $node->node_id, $branchHandle);
        } else {
            $nextNodes = $this->getNextNodes($workflow, $node->node_id);
        }

        foreach ($nextNodes as $nodeData) {
            $this->executeNode($workflow, $nodeData['node'], $run, $context, $nodeData['handle']);
        }
    }

    protected function executeShell(WorkflowNode $node, array $context): ?string
    {
        $command = $node->config['command'] ?? '';
        $command = $this->interpolate($command, $context);
        return shell_exec($command);
    }

    protected function executeJerry(WorkflowNode $node, array $context): ?string
    {
        $prompt = $node->config['prompt'] ?? '';
        $prompt = $this->interpolate($prompt, $context);

        // Use a simplified agent call
        try {
            $response = \OpenAI\Laravel\Facades\OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are Jerry, a helpful AI assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
            return $response->choices[0]->message->content ?? '';
        } catch (\Exception $e) {
            return "Error: {$e->getMessage()}";
        }
    }

    protected function executeHttp(WorkflowNode $node, array $context): ?array
    {
        $url = $this->interpolate($node->config['url'] ?? '', $context);
        $method = $node->config['method'] ?? 'GET';
        $body = $node->config['body'] ?? null;

        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url, [
            'json' => $body,
            'http_errors' => false,
        ]);

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->getBody()->getContents(), true),
        ];
    }

    protected function executeEmail(WorkflowNode $node, array $context): bool
    {
        // Placeholder - integrate with mail service
        $to = $node->config['to'] ?? '';
        $subject = $this->interpolate($node->config['subject'] ?? '', $context);
        $body = $this->interpolate($node->config['body'] ?? '', $context);

        Log::info("Workflow Email: To: {$to}, Subject: {$subject}");
        return true;
    }

    protected function executeCondition(WorkflowNode $node, array $context): bool
    {
        $field = $node->config['field'] ?? '';
        $operator = $node->config['operator'] ?? 'equals';
        $value = $node->config['value'] ?? '';

        $fieldValue = $this->interpolate($field, $context);

        return match ($operator) {
            'equals' => $fieldValue == $value,
            'not_equals' => $fieldValue != $value,
            'contains' => str_contains($fieldValue, $value),
            'greater_than' => $fieldValue > $value,
            'less_than' => $fieldValue < $value,
            default => false,
        };
    }

    protected function executeSetVariable(WorkflowNode $node, array $context): mixed
    {
        return $this->interpolate($node->config['value'] ?? '', $context);
    }

    protected function getNextNodes(Workflow $workflow, string $nodeId, ?string $handle = null): array
    {
        $query = $workflow->edges()->where('source_node_id', $nodeId);

        if ($handle) {
            $query->where('source_handle', $handle);
        }

        $edges = $query->get();
        $nodes = [];

        foreach ($edges as $edge) {
            $node = $workflow->nodes()->where('node_id', $edge->target_node_id)->first();
            if ($node) {
                $nodes[] = ['node' => $node, 'handle' => $edge->source_handle];
            }
        }

        return $nodes;
    }

    protected function interpolate(string $text, array $context): string
    {
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $text = str_replace("{{" . $key . "}}", $value, $text);
            }
        }
        return $text;
    }

    protected function log(WorkflowRun $run, string $message): void
    {
        $logs = $run->logs ?? [];
        $logs[] = [
            'time' => now()->toISOString(),
            'message' => $message,
        ];
        $run->update(['logs' => $logs]);
    }
}
