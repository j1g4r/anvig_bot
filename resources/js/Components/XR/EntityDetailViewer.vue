<template>
  <SpatialPanel :title="entity.title || 'Entity'" :size="currentSize" :position="position">
    <div class="entity-viewer">
      <div v-if="level === 1" class="level-one" @click="expand">
        <div class="icon">{{ entity.icon || "🔹" }}</div>
        <p class="summary">{{ entity.summary || entity.description || "No summary" }}</p>
        <span class="hint">Click to expand</span>
      </div>
      <div v-else-if="level === 2" class="level-two">
        <button class="back-btn" @click="collapse">← Back</button>
        <div class="metadata">
          <div v-for="(val, key) in entity.meta" :key="key" class="meta-item">
            <strong>{{ key }}:</strong> <span>{{ val }}</span>
          </div>
        </div>
        <div class="tags">
          <span v-for="tag in entity.tags" :key="tag" class="tag">{{ tag }}</span>
        </div>
        <button class="full-btn" @click="fullView">View Full Details →</button>
      </div>
      <div v-else class="level-three">
        <button class="back-btn" @click="expand">← Back to Summary</button>
        <pre class="json-view">{{ jsonDisplay }}</pre>
      </div>
    </div>
  </SpatialPanel>
</template>

<script setup>
import { ref, computed } from 'vue';
import SpatialPanel from './SpatialPanel.vue';

const props = defineProps({
  entity: { type: Object, required: true },
  position: { type: Object, default: () => ({ angle: 0, distance: 1.5 }) }
});

const level = ref(1);
const currentSize = ref('medium');

const jsonDisplay = computed(() => JSON.stringify(props.entity, null, 2));

const expand = () => { level.value = 2; currentSize.value = 'wide'; };
const fullView = () => { level.value = 3; currentSize.value = 'wide'; };
const collapse = () => { level.value = 1; currentSize.value = 'medium'; };
</script>

<style scoped>
.entity-viewer { cursor: pointer; }
.level-one { text-align: center; padding: 20px; }
.icon { font-size: 48px; margin-bottom: 16px; }
.summary { font-size: 1.1rem; opacity: 0.9; }
.hint { font-size: 0.8rem; opacity: 0.6; margin-top: 12px; display: block; }
.back-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 8px 16px; border-radius: 8px; cursor: pointer; margin-bottom: 16px; }
.metadata { margin-bottom: 16px; }
.meta-item { padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
.tags { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 0; }
.tag { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; }
.full-btn { width: 100%; padding: 12px; background: rgba(59, 130, 246, 0.6); border: none; color: white; border-radius: 8px; cursor: pointer; margin-top: 16px; }
.json-view { background: rgba(0,0,0,0.3); padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; max-height: 50vh; }
</style>
