# Extending ANVIG_BOT

This guide explains how to extend the capabilities of the system by adding new Tools to the agents or creating new specialized Agents.

## 🛠️ Adding a New Tool

Tools allow agents to interact with the external world (APIs, Operating System, Database). To create a new tool:

### 1. Create the Tool Class
Create a new PHP class in `app/Tools/` extending `App\Tools\BaseTool` (or implementing the Tool interface).

```php
<?php

namespace App\Tools;

use Illuminate\Support\Facades\Log;

class MyCustomTool extends BaseTool
{
    /**
     * The unique name of the tool called by the LLM.
     */
    public function name(): string
    {
        return 'my_custom_tool';
    }

    /**
     * Description helping the LLM understand WHEN to use this tool.
     */
    public function description(): string
    {
        return 'Use this tool to perform X operation. Input should be Y.';
    }

    /**
     * The required parameters for the tool (JSON Schema format).
     */
    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'input_data' => [
                    'type' => 'string',
                    'description' => 'The data to process',
                ],
            ],
            'required' => ['input_data'],
        ];
    }

    /**
     * The execution logic.
     */
    public function run(array $args): string
    {
        $input = $args['input_data'] ?? '';
        
        // precise logic here
        Log::info("Running custom tool with: $input");

        return "Successfully processed: " . $input;
    }
}
```

### 2. Register the Tool
Open `app/Services/AgentService.php` (or the specific Agent configuration) and add your tool class to the registered tools array.

*(Note: In Cycle 53+, tools may be auto-discovered or registered via database configuration. Check `app/Providers/AppServiceProvider.php` if strict binding is needed.)*

---

## 🤖 Creating a New Agent

Agents can be created dynamically via the Dashboard or seeded via code.

### Option A: Via Dashboard (God Mode)
1.  Navigate to **Agents** > **Create New**.
2.  **Name**: Give a persona name (e.g., "Architect").
3.  **Role**: Define the job title.
4.  **Model**: Select `llama3.2` or other available local models.
5.  **System Prompt**: This is crucial. It defines the behavior.
    > *Example:* "You are a Senior Software Architect. You focus on high-level design patterns, code modularity, and scalability. You do not write implementation details unless asked."

### Option B: Via Seeder
Create a seeder to ship the agent with the project.

```php
// database/seeders/AgentSeeder.php

Agent::create([
    'name' => 'Sherlock',
    'slug' => 'sherlock',
    'role' => 'Detective',
    'model' => 'llama3.2',
    'temperature' => 0.7,
    'system_prompt' => "You are a deductive reasoning engine. Analyze facts, find contradictions, and solve complex problems.",
    'is_active' => true,
]);
```

## 🧠 Modifying Agent Behavior

To change how all agents "think", modify the core logic in `App\Jobs\ProcessAgentThought`. Any changes here affect the global thinking loop (Thought -> Action -> Observation).

### Adding New "Eras" or Cycles
If you are developing a new major feature (e.g., "Era 6: Physical Robotics"):
1.  Define the goal in `task.md` or the Roadmap.
2.  Implement the specialized Controllers/Services.
3.  Register any background jobs in `routes/console.php` or the `ScheduleTool`.
