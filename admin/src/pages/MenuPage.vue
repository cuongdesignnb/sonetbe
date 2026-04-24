<template>
  <div class="page">
    <div class="page-header">
      <h1>Quản lý Menu</h1>
      <p class="page-subtitle">
        Kéo thả để sắp xếp thứ tự menu. Hỗ trợ 2 cấp (parent → children).
      </p>
    </div>

    <!-- Add Menu Form -->
    <div class="card mb-6">
      <div class="card-header">
        <h3>{{ editingId ? "Sửa menu" : "Thêm Menu mới" }}</h3>
      </div>
      <div class="card-body">
        <form
          @submit.prevent="editingId ? handleUpdate() : handleCreate()"
          class="form-row"
        >
          <div class="form-group" style="flex: 2">
            <label>Tiêu đề *</label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="e.g. Trang chủ"
              class="input"
            />
          </div>
          <div class="form-group" style="flex: 2">
            <label>URL *</label>
            <input
              v-model="form.url"
              type="text"
              required
              placeholder="e.g. / hoặc /courses"
              class="input"
            />
          </div>
          <div class="form-group" style="flex: 1">
            <label>Parent</label>
            <select v-model="form.parent_id" class="input">
              <option :value="null">— Không (Top level) —</option>
              <option v-for="m in topLevelMenus" :key="m.id" :value="m.id">
                {{ m.title }}
              </option>
            </select>
          </div>
          <div class="form-group" style="flex: 1">
            <label>Mở tab</label>
            <select v-model="form.target" class="input">
              <option value="_self">Cùng tab</option>
              <option value="_blank">Tab mới</option>
            </select>
          </div>
          <div class="form-group form-actions">
            <button type="submit" class="btn btn-primary" :disabled="saving">
              {{ editingId ? "Cập nhật" : "Thêm" }}
            </button>
            <button
              v-if="editingId"
              type="button"
              class="btn btn-outline"
              @click="cancelEdit"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Menu List -->
    <div class="card">
      <div class="card-header">
        <h3>Danh sách Menu</h3>
        <span class="badge">{{ flatCount }} items</span>
      </div>
      <div
        class="card-body"
        v-if="loading"
        style="text-align: center; padding: 40px"
      >
        Đang tải...
      </div>
      <div class="card-body menu-list" v-else-if="menus.length > 0">
        <div
          v-for="(menu, pi) in menus"
          :key="menu.id"
          class="menu-item-wrapper"
        >
          <!-- Parent item -->
          <div
            class="menu-item"
            :class="{
              dragging: dragId === menu.id,
              'drop-above':
                dropTarget?.id === menu.id && dropTarget?.zone === 'above',
              'drop-inside':
                dropTarget?.id === menu.id && dropTarget?.zone === 'inside',
              'drop-below':
                dropTarget?.id === menu.id && dropTarget?.zone === 'below',
            }"
            draggable="true"
            @dragstart="onDragStart($event, menu, pi, null)"
            @dragover.prevent="onDragOver($event, menu, false)"
            @dragleave="onDragLeave"
            @drop="onDrop($event)"
          >
            <div class="drag-handle" title="Kéo để sắp xếp">⠿</div>
            <div class="menu-info">
              <strong>{{ menu.title }}</strong>
              <span class="menu-url">{{ menu.url }}</span>
              <span v-if="menu.target === '_blank'" class="menu-badge"
                >↗ Tab mới</span
              >
              <span v-if="!menu.is_active" class="menu-badge inactive">Ẩn</span>
            </div>
            <div class="menu-actions">
              <button class="btn-icon" title="Sửa" @click="startEdit(menu)">
                ✏️
              </button>
              <button
                class="btn-icon"
                :title="menu.is_active ? 'Ẩn' : 'Hiện'"
                @click="toggleActive(menu)"
              >
                {{ menu.is_active ? "👁️" : "🚫" }}
              </button>
              <button
                class="btn-icon danger"
                title="Xóa"
                @click="handleDelete(menu)"
              >
                🗑️
              </button>
            </div>
          </div>

          <!-- Children -->
          <div
            class="children-list"
            v-if="menu.children && menu.children.length > 0"
          >
            <div
              v-for="(child, ci) in menu.children"
              :key="child.id"
              class="menu-item child"
              :class="{
                dragging: dragId === child.id,
                'drop-above':
                  dropTarget?.id === child.id && dropTarget?.zone === 'above',
                'drop-below':
                  dropTarget?.id === child.id && dropTarget?.zone === 'below',
              }"
              draggable="true"
              @dragstart="onDragStart($event, child, ci, menu.id)"
              @dragover.prevent="onDragOver($event, child, true)"
              @dragleave="onDragLeave"
              @drop="onDrop($event)"
            >
              <div class="drag-handle" title="Kéo để sắp xếp">⠿</div>
              <div class="child-indent">↳</div>
              <div class="menu-info">
                <strong>{{ child.title }}</strong>
                <span class="menu-url">{{ child.url }}</span>
                <span v-if="child.target === '_blank'" class="menu-badge"
                  >↗ Tab mới</span
                >
                <span v-if="!child.is_active" class="menu-badge inactive"
                  >Ẩn</span
                >
              </div>
              <div class="menu-actions">
                <button class="btn-icon" title="Sửa" @click="startEdit(child)">
                  ✏️
                </button>
                <button
                  class="btn-icon"
                  :title="child.is_active ? 'Ẩn' : 'Hiện'"
                  @click="toggleActive(child)"
                >
                  {{ child.is_active ? "👁️" : "🚫" }}
                </button>
                <button
                  class="btn-icon danger"
                  title="Xóa"
                  @click="handleDelete(child)"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div
        class="card-body"
        v-else
        style="text-align: center; padding: 40px; color: #888"
      >
        Chưa có menu nào. Thêm menu mới bằng form phía trên.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { apiFetch } from "../lib/api";

interface MenuItem {
  id: number;
  parent_id: number | null;
  title: string;
  url: string;
  target: string;
  icon: string | null;
  position: number;
  is_active: boolean;
  children?: MenuItem[];
}

const menus = ref<MenuItem[]>([]);
const loading = ref(true);
const saving = ref(false);
const editingId = ref<number | null>(null);

const form = ref({
  title: "",
  url: "/",
  parent_id: null as number | null,
  target: "_self",
});

const dragId = ref<number | null>(null);
const dropTarget = ref<{
  id: number;
  zone: "above" | "inside" | "below";
} | null>(null);
let dragData: {
  item: MenuItem;
  index: number;
  parentId: number | null;
} | null = null;

const topLevelMenus = computed(() => menus.value.filter((m) => !m.parent_id));
const flatCount = computed(() => {
  let count = 0;
  for (const m of menus.value) {
    count++;
    if (m.children) count += m.children.length;
  }
  return count;
});

/* ── API calls ── */

async function loadMenus() {
  loading.value = true;
  try {
    const res: any = await apiFetch("/api/admin/menus");
    menus.value = res.menus || [];
  } catch {
    menus.value = [];
  } finally {
    loading.value = false;
  }
}

async function handleCreate() {
  saving.value = true;
  try {
    await apiFetch("/api/admin/menus", {
      method: "POST",
      body: JSON.stringify(form.value),
    });
    resetForm();
    await loadMenus();
  } catch (e: any) {
    alert(e.message || "Lỗi khi tạo menu");
  } finally {
    saving.value = false;
  }
}

async function handleUpdate() {
  if (!editingId.value) return;
  saving.value = true;
  try {
    await apiFetch(`/api/admin/menus/${editingId.value}`, {
      method: "PUT",
      body: JSON.stringify(form.value),
    });
    resetForm();
    await loadMenus();
  } catch (e: any) {
    alert(e.message || "Lỗi khi cập nhật menu");
  } finally {
    saving.value = false;
  }
}

async function handleDelete(item: MenuItem) {
  if (
    !confirm(
      `Xóa menu "${item.title}"?${item.children?.length ? " (Bao gồm tất cả menu con)" : ""}`,
    )
  )
    return;
  try {
    await apiFetch(`/api/admin/menus/${item.id}`, { method: "DELETE" });
    await loadMenus();
  } catch (e: any) {
    alert(e.message || "Lỗi khi xóa menu");
  }
}

async function toggleActive(item: MenuItem) {
  try {
    await apiFetch(`/api/admin/menus/${item.id}`, {
      method: "PUT",
      body: JSON.stringify({ is_active: !item.is_active }),
    });
    await loadMenus();
  } catch (e: any) {
    alert(e.message || "Lỗi khi cập nhật trạng thái");
  }
}

async function saveReorder() {
  // Build flat items array from current tree state
  const items: { id: number; parent_id: number | null; position: number }[] =
    [];
  menus.value.forEach((m, pi) => {
    items.push({ id: m.id, parent_id: null, position: pi });
    if (m.children) {
      m.children.forEach((c, ci) => {
        items.push({ id: c.id, parent_id: m.id, position: ci });
      });
    }
  });
  try {
    const res: any = await apiFetch("/api/admin/menus/reorder", {
      method: "POST",
      body: JSON.stringify({ items }),
    });
    menus.value = res.menus || [];
  } catch (e: any) {
    alert(e.message || "Lỗi khi cập nhật thứ tự");
    await loadMenus();
  }
}

/* ── Form helpers ── */

function startEdit(item: MenuItem) {
  editingId.value = item.id;
  form.value = {
    title: item.title,
    url: item.url,
    parent_id: item.parent_id,
    target: item.target || "_self",
  };
}

function cancelEdit() {
  resetForm();
}

function resetForm() {
  editingId.value = null;
  form.value = { title: "", url: "/", parent_id: null, target: "_self" };
}

/* ── Drag & Drop ── */

function onDragStart(
  e: DragEvent,
  item: MenuItem,
  index: number,
  parentId: number | null,
) {
  dragId.value = item.id;
  dragData = { item, index, parentId };
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = "move";
  }
}

function onDragOver(e: DragEvent, targetItem: MenuItem, isChild: boolean) {
  e.preventDefault();
  if (!dragData || dragData.item.id === targetItem.id) return;

  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect();
  const y = e.clientY - rect.top;
  const h = rect.height;

  if (isChild) {
    // Children only support above/below (no nesting deeper than 2 levels)
    dropTarget.value = {
      id: targetItem.id,
      zone: y < h / 2 ? "above" : "below",
    };
  } else {
    // Top-level items: top 25% = above, middle 50% = inside (make child), bottom 25% = below
    if (y < h * 0.25) {
      dropTarget.value = { id: targetItem.id, zone: "above" };
    } else if (y > h * 0.75) {
      dropTarget.value = { id: targetItem.id, zone: "below" };
    } else {
      // Don't allow dropping into itself or into an item that is already a child being dragged
      dropTarget.value = { id: targetItem.id, zone: "inside" };
    }
  }
}

function onDragLeave() {
  dropTarget.value = null;
}

function removeFromTree(itemId: number) {
  // Remove from top-level
  const topIdx = menus.value.findIndex((m) => m.id === itemId);
  if (topIdx >= 0) {
    const [removed] = menus.value.splice(topIdx, 1);
    return removed;
  }
  // Remove from children
  for (const parent of menus.value) {
    if (parent.children) {
      const childIdx = parent.children.findIndex((c) => c.id === itemId);
      if (childIdx >= 0) {
        const [removed] = parent.children.splice(childIdx, 1);
        return removed;
      }
    }
  }
  return null;
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  if (!dragData || !dropTarget.value) {
    dragId.value = null;
    dragData = null;
    dropTarget.value = null;
    return;
  }

  const target = dropTarget.value;
  const srcItem = dragData.item;

  // Don't drop on self
  if (srcItem.id === target.id) {
    dragId.value = null;
    dragData = null;
    dropTarget.value = null;
    return;
  }

  // Remove source from tree
  const removed = removeFromTree(srcItem.id);
  if (!removed) {
    dragId.value = null;
    dragData = null;
    dropTarget.value = null;
    return;
  }

  // Detach children if moving into a parent (only 2 levels allowed)
  // If the dragged item had children and we're making it a child, promote its children to top-level
  if (
    target.zone === "inside" &&
    removed.children &&
    removed.children.length > 0
  ) {
    // Move orphaned children to top-level
    const orphans = removed.children.map((c) => ({
      ...c,
      parent_id: null,
      children: [],
    }));
    menus.value.push(...orphans);
    removed.children = [];
  }

  if (target.zone === "inside") {
    // Make dragged item a child of the target
    const parentMenu = menus.value.find((m) => m.id === target.id);
    if (parentMenu) {
      if (!parentMenu.children) parentMenu.children = [];
      removed.parent_id = parentMenu.id;
      parentMenu.children.push(removed);
    }
  } else {
    // Find target in tree
    const topIdx = menus.value.findIndex((m) => m.id === target.id);
    if (topIdx >= 0) {
      // Target is top-level → insert as top-level sibling
      removed.parent_id = null;
      removed.children = removed.children || [];
      const insertIdx = target.zone === "above" ? topIdx : topIdx + 1;
      menus.value.splice(insertIdx, 0, removed);
    } else {
      // Target is a child → insert as sibling child
      for (const parent of menus.value) {
        if (parent.children) {
          const childIdx = parent.children.findIndex((c) => c.id === target.id);
          if (childIdx >= 0) {
            removed.parent_id = parent.id;
            const insertIdx = target.zone === "above" ? childIdx : childIdx + 1;
            parent.children.splice(insertIdx, 0, removed);
            break;
          }
        }
      }
    }
  }

  dragId.value = null;
  dragData = null;
  dropTarget.value = null;

  // Save reorder to server
  saveReorder();
}

onMounted(loadMenus);
</script>

<style scoped>
.mb-6 {
  margin-bottom: 1.5rem;
}

.form-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 120px;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #555;
}

.form-actions {
  display: flex;
  gap: 8px;
  align-items: flex-end;
  padding-bottom: 2px;
}

.page-subtitle {
  color: #888;
  font-size: 14px;
  margin-top: 4px;
}

.badge {
  font-size: 12px;
  background: #e8e8e8;
  color: #555;
  padding: 2px 10px;
  border-radius: 10px;
}

/* Menu list styles */
.menu-list {
  padding: 0 !important;
}

.menu-item-wrapper {
  border-bottom: 1px solid #eee;
}

.menu-item-wrapper:last-child {
  border-bottom: none;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  transition: background 0.15s;
  cursor: grab;
}

.menu-item:hover {
  background: #fafafa;
}

.menu-item.dragging {
  opacity: 0.4;
  background: #f0f0f0;
}

.menu-item.drop-above {
  border-top: 3px solid #f97316;
  background: #fff7ed;
}

.menu-item.drop-below {
  border-bottom: 3px solid #f97316;
  background: #fff7ed;
}

.menu-item.drop-inside {
  background: #fef3c7;
  box-shadow: inset 0 0 0 2px #f59e0b;
  border-radius: 6px;
}

.menu-item.child {
  padding-left: 40px;
  background: #fafafa;
}

.menu-item.child:hover {
  background: #f5f5f5;
}

.drag-handle {
  cursor: grab;
  font-size: 18px;
  color: #ccc;
  user-select: none;
  flex-shrink: 0;
}

.drag-handle:hover {
  color: #999;
}

.child-indent {
  color: #ccc;
  font-size: 14px;
  flex-shrink: 0;
}

.menu-info {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.menu-info strong {
  font-size: 14px;
  color: #333;
}

.menu-url {
  font-size: 12px;
  color: #999;
  background: #f5f5f5;
  padding: 2px 8px;
  border-radius: 4px;
  font-family: monospace;
}

.menu-badge {
  font-size: 11px;
  background: #e0f2fe;
  color: #0284c7;
  padding: 1px 8px;
  border-radius: 8px;
  white-space: nowrap;
}

.menu-badge.inactive {
  background: #fef2f2;
  color: #dc2626;
}

.menu-actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.btn-icon {
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 4px;
  font-size: 14px;
  transition: background 0.15s;
}

.btn-icon:hover {
  background: #f0f0f0;
}

.btn-icon.danger:hover {
  background: #fee;
}

.children-list {
  border-top: 1px solid #f0f0f0;
}
</style>
