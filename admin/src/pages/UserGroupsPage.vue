<template>
  <div class="user-groups-page">
    <!-- Header -->
    <div class="page-header">
      <div class="page-header-content">
        <div class="page-icon">
          <svg
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
            />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
        </div>
        <div>
          <h1 class="page-title">Nhóm người dùng</h1>
          <p class="page-subtitle">Quản lý nhóm học viên</p>
        </div>
      </div>
      <div style="display: flex; gap: 8px">
        <button class="btn" :disabled="loading" @click="loadGroups">
          <svg
            v-if="!loading"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"
            />
          </svg>
          <svg
            v-else
            class="spin"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle
              cx="12"
              cy="12"
              r="10"
              stroke-dasharray="60"
              stroke-dashoffset="20"
            />
          </svg>
          {{ loading ? "Đang tải..." : "Làm mới" }}
        </button>
        <button class="btn btn-primary" @click="openCreate">
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Tạo nhóm mới
        </button>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger" style="margin-bottom: 20px">
      <svg
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
      {{ error }}
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-info">
          <span class="table-count">{{ groups.length }} nhóm</span>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 70px">ID</th>
              <th>Tên nhóm</th>
              <th>Mô tả</th>
              <th style="width: 130px">Số thành viên</th>
              <th style="width: 120px">Trạng thái</th>
              <th style="width: 200px">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="g in groups" :key="g.id" class="table-row">
              <td>
                <span class="id-badge">#{{ g.id }}</span>
              </td>
              <td>
                <div class="group-name-cell">
                  <span
                    class="group-color-dot"
                    :style="{ background: g.color || '#6366f1' }"
                  ></span>
                  <span class="group-name">{{ g.name }}</span>
                </div>
              </td>
              <td>
                <span class="group-desc">{{ g.description || "—" }}</span>
              </td>
              <td>
                <span
                  class="members-badge"
                  @click="openMembers(g)"
                >
                  <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                  </svg>
                  {{ g.members_count ?? 0 }} thành viên
                </span>
              </td>
              <td>
                <span
                  :class="[
                    'status-badge',
                    g.is_active ? 'status-success' : '',
                  ]"
                >
                  <span class="status-dot"></span>
                  {{ g.is_active ? "Hoạt động" : "Tắt" }}
                </span>
              </td>
              <td style="text-align: right; white-space: nowrap">
                <button
                  class="btn btn-secondary btn-sm"
                  @click="openMembers(g)"
                >
                  Thành viên
                </button>
                <button
                  class="btn btn-secondary btn-sm"
                  @click="openEdit(g)"
                >
                  Sửa
                </button>
                <button class="btn btn-danger btn-sm" @click="remove(g)">
                  Xóa
                </button>
              </td>
            </tr>
            <tr v-if="!loading && groups.length === 0">
              <td colspan="6" class="empty-cell">
                <div class="empty-state">
                  <svg
                    width="48"
                    height="48"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                  >
                    <path
                      d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                    />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  <p>Chưa có nhóm nào</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div
      v-if="modalOpen"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal" style="width: min(520px, 95vw)">
        <div class="modal-header">
          <div class="modal-title">
            {{ editingId ? `Sửa nhóm #${editingId}` : "Tạo nhóm mới" }}
          </div>
          <button class="btn btn-secondary btn-sm" @click="closeModal">
            Đóng
          </button>
        </div>
        <div class="modal-body">
          <div v-if="formError" class="alert alert-danger" style="margin-bottom: 16px">
            {{ formError }}
          </div>

          <form @submit.prevent="save">
            <div class="form-group">
              <label class="form-label">Tên nhóm *</label>
              <input
                v-model="form.name"
                class="input"
                placeholder="Nhập tên nhóm..."
                required
              />
            </div>

            <div class="form-group">
              <label class="form-label">Mô tả</label>
              <textarea
                v-model="form.description"
                class="input textarea"
                rows="3"
                placeholder="Mô tả nhóm (không bắt buộc)..."
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Màu sắc</label>
              <div class="color-picker">
                <button
                  v-for="c in presetColors"
                  :key="c.value"
                  type="button"
                  class="color-option"
                  :class="{ 'color-option-active': form.color === c.value }"
                  :style="{ background: c.value }"
                  :title="c.label"
                  @click="form.color = c.value"
                >
                  <svg
                    v-if="form.color === c.value"
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="#fff"
                    stroke-width="3"
                  >
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </button>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Trạng thái</label>
              <label class="toggle-label">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  class="toggle-input"
                />
                <span class="toggle-switch"></span>
                <span class="toggle-text">{{
                  form.is_active ? "Hoạt động" : "Tắt"
                }}</span>
              </label>
            </div>

            <div class="modal-footer" style="padding: 0; border: none; margin-top: 20px">
              <button
                type="button"
                class="btn btn-secondary"
                @click="closeModal"
              >
                Hủy
              </button>
              <button class="btn btn-primary" :disabled="saving" type="submit">
                <svg
                  v-if="saving"
                  class="spin"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="10"
                    stroke-dasharray="60"
                    stroke-dashoffset="20"
                  />
                </svg>
                {{ saving ? "Đang lưu..." : "Lưu" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Members Management Modal -->
    <div
      v-if="membersOpen"
      class="modal-overlay"
      @click.self="closeMembers"
    >
      <div class="modal" style="width: min(640px, 95vw)">
        <div class="modal-header">
          <div style="display: flex; align-items: center; gap: 12px">
            <span
              class="group-color-dot"
              :style="{ background: membersGroup?.color || '#6366f1' }"
              style="width: 12px; height: 12px"
            ></span>
            <div class="modal-title">
              Thành viên — {{ membersGroup?.name }}
            </div>
          </div>
          <button class="btn btn-secondary btn-sm" @click="closeMembers">
            Đóng
          </button>
        </div>
        <div class="modal-body">
          <!-- Add members search -->
          <div class="form-group">
            <label class="form-label">Thêm thành viên</label>
            <div class="search-select">
              <input
                v-model="memberSearch"
                class="input"
                placeholder="Tìm theo tên hoặc email..."
                @input="debouncedSearchUsers"
              />
              <div
                v-if="memberSearchResults.length > 0"
                class="search-dropdown"
              >
                <div
                  v-for="u in memberSearchResults"
                  :key="u.id"
                  class="search-dropdown-item"
                  @click="addMember(u)"
                >
                  <div class="user-avatar-sm">
                    {{ u.name?.charAt(0)?.toUpperCase() || "?" }}
                  </div>
                  <div style="flex: 1; min-width: 0">
                    <div style="font-weight: 500">{{ u.name }}</div>
                    <div style="font-size: 12px; color: var(--gray-400)">
                      {{ u.email }}
                    </div>
                  </div>
                  <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="var(--primary, #6366f1)"
                    stroke-width="2"
                  >
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                  </svg>
                </div>
              </div>
            </div>
          </div>

          <!-- Members list -->
          <div class="members-section">
            <div class="members-section-header">
              <span>Danh sách thành viên</span>
              <span class="members-count-badge">{{ members.length }}</span>
            </div>

            <div v-if="membersLoading" style="text-align: center; padding: 32px 0">
              <div style="font-size: 14px; color: var(--gray-400, #94a3b8)">
                Đang tải...
              </div>
            </div>

            <div v-else-if="members.length > 0" class="members-list">
              <div
                v-for="m in members"
                :key="m.id"
                class="member-item"
              >
                <div class="user-avatar-sm">
                  {{ m.name?.charAt(0)?.toUpperCase() || "?" }}
                </div>
                <div style="flex: 1; min-width: 0">
                  <div class="member-name">{{ m.name }}</div>
                  <div class="member-email">{{ m.email }}</div>
                </div>
                <button
                  class="btn-icon-danger"
                  title="Xóa khỏi nhóm"
                  @click="removeMember(m)"
                >
                  <svg
                    width="16"
                    height="16"
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
            </div>

            <div v-else class="members-empty">
              Nhóm chưa có thành viên nào
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";

interface UserGroup {
  id: number;
  name: string;
  description?: string | null;
  color?: string | null;
  is_active: boolean;
  members_count?: number;
}

interface GroupMember {
  id: number;
  name: string;
  email: string;
}

const presetColors = [
  { value: "#6366f1", label: "Indigo" },
  { value: "#8b5cf6", label: "Violet" },
  { value: "#ec4899", label: "Pink" },
  { value: "#ef4444", label: "Red" },
  { value: "#f97316", label: "Orange" },
  { value: "#eab308", label: "Yellow" },
  { value: "#22c55e", label: "Green" },
  { value: "#06b6d4", label: "Cyan" },
];

const groups = ref<UserGroup[]>([]);
const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const formError = ref<string | null>(null);
const { success: toastSuccess, error: toastError } = useToast();

// Create/Edit modal
const modalOpen = ref(false);
const editingId = ref<number | null>(null);
const form = ref({
  name: "",
  description: "",
  color: "#6366f1",
  is_active: true,
});

// Members modal
const membersOpen = ref(false);
const membersGroup = ref<UserGroup | null>(null);
const members = ref<GroupMember[]>([]);
const membersLoading = ref(false);
const memberSearch = ref("");
const memberSearchResults = ref<GroupMember[]>([]);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

function resetForm() {
  form.value = {
    name: "",
    description: "",
    color: "#6366f1",
    is_active: true,
  };
  formError.value = null;
}

function openCreate() {
  editingId.value = null;
  resetForm();
  modalOpen.value = true;
}

function openEdit(g: UserGroup) {
  editingId.value = g.id;
  form.value = {
    name: g.name,
    description: g.description || "",
    color: g.color || "#6366f1",
    is_active: g.is_active,
  };
  formError.value = null;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
}

async function loadGroups() {
  loading.value = true;
  error.value = null;
  try {
    const res = await apiFetch<{ data: UserGroup[] }>(
      "/api/admin/user-groups",
    );
    groups.value = res.data || [];
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  formError.value = null;
  try {
    const payload = {
      name: form.value.name,
      description: form.value.description || null,
      color: form.value.color,
      is_active: form.value.is_active,
    };

    if (!editingId.value) {
      await apiFetch("/api/admin/user-groups", {
        method: "POST",
        body: JSON.stringify(payload),
      });
      toastSuccess("Đã tạo nhóm thành công.");
    } else {
      await apiFetch(`/api/admin/user-groups/${editingId.value}`, {
        method: "PUT",
        body: JSON.stringify(payload),
      });
      toastSuccess("Đã cập nhật nhóm thành công.");
    }

    closeModal();
    await loadGroups();
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    formError.value = extractMessage(e);
  } finally {
    saving.value = false;
  }
}

async function remove(g: UserGroup) {
  if (!confirm(`Xóa nhóm "${g.name}"?`)) return;
  try {
    await apiFetch(`/api/admin/user-groups/${g.id}`, { method: "DELETE" });
    toastSuccess("Đã xóa nhóm.");
    await loadGroups();
  } catch (e) {
    toastError(extractMessage(e) || "Xóa nhóm thất bại.");
  }
}

// Members management
async function openMembers(g: UserGroup) {
  membersGroup.value = g;
  membersOpen.value = true;
  memberSearch.value = "";
  memberSearchResults.value = [];
  await loadMembers(g.id);
}

function closeMembers() {
  membersOpen.value = false;
  membersGroup.value = null;
  members.value = [];
}

async function loadMembers(groupId: number) {
  membersLoading.value = true;
  try {
    const res = await apiFetch<{ data: GroupMember[] }>(
      `/api/admin/user-groups/${groupId}/members`,
    );
    members.value = res.data || [];
  } catch (e) {
    toastError(extractMessage(e) || "Không thể tải danh sách thành viên.");
  } finally {
    membersLoading.value = false;
  }
}

function debouncedSearchUsers() {
  if (searchTimer) clearTimeout(searchTimer);
  const q = memberSearch.value.trim();
  if (q.length < 2) {
    memberSearchResults.value = [];
    return;
  }
  searchTimer = setTimeout(() => searchUsers(q), 300);
}

async function searchUsers(q: string) {
  try {
    const res = await apiFetch<{
      data: { id: number; name: string; email: string }[];
    }>(`/api/admin/users?search=${encodeURIComponent(q)}&per_page=10`);
    // Filter out users already in the group
    const existingIds = new Set(members.value.map((m) => m.id));
    memberSearchResults.value = (res.data || []).filter(
      (u) => !existingIds.has(u.id),
    );
  } catch {
    memberSearchResults.value = [];
  }
}

async function addMember(u: GroupMember) {
  if (!membersGroup.value) return;
  try {
    await apiFetch(
      `/api/admin/user-groups/${membersGroup.value.id}/members`,
      {
        method: "POST",
        body: JSON.stringify({ user_ids: [u.id] }),
      },
    );
    toastSuccess(`Đã thêm ${u.name} vào nhóm.`);
    memberSearch.value = "";
    memberSearchResults.value = [];
    await loadMembers(membersGroup.value.id);
    await loadGroups();
  } catch (e) {
    toastError(extractMessage(e) || "Thêm thành viên thất bại.");
  }
}

async function removeMember(m: GroupMember) {
  if (!membersGroup.value) return;
  if (!confirm(`Xóa ${m.name} khỏi nhóm?`)) return;
  try {
    await apiFetch(
      `/api/admin/user-groups/${membersGroup.value.id}/members`,
      {
        method: "DELETE",
        body: JSON.stringify({ user_ids: [m.id] }),
      },
    );
    toastSuccess(`Đã xóa ${m.name} khỏi nhóm.`);
    await loadMembers(membersGroup.value.id);
    await loadGroups();
  } catch (e) {
    toastError(extractMessage(e) || "Xóa thành viên thất bại.");
  }
}

onMounted(() => {
  loadGroups();
});
</script>

<style scoped>
.user-groups-page {
  max-width: 1200px;
  margin: 0 auto;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.page-header-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.page-icon {
  width: 52px;
  height: 52px;
  background: linear-gradient(
    135deg,
    rgba(99, 102, 241, 0.1) 0%,
    rgba(139, 92, 246, 0.1) 100%
  );
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary);
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  color: var(--gray-900);
  letter-spacing: -0.02em;
}

.page-subtitle {
  margin: 4px 0 0;
  color: var(--gray-500);
  font-size: 14px;
}

/* Table Card */
.table-card {
  background: #fff;
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.table-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--gray-100);
}

.table-count {
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-600);
}

.table-wrapper {
  overflow-x: auto;
}

.table {
  min-width: 800px;
}

.table-row {
  transition: background var(--transition-fast);
}

.table-row:hover {
  background: var(--gray-50);
}

/* Cell Styles */
.id-badge {
  display: inline-flex;
  padding: 4px 10px;
  background: var(--gray-100);
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-600);
}

.group-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.group-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.group-name {
  font-weight: 600;
  color: var(--gray-800);
}

.group-desc {
  font-size: 13px;
  color: var(--gray-500);
  max-width: 300px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
}

.members-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
  background: rgba(99, 102, 241, 0.1);
  color: var(--primary, #6366f1);
  cursor: pointer;
  transition: background 0.15s;
}

.members-badge:hover {
  background: rgba(99, 102, 241, 0.2);
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 600;
  background: var(--gray-100);
  color: var(--gray-600);
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-success {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

/* Empty State */
.empty-cell {
  padding: 60px 24px !important;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: var(--gray-400);
}

.empty-state svg {
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 14px;
}

/* Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: var(--bg-card, #fff);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid var(--border, #e5e7eb);
}

.modal-title {
  font-size: 18px;
  font-weight: 700;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--border, #e5e7eb);
}

/* Form */
.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
  color: var(--text-primary, #1f2937);
}

.textarea {
  resize: vertical;
  min-height: 60px;
}

/* Color Picker */
.color-picker {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.color-option {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 3px solid transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.15s, border-color 0.15s, box-shadow 0.15s;
  padding: 0;
}

.color-option:hover {
  transform: scale(1.15);
}

.color-option-active {
  border-color: var(--gray-800, #1f2937);
  box-shadow: 0 0 0 2px #fff, 0 0 0 4px var(--gray-300, #d1d5db);
}

/* Toggle */
.toggle-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.toggle-input {
  display: none;
}

.toggle-switch {
  width: 40px;
  height: 22px;
  background: var(--gray-300, #d1d5db);
  border-radius: 11px;
  position: relative;
  transition: background 0.2s;
}

.toggle-switch::after {
  content: "";
  width: 18px;
  height: 18px;
  background: #fff;
  border-radius: 50%;
  position: absolute;
  top: 2px;
  left: 2px;
  transition: transform 0.2s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.toggle-input:checked + .toggle-switch {
  background: var(--primary, #6366f1);
}

.toggle-input:checked + .toggle-switch::after {
  transform: translateX(18px);
}

.toggle-text {
  font-size: 14px;
  color: var(--gray-600, #475569);
}

/* Members Modal */
.members-section {
  margin-top: 8px;
}

.members-section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 700;
  color: var(--gray-700, #334155);
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--gray-100, #f1f5f9);
}

.members-count-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 22px;
  height: 22px;
  padding: 0 6px;
  border-radius: 11px;
  background: var(--primary, #6366f1);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 1px;
  background: var(--gray-100, #f1f5f9);
  border-radius: 10px;
  overflow: hidden;
}

.member-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: #fff;
  transition: background 150ms;
}

.member-item:hover {
  background: var(--gray-50, #f8fafc);
}

.member-name {
  font-weight: 600;
  font-size: 14px;
  color: var(--gray-800);
}

.member-email {
  font-size: 12px;
  color: var(--gray-400);
}

.members-empty {
  text-align: center;
  padding: 24px;
  color: var(--gray-400, #94a3b8);
  font-size: 13px;
  background: var(--gray-50, #f8fafc);
  border-radius: 10px;
}

.btn-icon-danger {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--gray-400, #94a3b8);
  transition: background 0.15s, color 0.15s;
  flex-shrink: 0;
}

.btn-icon-danger:hover {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

/* Search Dropdown */
.search-select {
  position: relative;
}

.search-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: var(--bg-card, #fff);
  border: 1px solid var(--border, #e5e7eb);
  border-radius: 8px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  z-index: 10;
  max-height: 220px;
  overflow-y: auto;
  margin-top: 4px;
}

.search-dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  cursor: pointer;
  transition: background 0.15s;
}

.search-dropdown-item:hover {
  background: var(--gray-50, #f9fafb);
}

.user-avatar-sm {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary, #6366f1), #8b5cf6);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
}

/* Button overrides */
.btn-primary {
  background: var(--primary, #6366f1);
  color: #fff;
  border: none;
}

.btn-primary:hover {
  opacity: 0.9;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Spin Animation */
.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
}
</style>
