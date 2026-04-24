<template>
  <div v-if="open" class="modal-backdrop" @click.self="close">
    <div class="media-picker-modal">
      <!-- Sidebar -->
      <aside class="picker-sidebar">
        <div class="picker-sidebar-title">Thư mục</div>
        <nav class="picker-folder-list">
          <button
            class="picker-folder-item"
            :class="{ active: currentFolder === 'all' }"
            @click="selectFolder('all')"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="3" y="3" width="7" height="7" rx="1" />
              <rect x="14" y="3" width="7" height="7" rx="1" />
              <rect x="3" y="14" width="7" height="7" rx="1" />
              <rect x="14" y="14" width="7" height="7" rx="1" />
            </svg>
            Tất cả
          </button>
          <button
            class="picker-folder-item"
            :class="{ active: currentFolder === 'uncategorized' }"
            @click="selectFolder('uncategorized')"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"
              />
            </svg>
            Chưa phân loại
          </button>
          <div class="picker-folder-divider"></div>
          <button
            v-for="folder in folders"
            :key="folder.id"
            class="picker-folder-item"
            :class="{ active: currentFolder === folder.id }"
            :style="{ paddingLeft: `${12 + getDepth(folder) * 12}px` }"
            @click="selectFolder(folder.id)"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"
              />
            </svg>
            {{ folder.name }}
          </button>
        </nav>
      </aside>

      <!-- Main -->
      <main class="picker-main">
        <div class="picker-header">
          <div>
            <h2 class="picker-title">{{ pickerTitle }}</h2>
            <p class="muted" style="margin: 0">
              Chọn từ thư viện hoặc tải lên mới
            </p>
          </div>
          <button class="btn-icon" @click="close">
            <svg
              width="20"
              height="20"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <div class="picker-toolbar">
          <div class="search-box" style="max-width: 280px">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input
              v-model="search"
              class="search-input"
              placeholder="Tìm kiếm..."
              @input="debouncedSearch"
            />
          </div>
          <label class="btn upload-btn">
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
              <polyline points="17 8 12 3 7 8" />
              <line x1="12" y1="3" x2="12" y2="15" />
            </svg>
            Tải lên
            <input
              type="file"
              :accept="uploadAccept"
              hidden
              multiple
              @change="upload"
            />
          </label>
          <button
            v-if="!multiple && selected"
            class="btn btn-danger btn-sm"
            style="margin-left: 6px"
            :disabled="deleting"
            @click="deleteSelected"
          >
            {{ deleting ? "Đang xóa..." : "Xóa ảnh" }}
          </button>
        </div>

        <div class="picker-grid-container">
          <div v-if="loading" class="media-loading">
            <div class="spinner"></div>
          </div>

          <div v-else class="picker-grid">
            <button
              v-for="m in media"
              :key="m.id"
              class="picker-item"
              :class="{ selected: isSelected(m) }"
              type="button"
              @click="onClickItem(m)"
            >
              <div class="picker-item-thumb">
                <img
                  v-if="m.url && m.type !== 'video'"
                  :src="m.url"
                  :alt="m.original_name || ''"
                />
                <div
                  v-else-if="m.type === 'video'"
                  class="video-thumb-placeholder"
                >
                  <svg
                    width="32"
                    height="32"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                  >
                    <polygon
                      points="5 3 19 12 5 21 5 3"
                      fill="currentColor"
                      opacity="0.7"
                    />
                  </svg>
                  <span class="video-name">{{
                    m.original_name || "Video"
                  }}</span>
                </div>
              </div>
              <div class="picker-item-check" v-if="isSelected(m)">
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                >
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                </svg>
              </div>
            </button>

            <div v-if="media.length === 0" class="picker-empty">
              {{
                acceptMode === "video"
                  ? "Không tìm thấy video"
                  : "Không tìm thấy hình ảnh"
              }}
            </div>
          </div>

          <div class="picker-pagination" v-if="lastPage > 1">
            <button
              class="btn btn-secondary btn-sm"
              :disabled="page <= 1"
              @click="prevPage"
            >
              ←
            </button>
            <span class="muted">{{ page }} / {{ lastPage }}</span>
            <button
              class="btn btn-secondary btn-sm"
              :disabled="page >= lastPage"
              @click="nextPage"
            >
              →
            </button>
          </div>
        </div>

        <div class="picker-footer">
          <div class="picker-selection">
            <template v-if="multiple">
              <template v-if="selectedCount > 0">
                <img
                  :src="selectedAssets[0]?.url || ''"
                  class="picker-preview"
                />
                <span>Đã chọn {{ selectedCount }} ảnh</span>
              </template>
              <span v-else class="muted">Chưa chọn hình ảnh</span>
            </template>
            <template v-else>
              <template v-if="selected">
                <img :src="selected.url || ''" class="picker-preview" />
                <span>{{ selected.original_name || "Đã chọn" }}</span>
              </template>
              <span v-else class="muted">Chưa chọn hình ảnh</span>
            </template>
          </div>
          <div class="row" style="gap: 8px">
            <button class="btn btn-secondary" @click="close">Hủy</button>
            <button
              v-if="multiple"
              class="btn"
              :disabled="selectedCount === 0"
              @click="choose"
            >
              Chọn ({{ selectedCount }})
            </button>
            <button v-else class="btn" :disabled="!selected" @click="choose">
              Chọn hình
            </button>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { apiFetch, apiForm, extractMessage } from "../lib/api";
import type { MediaAsset, MediaFolder } from "../lib/types";

type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
};

type FoldersResponse = {
  folders: MediaFolder[];
  counts: Record<number, number>;
  uncategorized: number;
};

const props = defineProps<{
  open: boolean;
  multiple?: boolean;
  accept?: "image" | "video" | "all";
}>();
const emit = defineEmits<{
  (e: "close"): void;
  (e: "select", asset: MediaAsset | MediaAsset[]): void;
}>();

const folders = ref<MediaFolder[]>([]);
const media = ref<MediaAsset[]>([]);
const loading = ref(false);
const search = ref("");
const page = ref(1);
const lastPage = ref(1);
const currentFolder = ref<number | "all" | "uncategorized">("all");
const selected = ref<MediaAsset | null>(null);
const selectedIds = ref<number[]>([]);
const selectedById = ref<Record<number, MediaAsset>>({});

const multiple = ref(!!props.multiple);
const acceptMode = ref(props.accept || "image");

const pickerTitle = computed(() => {
  if (acceptMode.value === "video") return "Chọn video";
  if (acceptMode.value === "all") return "Chọn file";
  return "Chọn hình ảnh";
});

const uploadAccept = computed(() => {
  if (acceptMode.value === "video")
    return "video/mp4,video/webm,video/quicktime,video/x-msvideo,.mp4,.webm,.mov,.avi,.mkv";
  if (acceptMode.value === "all") return "image/*,video/*";
  return "image/*";
});

watch(
  () => props.multiple,
  (v) => {
    multiple.value = !!v;
  },
);

watch(
  () => props.accept,
  (v) => {
    acceptMode.value = v || "image";
  },
);

function isSelected(asset: MediaAsset): boolean {
  if (multiple.value) return !!selectedById.value[asset.id];
  return selected.value?.id === asset.id;
}

function clearSelection() {
  selected.value = null;
  selectedIds.value = [];
  selectedById.value = {};
}

function onClickItem(asset: MediaAsset) {
  if (multiple.value) {
    if (!asset.url) return;
    if (selectedById.value[asset.id]) {
      delete selectedById.value[asset.id];
      selectedIds.value = selectedIds.value.filter((id) => id !== asset.id);
    } else {
      selectedById.value[asset.id] = asset;
      selectedIds.value = [...selectedIds.value, asset.id];
    }
    return;
  }

  selected.value = asset;
}

const selectedAssets = ref<MediaAsset[]>([]);
const selectedCount = ref(0);

watch(
  [selectedIds, selectedById],
  () => {
    const list = selectedIds.value
      .map((id) => selectedById.value[id])
      .filter(Boolean);
    selectedAssets.value = list;
    selectedCount.value = list.length;
  },
  { deep: true },
);

function close() {
  emit("close");
}

function choose() {
  if (multiple.value) {
    const assets = selectedIds.value
      .map((id) => selectedById.value[id])
      .filter(Boolean);
    if (assets.length === 0) return;
    emit("select", assets);
    emit("close");
    return;
  }

  if (!selected.value) return;
  emit("select", selected.value);
  emit("close");
}

function getDepth(folder: MediaFolder): number {
  let depth = 0;
  let current = folder;
  while (current.parent_id) {
    const parent = folders.value.find((f) => f.id === current.parent_id);
    if (!parent) break;
    depth++;
    current = parent;
  }
  return depth;
}

async function loadFolders() {
  try {
    const res = await apiFetch<FoldersResponse>("/api/admin/media/folders");
    folders.value = res.folders;
  } catch {
    // ignore
  }
}

async function load() {
  loading.value = true;
  try {
    const qs = new URLSearchParams();
    if (acceptMode.value === "video") {
      qs.set("type", "video");
    } else if (acceptMode.value === "all") {
      // no type filter
    } else {
      qs.set("type", "image");
    }
    qs.set("page", String(page.value));
    if (search.value.trim()) qs.set("search", search.value.trim());
    if (currentFolder.value === "uncategorized") {
      qs.set("folder_id", "uncategorized");
    } else if (typeof currentFolder.value === "number") {
      qs.set("folder_id", String(currentFolder.value));
    }

    const res = await apiFetch<Paginated<MediaAsset>>(
      `/api/admin/media?${qs.toString()}`,
    );
    media.value = res.data;
    page.value = res.current_page;
    lastPage.value = res.last_page;
  } catch (e) {
    alert(extractMessage(e));
  } finally {
    loading.value = false;
  }
}

function selectFolder(id: number | "all" | "uncategorized") {
  currentFolder.value = id;
  page.value = 1;
  load();
}

async function upload(event: Event) {
  const input = event.target as HTMLInputElement;
  const files = input.files;
  if (!files || files.length === 0) return;

  loading.value = true;
  try {
    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const form = new FormData();
      const isVideo =
        acceptMode.value === "video" ||
        (acceptMode.value === "all" && file.type.startsWith("video/"));

      if (isVideo) {
        form.append("video", file);
      } else {
        form.append("image", file);
      }
      if (typeof currentFolder.value === "number") {
        form.append("folder_id", String(currentFolder.value));
      }
      const endpoint = isVideo
        ? "/api/admin/media/videos"
        : "/api/admin/media/images";
      const res = await apiForm<{ asset: MediaAsset }>(endpoint, form);
      // Auto-select uploaded image
      if (multiple.value) {
        if (res.asset.url) {
          selectedById.value[res.asset.id] = res.asset;
          if (!selectedIds.value.includes(res.asset.id)) {
            selectedIds.value = [...selectedIds.value, res.asset.id];
          }
        }
      } else {
        selected.value = res.asset;
      }
    }
    input.value = "";
    await load();
  } catch (e) {
    alert(extractMessage(e));
  } finally {
    loading.value = false;
  }
}

const deleting = ref(false);

async function deleteSelected() {
  const asset = multiple.value ? null : selected.value;
  if (!asset) return;
  if (
    !confirm(`Xóa "${asset.original_name || "ảnh này"}"? Không thể hoàn tác.`)
  )
    return;

  deleting.value = true;
  try {
    await apiFetch(`/api/admin/media/${asset.id}`, { method: "DELETE" });
    selected.value = null;
    await load();
  } catch (e) {
    alert(extractMessage(e));
  } finally {
    deleting.value = false;
  }
}

let searchTimeout: number | null = null;
function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = window.setTimeout(() => {
    page.value = 1;
    load();
  }, 300);
}

function prevPage() {
  if (page.value > 1) {
    page.value--;
    load();
  }
}

function nextPage() {
  if (page.value < lastPage.value) {
    page.value++;
    load();
  }
}

watch(
  () => props.open,
  (v) => {
    if (v) {
      page.value = 1;
      clearSelection();
      search.value = "";
      currentFolder.value = "all";
      loadFolders();
      load();
    }
  },
);

onMounted(() => {
  if (props.open) {
    loadFolders();
    load();
  }
});
</script>

<style scoped>
.media-picker-modal {
  width: min(1100px, 95vw);
  height: min(700px, 90vh);
  background: #fff;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  display: grid;
  grid-template-columns: 200px 1fr;
  grid-template-rows: minmax(0, 1fr);
  overflow: hidden;
}

.picker-sidebar {
  background: var(--gray-50);
  border-right: 1px solid var(--gray-200);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.picker-sidebar-title {
  padding: 14px 16px;
  font-weight: 600;
  font-size: 13px;
  color: var(--gray-600);
  border-bottom: 1px solid var(--gray-200);
}

.picker-folder-list {
  flex: 1;
  overflow-y: auto;
  padding: 8px;
}

.picker-folder-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  text-align: left;
  color: var(--gray-600);
  font-size: 13px;
  transition: all 0.15s;
}

.picker-folder-item:hover {
  background: var(--gray-100);
}

.picker-folder-item.active {
  background: var(--primary-light);
  color: var(--primary);
}

.picker-folder-divider {
  height: 1px;
  background: var(--gray-200);
  margin: 6px 0;
}

.picker-main {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-height: 0;
}

.picker-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid var(--gray-100);
  flex-shrink: 0;
}

.picker-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: var(--gray-800);
}

.picker-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  gap: 12px;
  border-bottom: 1px solid var(--gray-100);
  flex-shrink: 0;
}

.picker-grid-container {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.picker-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 10px;
  padding: 16px 20px;
  flex: 1;
}

.picker-item {
  aspect-ratio: 1;
  border: 2px solid transparent;
  border-radius: var(--radius-sm);
  overflow: hidden;
  cursor: pointer;
  position: relative;
  background: var(--gray-100);
  transition: all 0.15s;
}

.picker-item:hover {
  border-color: var(--gray-300);
}

.picker-item.selected {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.picker-item-thumb {
  width: 100%;
  height: 100%;
}

.picker-item-thumb img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.picker-item-check {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 20px;
  height: 20px;
  background: var(--primary);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.picker-empty {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
  color: var(--gray-400);
}

.picker-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 12px;
  border-top: 1px solid var(--gray-100);
}

.picker-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-top: 1px solid var(--gray-200);
  background: var(--gray-50);
  flex-shrink: 0;
}

.picker-selection {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: var(--gray-700);
}

.picker-preview {
  width: 36px;
  height: 36px;
  object-fit: cover;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .media-picker-modal {
    grid-template-columns: 1fr;
  }

  .picker-sidebar {
    display: none;
  }
}

.video-thumb-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
  color: #a5b4fc;
  gap: 4px;
}
.video-thumb-placeholder svg {
  color: #818cf8;
}
.video-name {
  font-size: 9px;
  text-align: center;
  max-width: 90%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #c7d2fe;
}
</style>
