# ANVIG_BOT User Guide

Welcome, Commander. This guide helps you navigate the ANVIG_BOT interface and utilize your autonomous workforce.

## 1. The Dashboard (Command Center)

The Dashboard is your primary landing page. It provides a high-level overview of the system status.

### **Key Areas:**
*   **System Status**: Shows if the Brain (Queue) and Nervous System (WebSockets) are online.
*   **Active Agents**: A list of currently awake agents.
*   **Recent Notifications**: Alerts from agents about completed tasks or research findings.

## 2. Agent Chat Interface

Communicate directly with your agents.

### **Features:**
*   **Direct Chat**: Type naturally. "Jerry, check the server status."
*   **File Upload**: Click the `+` icon to upload PDFs, Images, or Text files. Agents can read and analyze these.
    *   *Usage*: Upload a screenshot of a bug and ask "What's wrong here?".
*   **Voice Mode**: Click the Microphone icon to speak. The agent will reply with text and (optionally) synthetic voice.

### **Slash Commands:**
*   `/clear`: Clear the current conversation context.
*   `/export`: Download the chat history as a JSON file.

## 3. The Cortex (3D Visualization)

Accessed via the Sidebar -> **Cortex**.

This is a real-time 3D visualization of the agent's "mind".
*   **Nodes**: Spheres represent connected Agents.
*   **Lasers**: Beams of light indicate active data transfer between agents, or between an agent and a tool.
*   **Use Case**: Keep this open on a second monitor to visually verify that the system is "thinking" without reading logs.

## 4. Kanban Implementation (Task Management)

Accessed via Sidebar -> **Kanban**.

Assign autonomous work to agents.

### **Columns:**
*   **TODO**: Tasks waiting to be picked up.
*   **DOING**: Tasks currently being executed by an agent.
*   **DONE**: Completed tasks.

### **Creating a Task:**
1.  Click **"New Task"**.
2.  **Title**: "Research Quantum Computing".
3.  **Description**: "Find the top 5 papers from 2024."
4.  **Assignee**: Select "Jerry" (or another agent).

*Note: If "God Mode" is active, agents will automatically pick up tasks from TODO.*

## 5. Setting Up Background Missions

You can instruct an agent to create a recurring schedule.

**Example Command:**
> "Jerry, I want you to check the Hacker News front page every day at 9 AM and summarize the top AI story."

The agent will:
1.  Understand the intent.
2.  Use the `ScheduleTool` to register a Cron job.
3.  The system will autonomously wake up at 9 AM daily to execute this.

## 6. God Mode (Singularity)

Accessed via **Settings** (Gear Icon).

**⚠️ Warning**: Enabling God Mode grants agents full autonomy.
*   They can **edit their own code**.
*   They can **create new agents**.
*   They can **delete files** if they deem them unnecessary.

Use with caution and ensure you have a git backup.
