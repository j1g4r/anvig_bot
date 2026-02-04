# Core Concepts

This document explains the specific terminology and conceptual models used in ANVIG_BOT.

## 1. The Agent (Persona)
An **Agent** is not just an LLM prompt. It is a persistent entity with:
*   **Identity**: Name, Role, and personality quirks.
*   **Memory**: Access to its own slice of the Vector Database.
*   **Tools**: A specific set of allowed capabilities (e.g., a "Researcher" agent has web access, but a "Coder" agent might only have file access).

## 2. Cycles & Eras
The project Roadmap (`task.md`) is divided into **Eras** and **Cycles**.
*   **Era**: A major conceptual phase (e.g., "Era 1: Foundation", "Era 5: Autonomy").
*   **Cycle**: A specific sprint or feature, usually numbered (e.g., "Cycle 42: Self-Evolution").
*   *Why?* This helps the autonomous agents understand *where* they are in the grand plan when they read the roadmap.

## 3. The Cortex (Neural Void)
The **Cortex** is the visual representation of the system's state.
*   It is not just eye candy; it represents the **active graph** of agents.
*   When agents "federate" (share knowledge), you will see visual links form between nodes.

## 4. God Mode
**God Mode** is a permission level, not a user.
*   When enabled (Cycle 50), the `SystemPrompt` injected into every agent changes.
*   Safety rails are removed.
*   Agents are allowed to edit `app/` files, `routes/`, and `migrations`.
*   *Without God Mode*, agents are typically read-only or limited to specific sandboxes.

## 5. Federated Learning
This concept (Cycle 43) allows agents to "teach" each other.
1.  Agent A discovers a new pattern (e.g., "The user prefers Laravel 11 style controllers").
2.  Agent A saves this to `agent_adaptations`.
3.  The **Federation Service** runs at 3 AM.
4.  It promotes high-confidence adaptations to the **Global Knowledge Pool**.
5.  Agent B wakes up and downloads this new knowledge, effectively "learning" what Agent A found.

## 6. The Singularity (Goal)
The ultimate goal of this project is to reach a state where the user (you) can give a high-level directive:
> "Build a SaaS from scratch that sells pet food."
...and the system will autonomously:
1.  Research competitors.
2.  Design the DB schema.
3.  Write the Code.
4.  Test it.
5.  Deploy it.
Without human intervention. We are currently at **Era 5**.
