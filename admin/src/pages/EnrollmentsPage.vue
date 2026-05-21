<template>
  <div class="enrollments-page">
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
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
            />
          </svg>
        </div>
        <div>
          <h1 class="page-title">Đơn đăng ký học</h1>
          <p class="page-subtitle">
            Quản lý các đơn đăng ký và thanh toán từ học viên
          </p>
        </div>
      </div>
      <div style="display: flex; gap: 8px">
        <button class="btn" :disabled="loading" @click="loadEnrollments">
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
        <button class="btn btn-primary" @click="openManualEnrollModal">
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
          Thêm thủ công
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

    <!-- Filters Card -->
    <div class="filter-card">
      <div class="filter-grid">
        <div class="filter-item filter-search">
          <label class="filter-label">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            Tìm kiếm
          </label>
          <input
            v-model="search"
            class="input"
            placeholder="Tìm theo tên hoặc email..."
            @keyup.enter="loadEnrollments"
          />
        </div>

        <div class="filter-item">
          <label class="filter-label">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
              />
            </svg>
            Khóa học
          </label>
          <select v-model.number="selectedCourseId" class="input">
            <option :value="0">Tất cả khóa học</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">
              {{ c.title }}
            </option>
          </select>
        </div>

        <div class="filter-item">
          <label class="filter-label">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
              <line x1="1" y1="10" x2="23" y2="10" />
            </svg>
            Thanh toán
          </label>
          <select v-model="paymentStatus" class="input">
            <option value="all">Tất cả</option>
            <option value="paid">Đã thanh toán</option>
            <option value="pending">Chưa thanh toán</option>
            <option value="free">Miễn phí</option>
          </select>
        </div>

        <div class="filter-item">
          <label class="filter-label">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            Trạng thái
          </label>
          <select v-model="enrollmentStatus" class="input">
            <option value="all">Tất cả</option>
            <option value="pending">Chờ thanh toán</option>
            <option value="active">Đang học</option>
            <option value="completed">Hoàn thành</option>
          </select>
        </div>

        <div class="filter-actions">
          <button
            class="btn btn-secondary"
            :disabled="loading"
            @click="loadEnrollments"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
            </svg>
            Áp dụng
          </button>
          <button class="btn btn-ghost" @click="resetFilters">
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
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-info">
          <span class="table-count">{{ enrollments.length }} đơn đăng ký</span>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th style="width: 70px">ID</th>
              <th style="width: 220px">Học viên</th>
              <th style="width: 280px">Khóa học</th>
              <th style="width: 140px">Thời gian</th>
              <th style="width: 130px">Số tiền</th>
              <th style="width: 130px">Thanh toán</th>
              <th>Mã đơn / SePay</th>
              <th style="width: 130px">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in enrollments" :key="e.id" class="table-row">
              <td>
                <span class="id-badge">#{{ e.id }}</span>
              </td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar">
                    {{ getUserInitial(e.user?.name) }}
                  </div>
                  <div class="user-info">
                    <div class="user-name">{{ e.user?.name || "--" }}</div>
                    <div class="user-email">{{ e.user?.email || "" }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="course-cell">
                  <div class="course-name">{{ e.course?.title || "--" }}</div>
                  <div class="course-meta">
                    ID: {{ e.course?.id || "--" }} ·
                    {{ formatCurrency(e.course?.price) }}
                  </div>
                </div>
              </td>
              <td>
                <div class="time-cell">
                  {{ formatDate(e.enrolled_at || e.created_at) }}
                </div>
              </td>
              <td>
                <div class="amount-cell">
                  <span class="amount-main">{{
                    formatCurrency(e.payment?.amount ?? e.amount_paid)
                  }}</span>
                  <span v-if="e.payment?.transfer_amount" class="amount-sub">
                    Nhận: {{ formatCurrency(e.payment.transfer_amount) }}
                  </span>
                </div>
              </td>
              <td>
                <span :class="['status-badge', paymentBadgeClass(e)]">
                  <span class="status-dot"></span>
                  {{ paymentLabel(e) }}
                </span>
              </td>
              <td>
                <div class="order-cell">
                  <div class="order-code">
                    {{ e.payment?.order_code || "--" }}
                  </div>
                  <div v-if="e.payment?.sepay_txn_id" class="sepay-id">
                    <svg
                      width="12"
                      height="12"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <path
                        d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"
                      />
                      <path d="M1 10h22" />
                    </svg>
                    {{ e.payment.sepay_txn_id }}
                  </div>
                </div>
              </td>
              <td>
                <span :class="['status-badge', enrollmentBadgeClass(e.status)]">
                  <span class="status-dot"></span>
                  {{ enrollmentLabel(e.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="!loading && enrollments.length === 0">
              <td colspan="8" class="empty-cell">
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
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                  <p>Chưa có đơn đăng ký nào</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Manual Enrollment Modal -->
    <div
      v-if="showManualModal"
      class="modal-overlay"
      @click.self="closeManualEnrollModal"
    >
      <div class="modal" style="width: min(520px, 95vw)">
        <div class="modal-header">
          <div class="modal-title">Thêm học viên thủ công</div>
          <button
            class="btn btn-secondary btn-sm"
            @click="closeManualEnrollModal"
          >
            Đóng
          </button>
        </div>
        <div class="modal-body">
          <div
            v-if="manualError"
            class="alert alert-danger"
            style="margin-bottom: 16px"
          >
            {{ manualError }}
          </div>
          <div
            v-if="manualSuccess"
            class="alert alert-success"
            style="margin-bottom: 16px"
          >
            {{ manualSuccess }}
          </div>

          <!-- User Search -->
          <div class="form-group">
            <label class="form-label">Học viên *</label>
            <div class="search-select">
              <input
                v-model="manualUserSearch"
                class="input"
                placeholder="Tìm theo tên hoặc email..."
                @input="debouncedSearchUsers"
              />
              <div
                v-if="manualUserResults.length > 0 && !manualSelectedUser"
                class="search-dropdown"
              >
                <div
                  v-for="u in manualUserResults"
                  :key="u.id"
                  class="search-dropdown-item"
                  @click="selectUser(u)"
                >
                  <div class="user-avatar-sm">
                    {{ u.name?.charAt(0)?.toUpperCase() || "?" }}
                  </div>
                  <div>
                    <div style="font-weight: 500">{{ u.name }}</div>
                    <div style="font-size: 12px; color: var(--text-secondary)">
                      {{ u.email }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="manualSelectedUser" class="selected-chip">
              <div class="user-avatar-sm">
                {{ manualSelectedUser.name?.charAt(0)?.toUpperCase() || "?" }}
              </div>
              <span
                >{{ manualSelectedUser.name }} ({{
                  manualSelectedUser.email
                }})</span
              >
              <button
                class="chip-remove"
                @click="
                  manualSelectedUser = null;
                  manualUserSearch = '';
                "
              >
                ×
              </button>
            </div>
          </div>

          <!-- Course Select -->
          <div class="form-group">
            <label class="form-label">Khóa học *</label>
            <select v-model.number="manualCourseId" class="input" @change="onManualCourseChange">
              <option :value="0" disabled>-- Chọn khóa học --</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">
                {{ c.title }}
              </option>
            </select>
          </div>

          <!-- Duration Tier Select -->
          <div class="form-group" v-if="manualCourseTiers.length > 0">
            <label class="form-label">Gói thời gian</label>
            <select v-model.number="manualTierId" class="input" @change="onManualTierChange">
              <option :value="0">-- Không chọn gói (mặc định) --</option>
              <option v-for="t in manualCourseTiers" :key="t.id" :value="t.id">
                {{ t.label }} - {{ t.price?.toLocaleString('vi-VN') }}đ
                {{ t.duration_days ? `(${t.duration_days} ngày)` : '(Vĩnh viễn)' }}
              </option>
            </select>
            <div class="form-hint" v-if="manualTierId">
              Chọn gói sẽ tự động tính giá và thời hạn hết hạn.
            </div>
          </div>

          <!-- Amount -->
          <div class="form-group">
            <label class="form-label">Số tiền (VNĐ)</label>
            <input
              v-model.number="manualAmount"
              type="number"
              class="input"
              placeholder="0 = miễn phí"
              min="0"
            />
            <div class="form-hint">
              Nhập 0 nếu tặng miễn phí. Số tiền sẽ được tính vào doanh thu.
            </div>
          </div>

          <!-- Note -->
          <div class="form-group">
            <label class="form-label">Ghi chú</label>
            <input
              v-model="manualNote"
              class="input"
              placeholder="VD: Tặng cho đối tác, thanh toán tiền mặt..."
            />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeManualEnrollModal">
            Hủy
          </button>
          <button
            class="btn btn-primary"
            :disabled="
              manualSubmitting || !manualSelectedUser || !manualCourseId
            "
            @click="submitManualEnroll"
          >
            <svg
              v-if="manualSubmitting"
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
            {{ manualSubmitting ? "Đang xử lý..." : "Thêm học viên" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import type { AdminEnrollment } from "../lib/types";

type CourseOption = { id: number; title: string };

const enrollments = ref<AdminEnrollment[]>([]);
const courses = ref<CourseOption[]>([]);
const selectedCourseId = ref(0);
const paymentStatus = ref<"all" | "paid" | "pending" | "free">("all");
const enrollmentStatus = ref<"all" | "pending" | "active" | "completed">("all");
const search = ref("");

const loading = ref(false);
const loadingCourses = ref(false);
const error = ref<string | null>(null);

// Manual enrollment modal state
const showManualModal = ref(false);
const manualUserSearch = ref("");
const manualUserResults = ref<{ id: number; name: string; email: string }[]>(
  [],
);
const manualSelectedUser = ref<{
  id: number;
  name: string;
  email: string;
} | null>(null);
const manualCourseId = ref(0);
const manualAmount = ref(0);
const manualNote = ref("");
const manualSubmitting = ref(false);
const manualError = ref<string | null>(null);
const manualSuccess = ref<string | null>(null);
const manualTierId = ref(0);
const manualCourseTiers = ref<{ id: number; label: string; price: number; duration_days: number | null }[]>([]);
let searchTimer: ReturnType<typeof setTimeout> | null = null;

function openManualEnrollModal() {
  showManualModal.value = true;
  manualUserSearch.value = "";
  manualUserResults.value = [];
  manualSelectedUser.value = null;
  manualCourseId.value = 0;
  manualAmount.value = 0;
  manualNote.value = "";
  manualTierId.value = 0;
  manualCourseTiers.value = [];
  manualError.value = null;
  manualSuccess.value = null;
}

function closeManualEnrollModal() {
  showManualModal.value = false;
}

function debouncedSearchUsers() {
  if (searchTimer) clearTimeout(searchTimer);
  manualSelectedUser.value = null;
  const q = manualUserSearch.value.trim();
  if (q.length < 2) {
    manualUserResults.value = [];
    return;
  }
  searchTimer = setTimeout(() => searchUsers(q), 300);
}

async function searchUsers(q: string) {
  try {
    const res = await apiFetch<{
      data: { id: number; name: string; email: string }[];
    }>(`/api/admin/users?search=${encodeURIComponent(q)}&per_page=10`);
    manualUserResults.value = res.data || [];
  } catch {
    manualUserResults.value = [];
  }
}

function selectUser(u: { id: number; name: string; email: string }) {
  manualSelectedUser.value = u;
  manualUserSearch.value = u.name;
  manualUserResults.value = [];
}

async function submitManualEnroll() {
  if (!manualSelectedUser.value || !manualCourseId.value) return;
  manualSubmitting.value = true;
  manualError.value = null;
  manualSuccess.value = null;
  try {
    const res = await apiFetch<{ message: string }>(
      "/api/admin/enrollments/manual",
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: manualSelectedUser.value.id,
          course_id: manualCourseId.value,
          duration_tier_id: manualTierId.value || null,
          amount: manualAmount.value || 0,
          note: manualNote.value,
        }),
      },
    );
    manualSuccess.value = res.message || "Thêm thành công!";
    // Reset form
    manualSelectedUser.value = null;
    manualUserSearch.value = "";
    manualCourseId.value = 0;
    manualTierId.value = 0;
    manualCourseTiers.value = [];
    manualAmount.value = 0;
    manualNote.value = "";
    // Reload
    await loadEnrollments();
  } catch (e) {
    manualError.value = extractMessage(e);
  } finally {
    manualSubmitting.value = false;
  }
}

async function onManualCourseChange() {
  manualTierId.value = 0;
  manualCourseTiers.value = [];
  if (!manualCourseId.value) return;
  try {
    const res = await apiFetch<{ data: { id: number; label: string; price: number; duration_days: number | null; is_active: boolean }[] }>(
      `/api/admin/courses/${manualCourseId.value}/duration-tiers`
    );
    manualCourseTiers.value = (res.data || []).filter(t => t.is_active);
  } catch {
    manualCourseTiers.value = [];
  }
}

function onManualTierChange() {
  if (!manualTierId.value) return;
  const tier = manualCourseTiers.value.find(t => t.id === manualTierId.value);
  if (tier) {
    manualAmount.value = tier.price;
  }
}

function getUserInitial(name?: string | null) {
  if (!name) return "?";
  return name.charAt(0).toUpperCase();
}

function formatDate(value?: string | null) {
  if (!value) return "--";
  try {
    return new Date(value).toLocaleString("vi-VN", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return value;
  }
}

function formatCurrency(value?: string | number | null) {
  if (value === undefined || value === null || value === "") return "--";
  const num = Number(value);
  if (Number.isNaN(num)) return String(value);
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(num);
}

function paymentLabel(e: AdminEnrollment) {
  if (e.payment_status === "paid") return "Đã TT";
  if (e.payment_status === "pending") return "Chờ TT";
  if (e.payment_status === "free") return "Miễn phí";
  return "--";
}

function paymentBadgeClass(e: AdminEnrollment) {
  if (e.payment_status === "paid") return "status-success";
  if (e.payment_status === "pending") return "status-warning";
  if (e.payment_status === "free") return "status-info";
  return "";
}

function enrollmentLabel(status?: string | null) {
  if (status === "pending") return "Chờ TT";
  if (status === "active") return "Đang học";
  if (status === "completed") return "Hoàn thành";
  return status || "--";
}

function enrollmentBadgeClass(status?: string | null) {
  if (status === "active") return "status-success";
  if (status === "pending") return "status-warning";
  if (status === "completed") return "status-primary";
  return "";
}

function resetFilters() {
  search.value = "";
  selectedCourseId.value = 0;
  paymentStatus.value = "all";
  enrollmentStatus.value = "all";
  loadEnrollments();
}

async function loadCourses() {
  loadingCourses.value = true;
  error.value = null;
  try {
    const res = await apiFetch<{ data: CourseOption[] }>(
      "/api/admin/courses/options",
    );
    courses.value = res.data || [];
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  } finally {
    loadingCourses.value = false;
  }
}

async function loadEnrollments() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (selectedCourseId.value)
      qs.set("course_id", String(selectedCourseId.value));
    if (paymentStatus.value !== "all")
      qs.set("payment_status", paymentStatus.value);
    if (enrollmentStatus.value !== "all")
      qs.set("status", enrollmentStatus.value);
    if (search.value.trim()) qs.set("search", search.value.trim());
    const res = await apiFetch<{ data: AdminEnrollment[] }>(
      `/api/admin/enrollments?${qs.toString()}`,
    );
    enrollments.value = res.data || [];
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

onMounted(async () => {
  await loadCourses();
  await loadEnrollments();
});
</script>

<style scoped>
.enrollments-page {
  max-width: 1600px;
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

/* Filter Card */
.filter-card {
  background: #fff;
  border-radius: var(--radius-xl);
  padding: 20px 24px;
  margin-bottom: 20px;
  box-shadow: var(--shadow-sm);
}

.filter-grid {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 160px;
}

.filter-search {
  flex: 1;
  min-width: 200px;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.filter-actions {
  display: flex;
  gap: 8px;
  margin-left: auto;
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
  min-width: 1100px;
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

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 38px;
  height: 38px;
  background: linear-gradient(
    135deg,
    var(--primary) 0%,
    var(--accent-violet) 100%
  );
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  font-size: 14px;
}

.user-info {
  min-width: 0;
}

.user-name {
  font-weight: 600;
  color: var(--gray-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 12px;
  color: var(--gray-400);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.course-cell {
  min-width: 0;
}

.course-name {
  font-weight: 600;
  color: var(--gray-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 260px;
}

.course-meta {
  font-size: 12px;
  color: var(--gray-400);
  margin-top: 2px;
}

.time-cell {
  font-size: 13px;
  color: var(--gray-600);
}

.amount-cell {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.amount-main {
  font-weight: 600;
  color: var(--gray-800);
}

.amount-sub {
  font-size: 11px;
  color: var(--gray-400);
}

.order-cell {
  min-width: 0;
}

.order-code {
  font-weight: 600;
  color: var(--gray-700);
  font-family: ui-monospace, monospace;
  font-size: 13px;
}

.sepay-id {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: var(--gray-400);
  margin-top: 4px;
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

.status-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
}

.status-primary {
  background: rgba(99, 102, 241, 0.1);
  color: var(--primary);
}

.status-info {
  background: rgba(59, 130, 246, 0.1);
  color: #2563eb;
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
@media (max-width: 1200px) {
  .filter-grid {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-item {
    width: 100%;
  }

  .filter-actions {
    margin-left: 0;
    margin-top: 8px;
  }
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

.form-hint {
  font-size: 12px;
  color: var(--text-secondary, #6b7280);
  margin-top: 4px;
}

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

.selected-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--gray-100, #f3f4f6);
  border-radius: 20px;
  padding: 6px 12px;
  margin-top: 8px;
  font-size: 13px;
}

.chip-remove {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  color: var(--gray-400);
  line-height: 1;
  padding: 0 2px;
}

.chip-remove:hover {
  color: var(--danger, #ef4444);
}

.alert-success {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  padding: 10px 14px;
  border-radius: 8px;
  font-size: 13px;
}

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
</style>
