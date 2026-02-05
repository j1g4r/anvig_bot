# AI Knowledge Index

## Indexing Rules
- Record key architectural decisions.
- Record reusable components or patterns.
- Record complex logic explanations.

## Entries

### 1. Hybrid 3D/DOM Interface Pattern
**Date:** 2026-02-05
**Context:** CORTEX Interface
**Solution:** Used Three.js for the spatial graph (Agents/Connections) but overlaid HTML/Vue elements for labels and metrics.
**Logic:**
- `Scene.vue` calculates 3D positions and projects them to 2D screen coordinates using `vector.project(camera)`.
- Returns an array of `style` objects { top, left, transform } to Vue template.
- Vue renders `div` elements at those coordinates.
**Benefit:** Allows easy styling (Tailwind) and interaction (Selectable text) found in DOM, while keeping the high-performance 3D visualization.

### 2. Reactive Three.js Scene System
**Date:** 2026-02-05
**Pattern:**
- `watch(() => props.data, ...)` inside `Scene.vue` triggers `updateAgents()`.
- `updateAgents` rebuilds the Three.js Group for each agent but preserves the Scene.
- `animate()` loop handles rotation independent of data updates.
**Benefit:** Decouples the simulation loop (60fps) from the data update loop (reactive props).

### 3. System Dashboard Widget
**Date:** 2026-02-05
**Component:** `SystemDashboard.vue`
**Style:** Glassmorphism with Tailwind (`backdrop-blur-xl`, `bg-black/80`).
**Features:** SVG Circle Gauge for health, Sparkline-like bars for tasks.

### 4. Selection & Event Bus Pattern (Phase 2)
**Date:** 2026-02-05
**Context:** Agent Selection
**Logic:**
- `Scene.vue` captures DOM clicks on projected labels.
- Emits `agent-select(id)` to parent.
- Parent (`Index.vue`) controls the `AgentDetailPanel` visibility and data.
**Benefit:** Keeps 3D component focused on visualization, delegating UI state management to the page controller.

### 5. Data Packet Simulation
**Date:** 2026-02-05
**Context:** Visualizing Task Flow
**Solution:** Single Particle System (`THREE.Points`) with custom update loop.
**Details:**
- Instead of creating Mesh for every packet, we manage an array of particle state objects `{ pos, target, progress }`.
- On every frame, update positions in the `Float32Array` attribute of the single Geometry.
- Spawns "bursts" of packets based on connection creation.
