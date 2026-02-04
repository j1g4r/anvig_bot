# System Architecture

## Overview
ANVIG_BOT is a **Hybrid Agentic System** that combines traditional MVC (Model-View-Controller) web architecture with an autonomous AI loop. It leverages Laravel's robust backend capabilities to orchestrate LLM interactions, manage state, and execute tools.

## Core Components

### 1. The Agent Brain (`AgentService`)
The core logic resides in `App\Services\AgentService`. This service handles:
*   **Prompt Engineering**: Constructing the system prompt based on agent personality, current context, and memory.
*   **Context Management**: Compressing conversation history (Cycle 14) to fit within context windows.
*   **Inference**: Communicating with Ollama (or OpenAI) to generate responses.
*   **Tool Execution**: Parsing LLM outputs for tool calls (e.g., `use_tool: shell_exec`) and executing them safely.

### 2. Memory Systems
The system employs a multi-tiered memory architecture:

*   **Short-Term Memory (Context)**: Managed via the `messages` table. Recent interactions are fed directly into the LLM prompt.
*   **Long-Term Memory (Vector/RAG)**: Managed via `App\Services\MemoryService`.
    *   **Embeddings**: Text is converted to vector embeddings (using a local model).
    *   **Storage**: Stored in SQLite (with math extensions) or Neo4j for semantic search.
    *   **Retrieval**: Relevant memories are fetched based on cosine similarity to the current query and injected into the prompt.
*   **Graph Memory**: (Optional) Neo4j integration for tracking relationships between entities (User -> prefers -> Dark Mode).

### 3. Tooling System
Agents interact with the world through **Tools**.
*   **Base Class**: `App\Tools\BaseTool`.
*   **Registry**: Tools are registered in `AgentService`.
*   **Available Tools**:
    *   `ShellTool`: Execute terminal commands (sandboxed).
    *   `BrowserTool`: Puppeteer-based web browsing.
    *   `FileTool`: Read/Write access to the filesystem.
    *   `MemoryTool`: Explicitly save/retrieve specific memories.
    *   `ScheduleTool`: Create Cron jobs.

## Data Flow

1.  **User Input**: User sends a message via the Dashboard Chat UI.
2.  **Controller**: `AgentController@chat` receives the request.
3.  **Job Dispatch**: A `ProcessAgentThought` job is dispatched (asynchronous) to prevent timeout.
4.  **Agent Logic (Job)**:
    *   Retrieves recent conversation history.
    *   Searches Long-Term Memory for relevant context.
    *   Constructs the Prompt.
    *   Calls LLM API (Ollama).
5.  **Reasoning Loop**:
    *   **Thought**: Agent generates an internal thought (hidden from user).
    *   **Action**: Agent decides to call a tool (e.g., `read_file`).
    *   **Observation**: Tool executes and returns output.
    *   **Refinement**: Agent analyzes the observation.
6.  **Response**: Agent generates the final text response which is broadcasted via **Reverb** (WebSockets) back to the Frontend.

## Database Schema Highlights

*   **`agents`**: Stores agent profiles, system prompts, and configuration (model, temperature).
*   **`conversations`**: Groups messages into threads.
*   **`messages`**: Individual chat nodes (User vs Assistant).
*   **`memories`**: Vector index for RAG.
*   **`kanban_tasks`**: Tasks managed by the Kanban board.
*   **`scheduled_tasks`**: Missions created by the `ScheduleTool`.

## Frontend Architecture
*   **Inertia.js**: Acts as the glue, allowing us to build a Single Page App (SPA) using server-side routing.
*   **Vue 3 Composition API**: Used for all UI components.
*   **State Management**: Minimal client-side state; relies on server state via Inertia props.
*   **Real-Time**: Echo + Pusher (Reverb) listeners update the UI state instantly without page reloads.
