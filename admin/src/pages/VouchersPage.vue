<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Mã giảm giá</h1>
      <button class="btn" @click="openCreate">Tạo mới</button>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>

    <!-- Stats -->
    <div class="stats-grid" style="margin-top: 16px">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">Tổng voucher</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.active }}</div>
        <div class="stat-label">Đang hoạt động</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.inactive }}</div>
        <div class="stat-label">Đã vô hiệu</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.total_used }}</div>
        <div class="stat-label">Lượt sử dụng</div>
      </div>
    </div>

    <div class="card" style="margin-top: 16px">
      <div
        class="row"
        style="justify-content: space-between; align-items: center; gap: 12px"
      >
        <input
          v-model="search"
          class="input"
          placeholder="Tìm kiếm mã voucher…"
          style="max-width: 320px"
          @keyup.enter="load"
        />
        <div class="row" style="gap: 8px">
          <select v-model="statusFilter" class="input" style="width: auto">
            <option value="">Tất cả trạng thái</option>
            <option value="active">Hoạt động</option>
            <option value="inactive">Vô hiệu</option>
            <option value="expired">Hết hạn</option>
          </select>
          <button class="btn" :disabled="loading" @click="load">
            {{ loading ? "Đang tải…" : "Tìm" }}
          </button>
        </div>
      </div>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th>Mã</th>
            <th>Tên</th>
            <th>Loại giảm</th>
            <th>Giá trị</th>
            <th>Áp dụng</th>
            <th>Sử dụng</th>
            <th>Thời hạn</th>
            <th>Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="v in vouchers" :key="v.id">
            <td>
              <code class="voucher-code">{{ v.code }}</code>
            </td>
            <td>{{ v.name }}</td>
            <td>
              <span
                :class="[
                  'badge',
                  v.discount_type === 'percent'
                    ? 'badge-info'
                    : 'badge-success',
                ]"
              >
                {{ v.discount_type === "percent" ? "Phần trăm" : "Cố định" }}
              </span>
            </td>
            <td>
              {{
                v.discount_type === "percent"
                  ? `${v.discount_value}%`
                  : formatCurrency(v.discount_value)
              }}
              <span
                v-if="v.discount_type === 'percent' && v.max_discount"
                class="muted"
              >
                (tối đa {{ formatCurrency(v.max_discount) }})
              </span>
            </td>
            <td>
              <span v-if="v.applicable_type === 'specific' && v.courses?.length" class="course-badges">
                <span v-for="c in v.courses" :key="c.id" class="badge badge-course" :title="c.title">
                  {{ c.title.length > 20 ? c.title.slice(0, 20) + '…' : c.title }}
                </span>
              </span>
              <span v-else class="badge badge-info">Tất cả</span>
            </td>
            <td>
              {{ v.used_count }}
              <span v-if="v.usage_limit">/ {{ v.usage_limit }}</span>
              <span v-else class="muted">/ ∞</span>
            </td>
            <td>
              <span v-if="v.valid_from || v.valid_until" class="date-range">
                {{ formatDate(v.valid_from) }} - {{ formatDate(v.valid_until) }}
              </span>
              <span v-else class="muted">Không giới hạn</span>
            </td>
            <td>
              <span :class="['badge', getStatusClass(v.status)]">
                {{ getStatusLabel(v.status) }}
              </span>
            </td>
            <td style="text-align: right">
              <button class="btn btn-secondary btn-sm" @click="openEdit(v)">
                Sửa
              </button>
              <button
                class="btn btn-danger btn-sm"
                style="margin-left: 4px"
                @click="confirmDelete(v)"
              >
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && vouchers.length === 0">
            <td colspan="9" class="muted">Chưa có mã giảm giá nào</td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.lastPage > 1" class="pagination">
        <button
          class="btn btn-sm"
          :disabled="pagination.currentPage === 1"
          @click="goToPage(pagination.currentPage - 1)"
        >
          ‹
        </button>
        <span class="pagination-info">
          Trang {{ pagination.currentPage }} / {{ pagination.lastPage }}
        </span>
        <button
          class="btn btn-sm"
          :disabled="pagination.currentPage === pagination.lastPage"
          @click="goToPage(pagination.currentPage + 1)"
        >
          ›
        </button>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal" style="max-width: 600px">
        <div class="modal-header">
          <h2>{{ editingVoucher ? "Sửa voucher" : "Tạo voucher mới" }}</h2>
          <button class="btn-close" @click="closeModal">×</button>
        </div>

        <form @submit.prevent="saveVoucher">
          <div class="modal-body">
            <div class="form-row">
              <div class="form-group" style="flex: 1">
                <label class="label">Mã voucher</label>
                <input
                  v-model="form.code"
                  class="input"
                  placeholder="Để trống sẽ tự tạo"
                  :readonly="!!editingVoucher"
                  style="text-transform: uppercase"
                />
                <small class="muted">Chỉ chữ và số, tối đa 50 ký tự</small>
              </div>
              <div class="form-group" style="flex: 1">
                <label class="label">Trạng thái</label>
                <select v-model="form.status" class="input">
                  <option value="active">Hoạt động</option>
                  <option value="inactive">Vô hiệu</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="label">Tên voucher *</label>
              <input v-model="form.name" class="input" required />
            </div>

            <div class="form-group">
              <label class="label">Mô tả</label>
              <textarea
                v-model="form.description"
                class="input"
                rows="2"
              ></textarea>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1">
                <label class="label">Loại giảm giá *</label>
                <select v-model="form.discount_type" class="input" required>
                  <option value="fixed">Số tiền cố định (VNĐ)</option>
                  <option value="percent">Phần trăm (%)</option>
                </select>
              </div>
              <div class="form-group" style="flex: 1">
                <label class="label">Giá trị giảm *</label>
                <input
                  v-model.number="form.discount_value"
                  type="number"
                  class="input"
                  min="0"
                  :max="form.discount_type === 'percent' ? 100 : undefined"
                  required
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1">
                <label class="label">Đơn tối thiểu (VNĐ)</label>
                <input
                  v-model.number="form.min_order_amount"
                  type="number"
                  class="input"
                  min="0"
                />
              </div>
              <div
                v-if="form.discount_type === 'percent'"
                class="form-group"
                style="flex: 1"
              >
                <label class="label">Giảm tối đa (VNĐ)</label>
                <input
                  v-model.number="form.max_discount"
                  type="number"
                  class="input"
                  min="0"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1">
                <label class="label">Giới hạn sử dụng</label>
                <input
                  v-model.number="form.usage_limit"
                  type="number"
                  class="input"
                  min="1"
                  placeholder="Không giới hạn"
                />
              </div>
              <div class="form-group" style="flex: 1">
                <label class="label">Lượt/người dùng</label>
                <input
                  v-model.number="form.usage_per_user"
                  type="number"
                  class="input"
                  min="1"
                  placeholder="Không giới hạn"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group" style="flex: 1">
                <label class="label">Bắt đầu</label>
                <input
                  v-model="form.valid_from"
                  type="datetime-local"
                  class="input"
                />
              </div>
              <div class="form-group" style="flex: 1">
                <label class="label">Kết thúc</label>
                <input
                  v-model="form.valid_until"
                  type="datetime-local"
                  class="input"
                />
              </div>
            </div>

            <!-- Applicable Type -->
            <div class="form-group">
              <label class="label">Phạm vi áp dụng</label>
              <div class="radio-group">
                <label class="radio-label">
                  <input type="radio" v-model="form.applicable_type" value="all" />
                  <span>Tất cả khóa học</span>
                </label>
                <label class="radio-label">
                  <input type="radio" v-model="form.applicable_type" value="specific" />
                  <span>Khóa học cụ thể</span>
                </label>
              </div>
            </div>

            <!-- Course selector -->
            <div v-if="form.applicable_type === 'specific'" class="form-group">
              <label class="label">Chọn khóa học áp dụng</label>
              <div class="course-search-box">
                <input
                  v-model="courseSearch"
                  class="input"
                  placeholder="Tìm khóa học…"
                  style="margin-bottom: 8px"
                />
                <div class="course-list">
                  <label
                    v-for="c in filteredCourses"
                    :key="c.id"
                    class="course-option"
                    :class="{ selected: form.course_ids.includes(c.id) }"
                  >
                    <input
                      type="checkbox"
                      :value="c.id"
                      v-model="form.course_ids"
                    />
                    <span>{{ c.title }}</span>
                  </label>
                  <div v-if="filteredCourses.length === 0" class="muted" style="padding: 8px; text-align: center">
                    Không tìm thấy khóa học
                  </div>
                </div>
              </div>
              <div v-if="form.course_ids.length" class="selected-courses">
                <span
                  v-for="cid in form.course_ids"
                  :key="cid"
                  class="badge badge-course"
                >
                  {{ getCourseTitle(cid) }}
                  <button type="button" class="badge-remove" @click="form.course_ids = form.course_ids.filter(x => x !== cid)">×</button>
                </span>
              </div>
            </div>

            <div v-if="formError" class="alert" style="margin-top: 12px">
              {{ formError }}
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">
              Hủy
            </button>
            <button type="submit" class="btn" :disabled="saving">
              {{
                saving ? "Đang lưu…" : editingVoucher ? "Cập nhật" : "Tạo mới"
              }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <div
      v-if="deletingVoucher"
      class="modal-overlay"
      @click.self="deletingVoucher = null"
    >
      <div class="modal" style="max-width: 400px">
        <div class="modal-header">
          <h2>Xác nhận xóa</h2>
          <button class="btn-close" @click="deletingVoucher = null">×</button>
        </div>
        <div class="modal-body">
          <p>
            Bạn có chắc muốn xóa voucher
            <strong>{{ deletingVoucher.code }}</strong
            >?
          </p>
          <p v-if="deletingVoucher.used_count > 0" class="muted">
            Voucher này đã được sử dụng {{ deletingVoucher.used_count }} lần nên
            sẽ chỉ bị vô hiệu hóa.
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="deletingVoucher = null">
            Hủy
          </button>
          <button class="btn btn-danger" :disabled="deleting" @click="doDelete">
            {{ deleting ? "Đang xóa…" : "Xóa" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import type { Voucher } from "../lib/types";

const vouchers = ref<Voucher[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const search = ref("");
const statusFilter = ref("");

const stats = ref({
  total: 0,
  active: 0,
  inactive: 0,
  total_used: 0,
});

const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  total: 0,
});

const showModal = ref(false);
const editingVoucher = ref<Voucher | null>(null);
const saving = ref(false);
const formError = ref<string | null>(null);

const deletingVoucher = ref<Voucher | null>(null);
const deleting = ref(false);

const allCourses = ref<{ id: number; title: string }[]>([]);
const courseSearch = ref("");

const form = ref({
  code: "",
  name: "",
  description: "",
  discount_type: "fixed" as "fixed" | "percent",
  discount_value: 0,
  min_order_amount: 0,
  max_discount: null as number | null,
  usage_limit: null as number | null,
  usage_per_user: null as number | null,
  valid_from: "",
  valid_until: "",
  status: "active",
  applicable_type: "all" as "all" | "specific",
  course_ids: [] as number[],
});


const filteredCourses = computed(() => {
  const q = courseSearch.value.toLowerCase().trim();
  if (!q) return allCourses.value;
  return allCourses.value.filter((c) =>
    c.title.toLowerCase().includes(q)
  );
});

function getCourseTitle(id: number): string {
  return allCourses.value.find((c) => c.id === id)?.title || `#${id}`;
}

async function loadCourses() {
  try {
    const res = await apiFetch<{ data: { id: number; title: string }[] }>(
      "/api/admin/courses/options"
    );
    allCourses.value = res.data;
  } catch {
    // ignore
  }
}

function formatCurrency(value: string | number): string {
  const num = typeof value === "string" ? parseFloat(value) : value;
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(num);
}

function formatDate(dateStr: string | null | undefined): string {
  if (!dateStr) return "—";
  const d = new Date(dateStr);
  return d.toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

function getStatusClass(status: string): string {
  switch (status) {
    case "active":
      return "badge-success";
    case "inactive":
      return "badge-warning";
    case "expired":
      return "badge-danger";
    default:
      return "";
  }
}

function getStatusLabel(status: string): string {
  switch (status) {
    case "active":
      return "Hoạt động";
    case "inactive":
      return "Vô hiệu";
    case "expired":
      return "Hết hạn";
    default:
      return status;
  }
}

async function loadStats() {
  try {
    const res = await apiFetch<{
      total: number;
      active: number;
      inactive: number;
      total_used: number;
    }>("/api/admin/vouchers/stats");
    stats.value = res;
  } catch {
    // ignore
  }
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (search.value.trim()) qs.set("search", search.value.trim());
    if (statusFilter.value) qs.set("status", statusFilter.value);
    qs.set("page", String(pagination.value.currentPage));

    const res = await apiFetch<{
      data: Voucher[];
      current_page: number;
      last_page: number;
      total: number;
    }>(`/api/admin/vouchers?${qs.toString()}`);

    vouchers.value = res.data;
    pagination.value = {
      currentPage: res.current_page,
      lastPage: res.last_page,
      total: res.total,
    };
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

function goToPage(page: number) {
  pagination.value.currentPage = page;
  load();
}

function resetForm() {
  form.value = {
    code: "",
    name: "",
    description: "",
    discount_type: "fixed",
    discount_value: 0,
    min_order_amount: 0,
    max_discount: null,
    usage_limit: null,
    usage_per_user: null,
    valid_from: "",
    valid_until: "",
    status: "active",
    applicable_type: "all",
    course_ids: [],
  };
  courseSearch.value = "";
  formError.value = null;
}

function openCreate() {
  editingVoucher.value = null;
  resetForm();
  showModal.value = true;
}

function openEdit(voucher: Voucher) {
  editingVoucher.value = voucher;
  form.value = {
    code: voucher.code,
    name: voucher.name,
    description: voucher.description || "",
    discount_type: voucher.discount_type,
    discount_value: Number(voucher.discount_value),
    min_order_amount: Number(voucher.min_order_amount) || 0,
    max_discount: voucher.max_discount ? Number(voucher.max_discount) : null,
    usage_limit: voucher.usage_limit || null,
    usage_per_user: voucher.usage_per_user || null,
    valid_from: voucher.valid_from ? voucher.valid_from.slice(0, 16) : "",
    valid_until: voucher.valid_until ? voucher.valid_until.slice(0, 16) : "",
    status: voucher.status,
    applicable_type: voucher.applicable_type || "all",
    course_ids: voucher.courses?.map((c) => c.id) || [],
  };
  courseSearch.value = "";
  formError.value = null;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingVoucher.value = null;
  resetForm();
}

async function saveVoucher() {
  saving.value = true;
  formError.value = null;

  try {
    const payload = {
      ...form.value,
      code: form.value.code.trim().toUpperCase() || undefined,
      valid_from: form.value.valid_from || null,
      valid_until: form.value.valid_until || null,
      course_ids: form.value.applicable_type === 'specific' ? form.value.course_ids : [],
    };

    if (editingVoucher.value) {
      await apiFetch(`/api/admin/vouchers/${editingVoucher.value.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } else {
      await apiFetch("/api/admin/vouchers", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    }

    closeModal();
    load();
    loadStats();
  } catch (e) {
    formError.value = extractMessage(e);
  } finally {
    saving.value = false;
  }
}

function confirmDelete(voucher: Voucher) {
  deletingVoucher.value = voucher;
}

async function doDelete() {
  if (!deletingVoucher.value) return;
  deleting.value = true;

  try {
    await apiFetch(`/api/admin/vouchers/${deletingVoucher.value.id}`, {
      method: "DELETE",
    });
    deletingVoucher.value = null;
    load();
    loadStats();
  } catch (e) {
    error.value = extractMessage(e);
  } finally {
    deleting.value = false;
  }
}

watch(statusFilter, () => {
  pagination.value.currentPage = 1;
  load();
});

onMounted(() => {
  load();
  loadStats();
  loadCourses();
});
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
}

.stat-card {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: var(--primary, #6366f1);
}

.stat-label {
  font-size: 13px;
  color: var(--muted, #6b7280);
  margin-top: 4px;
}

.voucher-code {
  background: var(--code-bg, #f3f4f6);
  padding: 2px 8px;
  border-radius: 4px;
  font-family: monospace;
  font-weight: 600;
}

.badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge-success {
  background: #dcfce7;
  color: #16a34a;
}

.badge-warning {
  background: #fef3c7;
  color: #d97706;
}

.badge-danger {
  background: #fee2e2;
  color: #dc2626;
}

.badge-info {
  background: #dbeafe;
  color: #2563eb;
}

.date-range {
  font-size: 13px;
}

.form-row {
  display: flex;
  gap: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 13px;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 16px;
}

.pagination-info {
  font-size: 14px;
  color: var(--muted, #6b7280);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: var(--card-bg, #fff);
  border-radius: 12px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: var(--muted, #6b7280);
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  padding: 16px 20px;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

/* ─── Course targeting ─── */
.radio-group {
  display: flex;
  gap: 16px;
  margin-top: 4px;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  font-size: 14px;
}

.course-search-box {
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  padding: 8px;
  background: var(--card-bg, #fff);
}

.course-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 6px;
}

.course-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  cursor: pointer;
  font-size: 13px;
  border-bottom: 1px solid var(--border-color, #f3f4f6);
  transition: background 0.15s;
}

.course-option:last-child {
  border-bottom: none;
}

.course-option:hover {
  background: #f0f9ff;
}

.course-option.selected {
  background: #eff6ff;
  font-weight: 500;
}

.selected-courses {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.badge-course {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge-remove {
  background: none;
  border: none;
  color: #dc2626;
  cursor: pointer;
  font-size: 14px;
  padding: 0 2px;
  line-height: 1;
}

.badge-remove:hover {
  color: #991b1b;
}

.course-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  max-width: 200px;
}
</style>
