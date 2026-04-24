<template>
  <div>
    <button
      class="folder-item"
      :class="{ active: currentFolder === folder.id }"
      :style="{ paddingLeft: `${16 + depth * 16}px` }"
      @click="$emit('select', folder.id)"
    >
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" />
      </svg>
      <span class="folder-name">{{ folder.name }}</span>
      <span class="folder-count">{{ counts[folder.id] || 0 }}</span>
      <div class="folder-actions" @click.stop>
        <button class="folder-action-btn" title="Edit" @click="$emit('edit', folder)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
        </button>
        <button class="folder-action-btn" title="Delete" @click="$emit('delete', folder)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="3 6 5 6 21 6" />
            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
          </svg>
        </button>
      </div>
    </button>

    <!-- Render children recursively -->
    <template v-for="child in children" :key="child.id">
      <FolderTreeItem
        :folder="child"
        :folders="folders"
        :counts="counts"
        :current-folder="currentFolder"
        :depth="depth + 1"
        @select="(id) => $emit('select', id)"
        @edit="(f) => $emit('edit', f)"
        @delete="(f) => $emit('delete', f)"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { MediaFolder } from "../lib/types";

const props = defineProps<{
  folder: MediaFolder;
  folders: MediaFolder[];
  counts: Record<number, number>;
  currentFolder: number | "all" | "uncategorized";
  depth: number;
}>();

defineEmits<{
  (e: "select", id: number): void;
  (e: "edit", folder: MediaFolder): void;
  (e: "delete", folder: MediaFolder): void;
}>();

const children = computed(() =>
  props.folders.filter((f) => f.parent_id === props.folder.id)
);
</script>
