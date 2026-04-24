<template>
  <div class="editor-wrapper" :style="wrapperStyle">
    <div ref="editorRoot" />
    <textarea
      v-if="ready && !quillInstance"
      class="textarea"
      :placeholder="placeholder"
      :value="modelValue"
      @input="(e) => emit('update:modelValue', (e.target as HTMLTextAreaElement).value)"
      :style="{ minHeight: `${minHeight}px` }"
    />
    <div v-if="!ready" class="editor-loading">Đang tải editor…</div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import Quill from "quill";

const props = withDefaults(
  defineProps<{
    modelValue: string;
    placeholder?: string;
    minHeight?: number;
    enableImage?: boolean;
  }>(),
  {
    placeholder: "Nhập nội dung...",
    minHeight: 180,
    enableImage: false,
  }
);

const emit = defineEmits<{
  (e: "update:modelValue", value: string): void;
  (e: "image-request"): void;
}>();

const editorRoot = ref<HTMLDivElement | null>(null);
const quillInstance = ref<any>(null);
const ready = ref(false);
const isSyncing = ref(false);

const wrapperStyle = computed(() => ({
  "--editor-min-height": `${props.minHeight}px`,
}));

const toolbar = computed(() => {
  const base: any[] = [
    [{ header: [1, 2, 3, false] }],
    ["bold", "italic", "underline", "strike"],
    [{ list: "ordered" }, { list: "bullet" }],
    [{ align: [] }],
    ["blockquote", "code-block"],
    ["link"],
  ];
  if (props.enableImage) base.push(["image"]);
  base.push(["clean"]);
  return base;
});

const modules = computed(() => ({
  toolbar: {
    container: toolbar.value,
    handlers: props.enableImage
      ? {
          image: () => emit("image-request"),
        }
      : {},
  },
}));

function insertHtml(html: string) {
  const quill = quillInstance.value as any;
  if (quill) {
    const range = quill.getSelection(true);
    const index = range ? range.index : quill.getLength();
    quill.clipboard.dangerouslyPasteHTML(index, html);
    quill.setSelection(index + html.length, 0);
    return;
  }
  emit("update:modelValue", `${props.modelValue || ""}${html}`.trim());
}

defineExpose({ insertHtml });

onMounted(async () => {
  await nextTick();
  try {
    if (!editorRoot.value) return;

    const quill = new (Quill as any)(editorRoot.value, {
      theme: "snow",
      placeholder: props.placeholder,
      modules: modules.value,
    });

    quill.root.innerHTML = props.modelValue || "";

    quill.on("text-change", () => {
      if (isSyncing.value) return;
      emit("update:modelValue", quill.root.innerHTML || "");
    });

    quillInstance.value = quill;
  } catch (error) {
    console.error("Quill init failed", error);
    quillInstance.value = null;
  } finally {
    ready.value = true;
  }
});

watch(
  () => props.modelValue,
  (next) => {
    const quill = quillInstance.value as any;
    if (!quill) return;
    const html = next || "";
    if (quill.root.innerHTML === html) return;
    isSyncing.value = true;
    quill.root.innerHTML = html;
    isSyncing.value = false;
  }
);
</script>
