<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Người dùng</h1>
      <div class="row" style="gap: 8px">
        <button class="btn btn-secondary" @click="exportUsers">
          Xuất Excel
        </button>
        <button class="btn" @click="openCreate">Tạo mới</button>
      </div>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>

    <div class="card" style="margin-top: 16px">
      <div
        class="row"
        style="justify-content: space-between; align-items: center"
      >
        <div
          class="row"
          style="gap: 10px; align-items: center; flex-wrap: wrap"
        >
          <input
            v-model="search"
            class="input"
            placeholder="Tìm kiếm…"
            style="max-width: 260px"
          />
          <select v-model="role" class="input" style="width: 160px">
            <option value="">Tất cả role</option>
            <option value="student">Student</option>
            <option value="instructor">Instructor</option>
            <option value="admin">Admin</option>
          </select>
          <select v-model="active" class="input" style="width: 160px">
            <option value="">Tất cả trạng thái</option>
            <option value="true">Active</option>
            <option value="false">Inactive</option>
          </select>
          <select v-model="courseFilter" class="input" style="width: 200px">
            <option value="">Tất cả khóa học</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">
              {{ c.title }}
            </option>
          </select>
        </div>
        <button class="btn" :disabled="loading" @click="load">
          {{ loading ? "Đang tải…" : "Làm mới" }}
        </button>
      </div>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th style="width: 40px">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleAll"
              />
            </th>
            <th style="width: 70px">ID</th>
            <th>Người dùng</th>
            <th>Email</th>
            <th style="width: 100px">Khóa học</th>
            <th style="width: 120px">Doanh thu</th>
            <th style="width: 130px">Role</th>
            <th style="width: 120px">Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.id">
            <td>
              <input
                type="checkbox"
                :checked="selectedIds.includes(u.id)"
                @change="toggleSelect(u.id)"
              />
            </td>
            <td>{{ u.id }}</td>
            <td>
              <div style="font-weight: 600">{{ u.name }}</div>
              <div class="muted" style="font-size: 12px">
                {{ u.phone || "" }}
              </div>
            </td>
            <td>{{ u.email }}</td>
            <td>
              <span
                v-if="u.enrollments_count != null"
                class="badge"
                style="cursor: pointer"
                @click="viewUser(u)"
              >
                {{ u.enrollments_count }} khóa
              </span>
            </td>
            <td>
              <span
                v-if="u.total_revenue > 0"
                style="font-weight: 600; color: var(--success, #22c55e)"
              >
                {{ formatCurrency(u.total_revenue) }}
              </span>
              <span v-else class="muted">0</span>
            </td>
            <td>
              <span class="badge">{{ u.role }}</span>
            </td>
            <td>
              <span :class="u.is_active ? 'badge badge-success' : 'badge'">
                {{ u.is_active ? "Active" : "Inactive" }}
              </span>
            </td>
            <td style="text-align: right; white-space: nowrap">
              <button class="btn btn-secondary btn-sm" @click="viewUser(u)">
                Chi tiết
              </button>
              <button class="btn btn-secondary btn-sm" @click="openEdit(u)">
                Sửa
              </button>
              <button class="btn btn-danger btn-sm" @click="remove(u)">
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && users.length === 0">
            <td colspan="9" class="muted">Chưa có người dùng nào</td>
          </tr>
        </tbody>
      </table>

      <div
        v-if="selectedIds.length > 0"
        style="margin-top: 12px; display: flex; gap: 8px; align-items: center"
      >
        <span class="muted">Đã chọn {{ selectedIds.length }} người dùng</span>
        <button class="btn btn-secondary btn-sm" @click="exportSelected">
          Xuất {{ selectedIds.length }} đã chọn
        </button>
      </div>
    </div>

    <!-- User Detail Modal -->
    <div
      v-if="detailOpen"
      class="modal-backdrop"
      @click.self="detailOpen = false"
    >
      <div class="modal" style="max-width: 860px">
        <div class="modal-header">
          <div style="display: flex; align-items: center; gap: 14px">
            <div class="detail-avatar">
              <img
                v-if="detailUser?.avatar"
                :src="detailUser.avatar"
                class="detail-avatar-img"
              />
              <span v-else class="detail-avatar-placeholder">
                {{ (detailUser?.name || "?")[0].toUpperCase() }}
              </span>
            </div>
            <div>
              <div class="modal-title">{{ detailUser?.name }}</div>
              <div style="font-size: 13px; color: var(--gray-500, #64748b)">
                {{ detailUser?.email }}
                <span v-if="detailUser?.phone" style="margin-left: 8px"
                  >· {{ detailUser.phone }}</span
                >
              </div>
            </div>
          </div>
          <button class="btn btn-secondary btn-sm" @click="detailOpen = false">
            ✕
          </button>
        </div>

        <div class="modal-body">
          <div v-if="detailLoading" style="text-align: center; padding: 40px 0">
            <div style="font-size: 14px; color: var(--gray-400, #94a3b8)">
              Đang tải…
            </div>
          </div>
          <div v-else>
            <!-- Stats cards -->
            <div class="detail-stats">
              <div class="detail-stat-card">
                <div
                  class="detail-stat-value"
                  style="color: var(--primary, #6366f1)"
                >
                  {{ detailEnrollments.length }}
                </div>
                <div class="detail-stat-label">Khóa học</div>
              </div>
              <div class="detail-stat-card">
                <div
                  class="detail-stat-value"
                  style="color: var(--success, #22c55e)"
                >
                  {{ formatCurrency(detailRevenue) }}
                </div>
                <div class="detail-stat-label">Tổng doanh thu</div>
              </div>
              <div class="detail-stat-card">
                <div class="detail-stat-value">
                  {{ detailPayments.length }}
                </div>
                <div class="detail-stat-label">Giao dịch</div>
              </div>
              <div class="detail-stat-card">
                <div class="detail-stat-value">
                  {{
                    detailUser?.created_at
                      ? new Date(detailUser.created_at).toLocaleDateString(
                          "vi-VN",
                        )
                      : "—"
                  }}
                </div>
                <div class="detail-stat-label">Ngày tham gia</div>
              </div>
            </div>

            <!-- Enrolled courses -->
            <div class="detail-section">
              <div class="detail-section-header">
                <span>📚 Khóa học đã đăng ký</span>
                <span class="detail-count">{{ detailEnrollments.length }}</span>
              </div>
              <div v-if="detailEnrollments.length > 0" class="detail-list">
                <div
                  v-for="e in detailEnrollments"
                  :key="e.id"
                  class="detail-list-item"
                >
                  <div style="flex: 1; min-width: 0">
                    <div
                      style="
                        font-weight: 600;
                        font-size: 14px;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                      "
                    >
                      {{ e.course?.title || "Khóa học đã xóa" }}
                    </div>
                    <div
                      style="
                        font-size: 12px;
                        color: var(--gray-400, #94a3b8);
                        margin-top: 2px;
                      "
                    >
                      Đăng ký
                      {{
                        e.enrolled_at
                          ? new Date(e.enrolled_at).toLocaleDateString("vi-VN")
                          : "—"
                      }}
                    </div>
                  </div>
                  <span class="badge badge-success" style="font-size: 11px">{{
                    e.status
                  }}</span>
                </div>
              </div>
              <div v-else class="detail-empty">Chưa đăng ký khóa học nào</div>
            </div>

            <!-- Payment history -->
            <div class="detail-section">
              <div class="detail-section-header">
                <span>💳 Lịch sử thanh toán</span>
                <span class="detail-count">{{ detailPayments.length }}</span>
              </div>
              <div v-if="detailPayments.length > 0" class="detail-list">
                <div
                  v-for="p in detailPayments"
                  :key="p.id"
                  class="detail-list-item"
                >
                  <div
                    style="
                      display: flex;
                      align-items: center;
                      gap: 10px;
                      flex: 1;
                      min-width: 0;
                    "
                  >
                    <span class="detail-order-code">{{ p.order_code }}</span>
                    <span
                      style="
                        font-size: 13px;
                        color: var(--gray-600, #475569);
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                      "
                    >
                      {{ p.course?.title || "—" }}
                    </span>
                  </div>
                  <div
                    style="
                      display: flex;
                      align-items: center;
                      gap: 16px;
                      flex-shrink: 0;
                    "
                  >
                    <span
                      style="
                        font-weight: 700;
                        font-size: 14px;
                        color: var(--success, #22c55e);
                      "
                    >
                      {{ formatCurrency(p.amount) }}
                    </span>
                    <span
                      style="
                        font-size: 12px;
                        color: var(--gray-400, #94a3b8);
                        min-width: 80px;
                        text-align: right;
                      "
                    >
                      {{
                        p.paid_at
                          ? new Date(p.paid_at).toLocaleDateString("vi-VN")
                          : "—"
                      }}
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="detail-empty">Chưa có thanh toán nào</div>
            </div>
          </div>
        </div>

        <div class="modal-footer" v-if="!detailLoading">
          <div style="flex: 1"></div>
          <button
            class="btn btn-secondary btn-sm"
            @click="exportSingle(detailUser!.id)"
          >
            📥 Xuất Excel
          </button>
          <button
            class="btn btn-sm"
            @click="
              detailOpen = false;
              openEdit(detailUser!);
            "
          >
            ✏️ Chỉnh sửa
          </button>
        </div>
      </div>
    </div>

    <div v-if="modalOpen" class="modal-backdrop" @click.self="closeModal">
      <div class="modal" style="max-width: 720px">
        <div
          class="row"
          style="justify-content: space-between; align-items: center"
        >
          <h2 class="h2" style="margin: 0">
            {{ editingId ? `Sửa user #${editingId}` : "Tạo user" }}
          </h2>
          <button class="btn btn-secondary" @click="closeModal">Đóng</button>
        </div>

        <form class="form" style="margin-top: 12px" @submit.prevent="save">
          <div class="grid">
            <label class="label">
              <span>Họ tên</span>
              <input v-model="form.name" class="input" required />
            </label>
            <label class="label">
              <span>Email</span>
              <input v-model="form.email" class="input" type="email" required />
            </label>
          </div>

          <div class="grid">
            <label class="label">
              <span>Role</span>
              <select v-model="form.role" class="input">
                <option value="student">Student</option>
                <option value="instructor">Instructor</option>
                <option value="admin">Admin</option>
              </select>
            </label>
            <label class="label">
              <span>Trạng thái</span>
              <select v-model="form.is_active" class="input">
                <option :value="true">Active</option>
                <option :value="false">Inactive</option>
              </select>
            </label>
          </div>

          <label class="label">
            <span>Avatar URL</span>
            <div style="display: flex; gap: 8px">
              <input
                v-model="form.avatar"
                class="input"
                placeholder="https://..."
              />
              <button
                type="button"
                class="btn btn-secondary"
                style="white-space: nowrap"
                @click="openMediaPicker = true"
              >
                Chọn ảnh
              </button>
            </div>
            <img
              v-if="form.avatar"
              :src="form.avatar"
              style="
                max-width: 48px;
                max-height: 48px;
                border-radius: 50%;
                margin-top: 6px;
              "
            />
          </label>

          <label class="label">
            <span>Bio</span>
            <textarea v-model="form.bio" class="textarea" rows="3" />
          </label>

          <div class="grid">
            <label class="label">
              <span>Phone</span>
              <input v-model="form.phone" class="input" />
            </label>
            <label class="label">
              <span>Date of birth</span>
              <input v-model="form.date_of_birth" class="input" type="date" />
            </label>
          </div>

          <label class="label">
            <span>Mật khẩu mới</span>
            <input
              v-model="form.password"
              class="input"
              type="password"
              placeholder="Để trống nếu không đổi"
            />
          </label>

          <button class="btn" :disabled="saving" type="submit">
            {{ saving ? "Đang lưu…" : "Lưu" }}
          </button>
        </form>
      </div>
    </div>

    <MediaPickerModal
      :open="openMediaPicker"
      @select="onMediaSelect"
      @close="openMediaPicker = false"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch } from "vue";
import { apiFetch, extractMessage, HttpError, getToken } from "../lib/api";
import { useToast } from "../lib/toast";
import MediaPickerModal from "../components/MediaPickerModal.vue";
import type { AdminUser } from "../lib/types";

const users = ref<any[]>([]);
const courses = ref<{ id: number; title: string }[]>([]);
const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess, error: toastError } = useToast();

const search = ref("");
const role = ref("");
const active = ref("");
const courseFilter = ref("");

const modalOpen = ref(false);
const openMediaPicker = ref(false);
const editingId = ref<number | null>(null);
const selectedIds = ref<number[]>([]);

// Detail modal
const detailOpen = ref(false);
const detailLoading = ref(false);
const detailUser = ref<any>(null);
const detailEnrollments = ref<any[]>([]);
const detailPayments = ref<any[]>([]);
const detailRevenue = ref(0);

const allSelected = computed(
  () =>
    users.value.length > 0 &&
    users.value.every((u) => selectedIds.value.includes(u.id)),
);

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = users.value.map((u) => u.id);
  }
}

function toggleSelect(id: number) {
  const idx = selectedIds.value.indexOf(id);
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1);
  } else {
    selectedIds.value.push(id);
  }
}

function formatCurrency(val: number | string) {
  const num = Number(val) || 0;
  return new Intl.NumberFormat("vi-VN").format(num) + "đ";
}

function onMediaSelect(asset: any) {
  const url =
    asset?.url || asset?.path || (typeof asset === "string" ? asset : "");
  if (url) form.value.avatar = url;
  openMediaPicker.value = false;
}

const form = ref({
  name: "",
  email: "",
  role: "student" as AdminUser["role"],
  is_active: true,
  avatar: "",
  bio: "",
  phone: "",
  date_of_birth: "",
  password: "",
});

function resetForm() {
  form.value = {
    name: "",
    email: "",
    role: "student",
    is_active: true,
    avatar: "",
    bio: "",
    phone: "",
    date_of_birth: "",
    password: "",
  };
}

function openCreate() {
  editingId.value = null;
  resetForm();
  modalOpen.value = true;
}

function openEdit(u: AdminUser) {
  editingId.value = u.id;
  form.value.name = u.name;
  form.value.email = u.email;
  form.value.role = u.role;
  form.value.is_active = u.is_active ?? true;
  form.value.avatar = u.avatar || "";
  form.value.bio = u.bio || "";
  form.value.phone = u.phone || "";
  form.value.date_of_birth = u.date_of_birth || "";
  form.value.password = "";
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (search.value.trim()) qs.set("search", search.value.trim());
    if (role.value) qs.set("role", role.value);
    if (active.value !== "") qs.set("active", active.value);
    if (courseFilter.value) qs.set("course_id", courseFilter.value);
    const res = await apiFetch<{ data: any[] }>(
      `/api/admin/users?${qs.toString()}`,
    );
    users.value = res.data || [];
    selectedIds.value = [];
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

async function loadCourses() {
  try {
    const res = await apiFetch<{ data: any[] }>("/api/admin/courses");
    courses.value = (res.data || []).map((c: any) => ({
      id: c.id,
      title: c.title,
    }));
  } catch {}
}

async function viewUser(u: any) {
  detailUser.value = u;
  detailOpen.value = true;
  detailLoading.value = true;
  try {
    const res = await apiFetch<any>(`/api/admin/users/${u.id}`);
    detailUser.value = res.user;
    detailEnrollments.value = res.enrollments || [];
    detailPayments.value = res.payments || [];
    detailRevenue.value = Number(res.total_revenue) || 0;
  } catch (e) {
    toastError(extractMessage(e) || "Không thể tải thông tin");
  } finally {
    detailLoading.value = false;
  }
}

function buildExportQs() {
  const qs = new URLSearchParams();
  if (search.value.trim()) qs.set("search", search.value.trim());
  if (role.value) qs.set("role", role.value);
  if (active.value !== "") qs.set("active", active.value);
  if (courseFilter.value) qs.set("course_id", courseFilter.value);
  return qs;
}

async function exportUsers() {
  try {
    const qs = buildExportQs();
    const blob = await fetchCsvBlob(`/api/admin/users/export?${qs.toString()}`);
    downloadBlob(blob, `users_${Date.now()}.csv`);
    toastSuccess("Đã xuất danh sách người dùng.");
  } catch (e) {
    toastError(extractMessage(e) || "Xuất Excel thất bại.");
  }
}

async function exportSelected() {
  if (selectedIds.value.length === 0) return;
  try {
    const qs = buildExportQs();
    qs.set("ids", selectedIds.value.join(","));
    const blob = await fetchCsvBlob(`/api/admin/users/export?${qs.toString()}`);
    downloadBlob(blob, `users_selected_${Date.now()}.csv`);
    toastSuccess(`Đã xuất ${selectedIds.value.length} người dùng.`);
  } catch (e) {
    toastError(extractMessage(e) || "Xuất Excel thất bại.");
  }
}

async function exportSingle(userId: number) {
  try {
    const qs = new URLSearchParams({ ids: String(userId) });
    const blob = await fetchCsvBlob(`/api/admin/users/export?${qs.toString()}`);
    downloadBlob(blob, `user_${userId}_${Date.now()}.csv`);
    toastSuccess("Đã xuất thông tin người dùng.");
  } catch (e) {
    toastError(extractMessage(e) || "Xuất Excel thất bại.");
  }
}

async function fetchCsvBlob(url: string): Promise<Blob> {
  const token = getToken();
  const headers: Record<string, string> = {};
  if (token) headers["Authorization"] = `Bearer ${token}`;
  const res = await fetch(url, { headers });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.blob();
}

function downloadBlob(blob: Blob | any, filename: string) {
  // If blob is not a Blob, convert it
  const b =
    blob instanceof Blob ? blob : new Blob([blob], { type: "text/csv" });
  const url = URL.createObjectURL(b);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

async function save() {
  saving.value = true;
  error.value = null;
  try {
    const payload: Record<string, any> = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
      is_active: form.value.is_active,
      avatar: form.value.avatar || null,
      bio: form.value.bio || null,
      phone: form.value.phone || null,
      date_of_birth: form.value.date_of_birth || null,
    };
    if (form.value.password) payload.password = form.value.password;

    if (!editingId.value) {
      await apiFetch("/api/admin/users", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ...payload,
          password: payload.password || "changeme123",
        }),
      });
    } else {
      await apiFetch(`/api/admin/users/${editingId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    }

    closeModal();
    toastSuccess("Đã lưu người dùng thành công.");
    await load();
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  } finally {
    saving.value = false;
  }
}

async function remove(u: AdminUser) {
  if (!confirm(`Xóa user ${u.name}?`)) return;
  try {
    await apiFetch(`/api/admin/users/${u.id}`, { method: "DELETE" });
    await load();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

watch([search, role, active, courseFilter], () => {
  // user can click refresh if needed
});

onMounted(() => {
  load();
  loadCourses();
});
</script>

<style scoped>
.detail-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--primary, #6366f1), #a78bfa);
  display: flex;
  align-items: center;
  justify-content: center;
}
.detail-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.detail-avatar-placeholder {
  font-size: 20px;
  font-weight: 700;
  color: #fff;
}

.detail-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 24px;
}
.detail-stat-card {
  background: var(--gray-50, #f8fafc);
  border: 1px solid var(--gray-100, #f1f5f9);
  border-radius: 12px;
  padding: 16px;
  text-align: center;
}
.detail-stat-value {
  font-size: 20px;
  font-weight: 800;
  color: var(--gray-900, #0f172a);
  line-height: 1.2;
}
.detail-stat-label {
  font-size: 12px;
  color: var(--gray-400, #94a3b8);
  margin-top: 4px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.detail-section {
  margin-bottom: 20px;
}
.detail-section-header {
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
.detail-count {
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

.detail-list {
  display: flex;
  flex-direction: column;
  gap: 1px;
  background: var(--gray-100, #f1f5f9);
  border-radius: 10px;
  overflow: hidden;
}
.detail-list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  background: #fff;
  transition: background 150ms;
}
.detail-list-item:hover {
  background: var(--gray-50, #f8fafc);
}

.detail-order-code {
  font-family: monospace;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  background: var(--gray-100, #f1f5f9);
  color: var(--gray-600, #475569);
  flex-shrink: 0;
}

.detail-empty {
  text-align: center;
  padding: 24px;
  color: var(--gray-400, #94a3b8);
  font-size: 13px;
  background: var(--gray-50, #f8fafc);
  border-radius: 10px;
}

@media (max-width: 640px) {
  .detail-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
