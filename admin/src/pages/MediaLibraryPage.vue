<template>
  <div class="media-library-layout">
    <!-- Sidebar: Folder Tree -->
    <aside class="media-sidebar">
      <div class="sidebar-header">
        <span class="sidebar-title">Thư mục</span>
        <button
          class="btn-icon"
          title="Tạo thư mục"
          @click="showNewFolder = true"
        >
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M12 5v14M5 12h14" />
          </svg>
        </button>
      </div>

      <nav class="folder-tree">
        <button
          class="folder-item"
          :class="{ active: currentFolder === 'all' }"
          @click="selectFolder('all')"
        >
          <svg
            width="18"
            height="18"
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
          <span>Tất cả</span>
          <span class="folder-count">{{ totalCount }}</span>
        </button>

        <button
          class="folder-item"
          :class="{ active: currentFolder === 'uncategorized' }"
          @click="selectFolder('uncategorized')"
        >
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"
            />
          </svg>
          <span>Chưa phân loại</span>
          <span class="folder-count">{{ uncategorizedCount }}</span>
        </button>

        <div class="folder-divider"></div>

        <template v-for="folder in rootFolders" :key="folder.id">
          <FolderTreeItem
            :folder="folder"
            :folders="folders"
            :counts="folderCounts"
            :current-folder="currentFolder"
            :depth="0"
            @select="selectFolder"
            @edit="editFolder"
            @delete="confirmDeleteFolder"
          />
        </template>
      </nav>

      <!-- New Folder Form -->
      <div v-if="showNewFolder" class="new-folder-form">
        <input
          v-model="newFolderName"
          class="input"
          placeholder="Tên thư mục"
          @keyup.enter="createFolder"
          @keyup.escape="showNewFolder = false"
          ref="newFolderInput"
        />
        <div class="row" style="gap: 6px; margin-top: 8px">
          <button class="btn btn-sm" @click="createFolder">Tạo</button>
          <button
            class="btn btn-secondary btn-sm"
            @click="showNewFolder = false"
          >
            Hủy
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="media-main">
      <!-- Header -->
      <div class="media-header">
        <div class="media-header-left">
          <h1 class="h1" style="margin: 0">Thư viện ảnh</h1>
          <span class="muted">{{ currentFolderName }}</span>
        </div>
        <div class="media-header-right">
          <div class="view-toggle">
            <button
              class="view-btn"
              :class="{ active: viewMode === 'grid' }"
              @click="viewMode = 'grid'"
              title="Dạng lưới"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <rect x="3" y="3" width="7" height="7" />
                <rect x="14" y="3" width="7" height="7" />
                <rect x="3" y="14" width="7" height="7" />
                <rect x="14" y="14" width="7" height="7" />
              </svg>
            </button>
            <button
              class="view-btn"
              :class="{ active: viewMode === 'list' }"
              @click="viewMode = 'list'"
              title="Dạng danh sách"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <line x1="8" y1="6" x2="21" y2="6" />
                <line x1="8" y1="12" x2="21" y2="12" />
                <line x1="8" y1="18" x2="21" y2="18" />
                <line x1="3" y1="6" x2="3.01" y2="6" />
                <line x1="3" y1="12" x2="3.01" y2="12" />
                <line x1="3" y1="18" x2="3.01" y2="18" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="media-toolbar">
        <div class="toolbar-left">
          <div class="search-box">
            <svg
              width="18"
              height="18"
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
        </div>
        <div class="toolbar-right">
          <label class="upload-btn btn">
            <svg
              width="18"
              height="18"
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
              accept="image/*"
              multiple
              hidden
              @change="handleUpload"
            />
          </label>
        </div>
      </div>

      <!-- Drop Zone -->
      <div
        class="drop-zone"
        :class="{ active: isDragging }"
        @dragenter.prevent="isDragging = true"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <div v-if="isDragging" class="drop-overlay">
          <svg
            width="48"
            height="48"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
          >
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
          </svg>
          <span>Thả file để tải lên</span>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="media-loading">
          <div class="spinner"></div>
          <span>Đang tải...</span>
        </div>

        <!-- Grid View -->
        <div v-else-if="viewMode === 'grid'" class="media-grid-wp">
          <div
            v-for="m in media"
            :key="m.id"
            class="media-card"
            :class="{ selected: selectedAsset?.id === m.id }"
            @click="selectAsset(m)"
          >
            <div class="media-card-thumb">
              <button
                class="media-card-delete"
                title="Xóa"
                @click.stop="confirmDeleteAsset(m)"
              >
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <polyline points="3 6 5 6 21 6" />
                  <path
                    d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
                  />
                </svg>
              </button>
              <img v-if="m.url" :src="m.url" :alt="m.original_name || ''" />
              <div v-else class="media-card-placeholder">
                <svg
                  width="32"
                  height="32"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <rect x="3" y="3" width="18" height="18" rx="2" />
                  <circle cx="8.5" cy="8.5" r="1.5" />
                  <polyline points="21 15 16 10 5 21" />
                </svg>
              </div>
              <div class="media-card-check" v-if="selectedAsset?.id === m.id">
                <svg
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                >
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                </svg>
              </div>
            </div>
            <div class="media-card-info">
              <span class="media-card-name">{{
                truncateName(m.original_name)
              }}</span>
              <span class="media-card-size">{{ formatSize(m.size) }}</span>
            </div>
          </div>

          <div v-if="media.length === 0" class="media-empty">
            <svg
              width="64"
              height="64"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1"
            >
              <rect x="3" y="3" width="18" height="18" rx="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <polyline points="21 15 16 10 5 21" />
            </svg>
            <span>Không tìm thấy hình ảnh</span>
            <span class="muted">Tải lên một số hình ảnh để bắt đầu</span>
          </div>
        </div>

        <!-- List View -->
        <div v-else class="media-list-wp">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 60px"></th>
                <th>Tập tin</th>
                <th style="width: 120px">Kích thước</th>
                <th style="width: 180px">Ngày tải</th>
                <th style="width: 80px"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="m in media"
                :key="m.id"
                :class="{ selected: selectedAsset?.id === m.id }"
                @click="selectAsset(m)"
              >
                <td>
                  <div class="list-thumb">
                    <img
                      v-if="m.url"
                      :src="m.url"
                      :alt="m.original_name || ''"
                    />
                  </div>
                </td>
                <td>
                  <div class="list-name">
                    {{ m.original_name || `#${m.id}` }}
                  </div>
                  <div class="list-type muted">{{ m.mime_type }}</div>
                </td>
                <td class="muted">{{ formatSize(m.size) }}</td>
                <td class="muted">{{ formatDate(m.created_at) }}</td>
                <td>
                  <button
                    class="btn-icon btn-danger-icon"
                    @click.stop="confirmDeleteAsset(m)"
                    title="Xóa"
                  >
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <polyline points="3 6 5 6 21 6" />
                      <path
                        d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
                      />
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="media.length === 0" class="media-empty">
            <span>Không tìm thấy hình ảnh</span>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="media-pagination" v-if="lastPage > 1">
        <button
          class="btn btn-secondary btn-sm"
          :disabled="page <= 1"
          @click="prevPage"
        >
          ← Trước
        </button>
        <span class="pagination-info">Trang {{ page }} / {{ lastPage }}</span>
        <button
          class="btn btn-secondary btn-sm"
          :disabled="page >= lastPage"
          @click="nextPage"
        >
          Sau →
        </button>
      </div>
    </main>

    <!-- Details Panel -->
    <aside class="media-details" :class="{ open: !!selectedAsset }">
      <template v-if="selectedAsset">
        <div class="details-header">
          <h3>Chi tiết tập tin</h3>
          <button class="btn-icon" @click="selectedAsset = null">
            <svg
              width="18"
              height="18"
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

        <div class="details-preview">
          <img
            v-if="selectedAsset.url"
            :src="selectedAsset.url"
            :alt="selectedAsset.original_name || ''"
          />
        </div>

        <div class="details-form">
          <div class="detail-row">
            <label>Tên tập tin</label>
            <input v-model="editName" class="input" @blur="updateAssetName" />
          </div>

          <div class="detail-row">
            <label>Thư mục</label>
            <select
              v-model="editFolderId"
              class="input"
              @change="updateAssetFolder"
            >
              <option :value="null">Chưa phân loại</option>
              <option v-for="f in folders" :key="f.id" :value="f.id">
                {{ getFolderPath(f) }}
              </option>
            </select>
          </div>

          <div class="detail-row">
            <label>Đường dẫn</label>
            <div class="url-box">
              <input
                :value="selectedAsset.url || ''"
                class="input"
                readonly
                @focus="selectAll"
              />
              <button class="btn btn-secondary btn-sm" @click="copyUrl">
                Sao chép
              </button>
            </div>
          </div>

          <div class="detail-row">
            <label>Kích thước</label>
            <span class="detail-value">{{
              formatSize(selectedAsset.size)
            }}</span>
          </div>

          <div class="detail-row">
            <label>Ngày tải</label>
            <span class="detail-value">{{
              formatDate(selectedAsset.created_at)
            }}</span>
          </div>

          <div class="details-actions">
            <button
              class="btn btn-danger"
              @click="confirmDeleteAsset(selectedAsset)"
            >
              <svg
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <polyline points="3 6 5 6 21 6" />
                <path
                  d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"
                />
              </svg>
              Xóa vĩnh viễn
            </button>
          </div>
        </div>
      </template>
    </aside>

    <!-- Edit Folder Modal -->
    <div
      v-if="editingFolder"
      class="modal-backdrop"
      @click.self="editingFolder = null"
    >
      <div class="modal" style="max-width: 400px">
        <h3 style="margin: 0 0 16px">Sửa thư mục</h3>
        <div class="form">
          <label class="label">
            <span>Tên</span>
            <input v-model="editingFolder.name" class="input" />
          </label>
          <label class="label">
            <span>Thư mục cha</span>
            <select v-model="editingFolder.parent_id" class="input">
              <option :value="null">(Gốc)</option>
              <option
                v-for="f in folders.filter((x) => x.id !== editingFolder!.id)"
                :key="f.id"
                :value="f.id"
              >
                {{ getFolderPath(f) }}
              </option>
            </select>
          </label>
        </div>
        <div
          class="row"
          style="justify-content: flex-end; margin-top: 16px; gap: 8px"
        >
          <button class="btn btn-secondary" @click="editingFolder = null">
            Hủy
          </button>
          <button class="btn" @click="saveFolder">Lưu</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { apiFetch, apiForm, extractMessage, HttpError } from "../lib/api";
import type { MediaAsset, MediaFolder } from "../lib/types";
import FolderTreeItem from "../components/FolderTreeItem.vue";

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

// State
const folders = ref<MediaFolder[]>([]);
const folderCounts = ref<Record<number, number>>({});
const uncategorizedCount = ref(0);
const media = ref<MediaAsset[]>([]);
const loading = ref(false);
const page = ref(1);
const lastPage = ref(1);
const search = ref("");
const currentFolder = ref<number | "all" | "uncategorized">("all");
const viewMode = ref<"grid" | "list">("grid");
const selectedAsset = ref<MediaAsset | null>(null);
const isDragging = ref(false);

// Edit states
const editName = ref("");
const editFolderId = ref<number | null>(null);
const editingFolder = ref<MediaFolder | null>(null);

// New folder
const showNewFolder = ref(false);
const newFolderName = ref("");
const newFolderInput = ref<HTMLInputElement | null>(null);

// Computed
const rootFolders = computed(() => folders.value.filter((f) => !f.parent_id));
const totalCount = computed(() => {
  let total = uncategorizedCount.value;
  for (const count of Object.values(folderCounts.value)) {
    total += count;
  }
  return total;
});

const currentFolderName = computed(() => {
  if (currentFolder.value === "all") return "Tất cả";
  if (currentFolder.value === "uncategorized") return "Chưa phân loại";
  const f = folders.value.find((x) => x.id === currentFolder.value);
  return f?.name || "Thư mục";
});

// Functions
function truncateName(name: string | null, max = 24): string {
  if (!name) return "Untitled";
  if (name.length <= max) return name;
  return name.slice(0, max - 3) + "...";
}

function formatSize(bytes: number | null): string {
  if (!bytes) return "—";
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / (1024 * 1024)).toFixed(2) + " MB";
}

function formatDate(d: string): string {
  try {
    return new Date(d).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  } catch {
    return d;
  }
}

function selectAll(e: Event) {
  (e.target as HTMLInputElement).select();
}

function getFolderPath(folder: MediaFolder): string {
  const parts: string[] = [folder.name];
  let current = folder;
  while (current.parent_id) {
    const parent = folders.value.find((f) => f.id === current.parent_id);
    if (!parent) break;
    parts.unshift(parent.name);
    current = parent;
  }
  return parts.join(" / ");
}

// API calls
async function loadFolders() {
  try {
    const res = await apiFetch<FoldersResponse>("/api/admin/media/folders");
    folders.value = res.folders;
    folderCounts.value = res.counts;
    uncategorizedCount.value = res.uncategorized;
  } catch (e) {
    console.error("Failed to load folders", e);
  }
}

async function loadMedia() {
  loading.value = true;
  try {
    const qs = new URLSearchParams();
    qs.set("type", "image");
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
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    alert(extractMessage(e));
  } finally {
    loading.value = false;
  }
}

function selectFolder(id: number | "all" | "uncategorized") {
  currentFolder.value = id;
  page.value = 1;
  selectedAsset.value = null;
  loadMedia();
}

function selectAsset(asset: MediaAsset) {
  selectedAsset.value = asset;
  editName.value = asset.original_name || "";
  editFolderId.value = asset.folder_id;
}

async function createFolder() {
  if (!newFolderName.value.trim()) return;
  try {
    await apiFetch("/api/admin/media/folders", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: newFolderName.value.trim(),
        parent_id:
          typeof currentFolder.value === "number" ? currentFolder.value : null,
      }),
    });
    newFolderName.value = "";
    showNewFolder.value = false;
    await loadFolders();
  } catch (e) {
    alert(extractMessage(e));
  }
}

function editFolder(folder: MediaFolder) {
  editingFolder.value = { ...folder };
}

async function saveFolder() {
  if (!editingFolder.value) return;
  try {
    await apiFetch(`/api/admin/media/folders/${editingFolder.value.id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: editingFolder.value.name,
        parent_id: editingFolder.value.parent_id,
      }),
    });
    editingFolder.value = null;
    await loadFolders();
  } catch (e) {
    alert(extractMessage(e));
  }
}

async function confirmDeleteFolder(folder: MediaFolder) {
  if (
    !confirm(
      `Xóa thư mục "${folder.name}"? Các file sẽ được chuyển về Chưa phân loại.`,
    )
  )
    return;
  try {
    await apiFetch(`/api/admin/media/folders/${folder.id}`, {
      method: "DELETE",
    });
    if (currentFolder.value === folder.id) {
      currentFolder.value = "all";
    }
    await loadFolders();
    await loadMedia();
  } catch (e) {
    alert(extractMessage(e));
  }
}

async function updateAssetName() {
  if (!selectedAsset.value) return;
  if (editName.value === selectedAsset.value.original_name) return;
  try {
    await apiFetch(`/api/admin/media/${selectedAsset.value.id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ original_name: editName.value }),
    });
    selectedAsset.value.original_name = editName.value;
    // Update in list
    const idx = media.value.findIndex((m) => m.id === selectedAsset.value!.id);
    if (idx >= 0) media.value[idx].original_name = editName.value;
  } catch (e) {
    alert(extractMessage(e));
  }
}

async function updateAssetFolder() {
  if (!selectedAsset.value) return;
  try {
    await apiFetch(`/api/admin/media/${selectedAsset.value.id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ folder_id: editFolderId.value }),
    });
    selectedAsset.value.folder_id = editFolderId.value;
    await loadFolders();
    await loadMedia();
  } catch (e) {
    alert(extractMessage(e));
  }
}

async function confirmDeleteAsset(asset: MediaAsset) {
  if (!confirm("Xóa vĩnh viễn tập tin này?")) return;
  try {
    await apiFetch(`/api/admin/media/${asset.id}`, { method: "DELETE" });
    if (selectedAsset.value?.id === asset.id) {
      selectedAsset.value = null;
    }
    await loadFolders();
    await loadMedia();
  } catch (e) {
    alert(extractMessage(e));
  }
}

async function copyUrl() {
  if (!selectedAsset.value?.url) return;
  await navigator.clipboard.writeText(selectedAsset.value.url);
}

async function uploadFiles(files: FileList | File[]) {
  for (const file of Array.from(files)) {
    if (!file.type.startsWith("image/")) continue;
    try {
      const form = new FormData();
      form.append("image", file);
      if (typeof currentFolder.value === "number") {
        form.append("folder_id", String(currentFolder.value));
      }
      await apiForm("/api/admin/media/images", form);
    } catch (e) {
      alert(`Failed to upload ${file.name}: ${extractMessage(e)}`);
    }
  }
  await loadFolders();
  await loadMedia();
}

function handleUpload(e: Event) {
  const input = e.target as HTMLInputElement;
  if (input.files) {
    uploadFiles(input.files);
    input.value = "";
  }
}

function handleDrop(e: DragEvent) {
  isDragging.value = false;
  if (e.dataTransfer?.files) {
    uploadFiles(e.dataTransfer.files);
  }
}

let searchTimeout: number | null = null;
function debouncedSearch() {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = window.setTimeout(() => {
    page.value = 1;
    loadMedia();
  }, 300);
}

function prevPage() {
  if (page.value > 1) {
    page.value--;
    loadMedia();
  }
}

function nextPage() {
  if (page.value < lastPage.value) {
    page.value++;
    loadMedia();
  }
}

watch(showNewFolder, (v) => {
  if (v) {
    nextTick(() => newFolderInput.value?.focus());
  }
});

onMounted(() => {
  loadFolders();
  loadMedia();
});
</script>
