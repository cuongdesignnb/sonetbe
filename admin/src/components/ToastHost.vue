<template>
  <div class="toast-host" aria-live="polite" aria-atomic="true">
    <div
      v-for="t in toasts"
      :key="t.id"
      class="toast"
      :class="`toast-${t.variant}`"
    >
      <div class="toast-message">{{ t.message }}</div>
      <button class="toast-close" @click="remove(t.id)" aria-label="Đóng">
        ✕
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useToast } from "../lib/toast";

const { toasts, remove } = useToast();
</script>
