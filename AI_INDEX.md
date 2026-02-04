# AI Index

## System Metadata
- **Project:** ANVIG_BOT
- **Framework:** Laravel 12 / Inertia Vue 3
- **PHP Version:** 8.4
- **Type:** Autonomous AI Agent
- **Description:** A local-first autonomous agent clone of OpenClaw.

## Knowledge Graph
- **Roadmap:** 50-Cycle Master Evolution Plan (Era 1 & 2 Completed).
- **Collaboration:** Collaborative Canvas side-panel and Multi-Agent Delegation.
- **Proactivity:** Task Scheduling for autonomous background missions.
- **Vision:** Achieving "The God-Mode" - a fully autonomous, distributed, and self-improving AGI ecosystem.

## Request Log
- **[Init]:** Create clone of openclaw from scratch. (Status: Cycle 1 Foundation Complete)
- **Solution:** setup Laravel 12 + Inertia/Vue. Implemented AgentService, ShellTool, Database Schema, and Chat Interface. (Switched to Ollama/llama3.2 + Vision)
- **Configuration:** Updated `.env` to use `http://localhost:11434/v1` and set default agent model to `llama3.2`.
- **[Init]:** Create clone of openclaw from scratch. (Status: Cycle 1 Foundation Complete)
- **Solution:** setup Laravel 12 + Inertia/Vue. Implemented AgentService, ShellTool, Database Schema, and Chat Interface. (Switched to Ollama/llama3.2 + Vision + Telegram)
- **Configuration:** Updated `.env` to use `http://localhost:11434/v1` and set default agent model to `llama3.2`.
- **Vision:** Added `images` support to DB, Backend, and Frontend. Users can upload images to chat.
- **Telegram:** Added `telegram:run` command for real-time interaction via Telegram Bot.
- **Telegram:** Added `telegram:run` command for real-time interaction via Telegram Bot.
- **Async & Tools:** Implemented `ProcessAgentThought` Job for background processing. Added `FileTool` for file system access.
- **Browser:** Implemented `BrowserTool` and `browser-bridge.js` using Puppeteer.
- **Memory:** Implemented `MemoryTool` with Vector Embeddings (SQLite+PHP) for Long-Term Memory. Agent renamed to 'Jerry'.
- **RAG:** Implemented `ResearchTool` to automatically browse, read, and memorize web pages.
- **Local Vector DB:** Upgraded SQLite storage to use Binary Float32 (BLOB) for high-performance local vector similarity.
- **Broadcasting:** Implemented Laravel Reverb for real-time dashboard updates.
- **Multimodal:** Implemented Voice (TTS), Auditory Feedback, and Visual "Thinking" states in Cycle 9.
- **Data Intel:** Implemented `DatabaseTool`, `Memory Vault` (Visual UI), and Chat Export in Cycle 10.
- **Multimodal AI Team:** Implemented Cycle 11 Specialist Agents and Delegation logic.
- **Sentience:** Cycle 12 Internal Monologue (Thought/Plan/Critique) and Neural Flow UI toggle.
- **Compression:** Cycle 14 Context Compression for token-efficient memory.
- **API Hub:** Cycle 15 Webhook integration (Inbound/Outbound).
- **Voice STT:** Cycle 16 Web Speech API for local, privacy-first transcription.
- **Canvas:** Cycle 17 Collaborative side-panel for persistent artifacts and real-time sync.
- **Scheduling:** Cycle 18 Proactive Background Missions (`schedule_mission` tool).
- **UX:** Expanded application to 100% full-width.
- **Strategic Pivot:** Defined the 50-Cycle "Singularity" Roadmap (Task ID 1113).
- **C39 Continuous Learning:** Implemented learning pipeline with `learning_examples`, `learning_sessions`, `agent_adaptations` tables. Added `ContinuousLearningService` (pattern extraction via AI), `InteractionCollectorService` (data capture), `LearningTool` (self-reflection), and thumbs up/down feedback UI. Learning patterns are injected into agent prompts for behavior adaptation.
- **C40 Global Notification Hub:** Centralized notification system with `NotificationService`, `EmailDriver`, `LogDriver`, and `NotifyTool`. Agents can now send alerts to users via configured channels. Added "Notification Preferences" UI in user profile.
- **C41 VR/AR Interface (The Cortex):** Implemented 3D Command Center using Three.js and Vue 3. Visualizes agents as nodes in a 3D space ("Neural Void") with active conversation links. Accessible at `/cortex`.
- **C42 Autonomous Codebase Evolution:** Implemented `RoadmapTool` for reading/writing `task.md` and `EvolutionService` to analyze roadmap progress. Added `agent:evolve` command to identify and scaffold the next development cycle autonomously.
- **C43 Federated Learning:** Implemented `GlobalKnowledgePool` schema and model. Created `FederatedLearningService` to aggregate high-efficacy local agent adaptations into a shared pool and distribute them to other agents. Added `agent:federate` command.
- **C44 Predictive Intelligence:** Implemented `user_patterns` table for tracking behavioral context. Created `PredictionService` to train on user actions and predict future needs based on time/day context. Added `agent:predict` command.
- **C45 Emotional Intelligence:** Added `sentiment` and `sentiment_score` to `messages` table. Implemented `SentimentAnalysisService` and integrated it into `AgentController` (analysis) and `AgentService` (adaptive system prompt). Agents now detect Frustration/Joy and adapt tone.
- **C47 Zero-Latency Hybrid Inferencing:** Implemented `semantic_cache` table and `InferenceCacheService`. System now caches identical User Queries (normalized) and serves cached responses immediately, bypassing the LLM for ~10ms latency on recurring questions.
- **C48 Fully Autonomous R&D Agent:** Implemented `research_topics` table and `ResearchService`. Agents can now autonomously explore technical topics, assess relevance to the project, and store findings. Added `agent:research` command.
- **C49 Final Optimization:** Created `QuantumOptimizationService` and `agent:optimize` command. Implemented automated routines for database optimization (`VACUUM`/`OPTIMIZE`), system cache priming, and log pruning to ensure long-term stability and performance.
- **C50 God Mode (Singularity):** Implemented `GodModeService`, `system_settings` table, and `GodModeToggle` UI. This feature allows shifting the system into "Full Autonomy" (Level 5), updating global flags (`god_mode_enabled`) that permit unrestricted agent actions.
- **C51 Dynamic Cron System:** Implemented `agent_cron_jobs` table and improved `ScheduleTool` (`schedule_mission`). Agents can now programmatically create, list, and delete recurring Artisan commands (Cron jobs) that are injected into the Laravel scheduler at runtime.
- **C52 Open Access (No Auth):** Removed default Laravel authentication. Configured `HandleInertiaRequests` to inject a mock "Commander" user (ID 1) for all visitors, making the Dashboard the public landing page. Removed Login/Register routes.
