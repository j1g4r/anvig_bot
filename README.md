# ANVIG_BOT (The Autonomous Singularity Engine)

```
    _    _   ___     _______ ____    ____  ____ _____ 
   / \  | \ | \ \   / /_   _/ ___|  | __ )|  _ \_   _|
  / _ \ |  \| |\ \ / /  | || |  _   |  _ \| | | || |  
 / ___ \| |\  | \ V /   | || |_| |  | |_) | |_| || |  
/_/   \_\_| \_|  \_/    |_| \____|  |____/|____/ |_|  
```

**ANVIG_BOT** ("Jerry") is a state-of-the-art **Autonomous Artificial General Intelligence (AAGI) Prototype** designed to operate entirely on local hardware. It represents a paradigm shift from "Chatbot" to "Digital Lifeform"—an entity capable of self-scheduling, self-coding, and maintaining a persistent existence independent of user interaction.

> **Core Philosophy:** "The Zero-Stop Loop." The agent thinks, observes, and acts in a continuous background loop, simulating consciousness.

---

## 📜 Table of Contents
1.  [System Architecture](#-system-architecture)
2.  [The Visual Cortex & Cortex](#-the-visual-cortex--cortex)
3.  [The Kanban Orchestrator](#-the-kanban-orchestrator)
4.  [Core Capabilities](#-core-capabilities)
5.  [Tool Ecosystem](#-tool-ecosystem)
6.  [Scripts & Automation](#-scripts--automation)
7.  [Installation & Deployment](#-installation--deployment)
8.  [Operational Commands](#-operational-commands)

---

## 🏗️ System Architecture

The architecture mimics a biological nervous system, built on top of **Laravel 12** (The Central Nervous System) and **Vue 3** (The Visual Interface).

### 1. The Brain (`AgentService.php`)
The core logic engine implementing a **Recursive Reasoning Loop**:
*   **Thought**: Analyzes context + memory.
*   **Plan**: Formulates a multi-step plan (CoT).
*   **Action**: Executes tools (Shell, Browser, Code).
*   **Observation**: Reads output and repeats.

### 2. The Pulse (`Queue` & `Schedule`)
The agent is "always on."
*   **Heartbeat**: A continuous `ProcessAgentThought` job runs in the background.
*   **Circadian Rhythm**: The `ScheduleTool` writes Cron jobs, effectively deciding *when* to wake up.

### 3. The Nervous System (`Reverb`)
Real-time WebSockets connect the backend Brain to the frontend Dashboard, pushing thoughts, logs, and visual updates in milliseconds.

---

## 👁️ The Visual Cortex & Cortex

### The Cortex (`/cortex`)
A 3D "Subconscious" Visualizer primarily built with **Three.js**.
*   **Visualizes Thought**: Renders the agent's internal state as a constellation of glowing nodes.
*   **Agent Collaboration**: Shows real-time "beams" and data particles flowing between agents (e.g., Jerry delegating to Researcher) to visualize multi-agent collaboration.
*   **Active Links**: Displays active task connections and data transfer.

---

## 📋 The Kanban Orchestrator

The **Kanban Board** (`/kanban`) is the project management center for the agents.
*   **Autonomous Management**: Agents can create, move, and complete their own tasks using the `KanbanTool`.
*   **Task Visualization**: view the status of all missions (Ready, Active, Hold, Done).
*   **Agent Assignment**: Tasks are assigned to specific agents (Jerry, Researcher, Developer, etc.).

---

## 🔥 Core Capabilities

### 1. God Mode & Self-Evolution
*   **Permissions**: When enabled, the agent gains root-level access to its own codebase.
*   **Self-Refactoring**: The agent can read and rewrite its own logic (`AgentService.php`) to optimize performance.

### 2. Multi-Agent Collaboration
*   **Specialization**: The system supports multiple specialized agents (e.g., **Researcher**, **Developer**, **Compliance**, **Analyst**).
*   **Delegation**: The primary agent ("Jerry") can delegate sub-tasks to specialists using the `DelegateTool`.

### 3. Desktop Sentience
*   **Vision**: Can capture and analyze the host screen.
*   **Control**: Can control the mouse and keyboard via Python scripts (`desktop_sentience.py`) to interact with non-API desktop applications.

---

## 🛠️ Tool Ecosystem

The agent interacts with the world via a modular Tool Interface.

| Tool | Description |
| :--- | :--- |
| **ShellTool** | Executes CLI commands (Secured by blacklist). |
| **BrowserTool** | Controls headless Chrome to browse the web. |
| **FileTool** | Reads, Writes, and Patches files. |
| **KanbanTool** | Manages the Kanban board (Create/Update tasks). |
| **DelegateTool** | Delegates tasks to other specialized agents. |
| **DesktopTool** | Controls host Mouse/Keyboard and captures Screen. |
| **MemoryTool** | Stores/Retrieves vector embeddings (Long-term memory). |
| **DatabaseTool** | Executes SQL queries to analyze data. |
| **ResearchTool** | Spawns threads to deep-research topics. |
| **ScheduleTool** | Manages OS Cron jobs. |
| **SmartHomeTool** | Controls IoT devices via MQTT. |
| **MfaTool** | Handles TOTP 2FA tokens. |
| **ComplianceTool** | Checks code/actions against safety rules. |
| **GraphTool** | Manages Knowledge Graph relationships. |
| **CanvasTool** | Updates the shared "Canvas" workspace. |
| **WebhookTool** | Receives external webhooks. |
| **NotifyTool** | Sends user notifications. |

---

## 🤖 Scripts & Automation

The project includes several automation scripts in the `scripts/` directory:

| Script | Purpose |
| :--- | :--- |
| `start.sh` | **Unified Startup**. Launches Web Server, Queue, Reverb, and Vite in parallel. |
| `keep_alive_worker.sh` | watchdog script to ensure the queue worker stays running. |
| `desktop_sentience.py` | Python bridge for PyAutoGUI (Screen/Mouse/Keyboard control). |
| `memory_cluster.py` | Python script for clustering vector memories. |
| `neo4j_bridge.py` | Python bridge for advanced graph database interactions. |

### NPM Scripts
*   `npm run dev`: Starts the Vite development server (hot-reload).
*   `npm run build`: Compiles assets for production.
*   `npm run start`: Executes `./scripts/start.sh` (Recommended for dev).

---

## 🚀 Installation & Deployment

### 1. Requirements
*   **Hardware**: Apple Silicon (M1/M2/M3) recommended.
*   **Software**: Docker (optional), PHP 8.2+, Node 20+, Redis, Python 3.9+.

### 2. Setup
```bash
git clone https://github.com/your-org/ANVIG_BOT.git
cd ANVIG_BOT
composer install && npm install
cp .env.example .env && php artisan key:generate
# Configure .env with API keys (OpenAI, Ollama, etc.)
php artisan migrate
```

### 3. Launch
The easiest way to start the full system:
```bash
npm run start
```
*This command runs the `scripts/start.sh` script.*

### 4. Application Access
*   **Dashboard**: `http://localhost:8000`
*   **Cortex**: `http://localhost:8000/cortex`
*   **Kanban**: `http://localhost:8000/kanban`

---

## 💻 Operational Commands

For manual control or debugging:

### System Processes
1.  **Web Server**: `php artisan serve`
2.  **The Brain (Queue)**: `php artisan queue:listen --tries=1 --timeout=0`
3.  **Nervous System**: `php artisan reverb:start`
4.  **Frontend**: `npm run dev`

### Agent CLI Tools
*   `php artisan agent:evolve` - Trigger self-improvement cycle.
*   `php artisan agent:research {topic}` - Spawn a research agent.
*   `php artisan telegram:run` - Start Telegram bot polling.
*   `php artisan agent:federate` - Sync knowledge to the pool.

---
**License**: MIT.
**Architect**: Antigravity.
