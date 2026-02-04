# API Reference

While ANVIG_BOT is primarily an interface-driven application, it exposes several internal APIs for the frontend (Inertia) and external tools (Telegram/Webhooks).

## Base URL
Default: `http://localhost:8000`

## 1. Agent Interaction

### POST `/agents/{agent}/chat/{conversation}`
Send a message to an agent.

**Parameters:**
*   `message` (string, required): The user's input.
*   `files` (array, optional): Uploaded file objects.
*   `voice_mode` (boolean): If true, response favors speech patterns.

**Response:**
```json
{
  "status": "success"
}
```
*Note: The actual response text is delivered via WebSocket (channel: `conversation.{id}`).*

### GET `/agents/vault`
Retrieve the Memory Galaxy state.

**Response:**
```json
{
  "memories": [
    {
      "id": 1,
      "content": "User prefers dark mode.",
      "embedding": "..."
    }
  ]
}
```

## 2. Cortex (Visualization)

### GET `/cortex/data` (Internal)
Returns the graph data for 3D visualization.
*   **Nodes**: Agents, Users, Tools.
*   **Links**: Active relationships.

## 3. Kanban

### GET `/kanban/board`
Get the full board state.

### POST `/kanban/tasks`
Create a new task.

**Payload:**
```json
{
  "title": "Research AI",
  "description": "...",
  "status": "todo", // todo, doing, done
  "assigned_to": 1 // Agent ID
}
```

## 4. Webhooks (Inbound)

### POST `/api/webhook/telegram`
Endpoint for Telegram Bot interaction.

**Headers:**
*   `X-Telegram-Bot-Api-Secret-Token`: (Your Secret)

**Payload:**
Standard Telegram Update Object.

## 5. Artisan Commands (CLI API)

*   `agent:poke`: Triggers the `AgentService` to check for pending tasks.
*   `agent:evolve`: Runs the self-evolution pipeline.
*   `agent:research {topic}`: Manually triggers a research mission.

---

## Authentication
By default (Cycle 52+), the system runs in **No-Auth Mode**.
Requests are automatically authenticated as User ID 1 via middleware.
*   **Production Warning**: If deploying publicly, re-enable `auth:sanctum` middleware in `routes/web.php`.
