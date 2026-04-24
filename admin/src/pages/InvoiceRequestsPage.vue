<template>
  <div class="invoice-page">
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
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
        </div>
        <div>
          <h1 class="page-title">Yêu cầu xuất hóa đơn</h1>
          <p class="page-subtitle">
            Quản lý các yêu cầu xuất hóa đơn từ khách hàng
          </p>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="stats">
      <div class="stat-card">
        <span class="stat-value">{{ stats.total }}</span>
        <span class="stat-label">Tổng yêu cầu</span>
      </div>
      <div class="stat-card stat-pending">
        <span class="stat-value">{{ stats.pending }}</span>
        <span class="stat-label">Chờ xử lý</span>
      </div>
      <div class="stat-card stat-processing">
        <span class="stat-value">{{ stats.processing }}</span>
        <span class="stat-label">Đang xử lý</span>
      </div>
      <div class="stat-card stat-completed">
        <span class="stat-value">{{ stats.completed }}</span>
        <span class="stat-label">Đã hoàn thành</span>
      </div>
      <div class="stat-card stat-rejected">
        <span class="stat-value">{{ stats.rejected }}</span>
        <span class="stat-label">Từ chối</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
      <div class="filter-grid">
        <div class="filter-item filter-search">
          <label class="filter-label">Tìm kiếm</label>
          <input
            v-model="filters.search"
            class="input"
            placeholder="Tên công ty, MST, email..."
            @keyup.enter="applyFilters"
          />
        </div>
        <div class="filter-item">
          <label class="filter-label">Trạng thái</label>
          <select v-model="filters.status" class="input" @change="applyFilters">
            <option value="">Tất cả</option>
            <option value="pending">Chờ xử lý</option>
            <option value="processing">Đang xử lý</option>
            <option value="completed">Đã hoàn thành</option>
            <option value="rejected">Từ chối</option>
          </select>
        </div>
        <div class="filter-actions">
          <button class="btn btn-secondary" @click="applyFilters">Lọc</button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <svg
        class="spin"
        width="24"
        height="24"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
      >
        <path
          d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"
        />
      </svg>
      Đang tải...
    </div>

    <!-- Table -->
    <div v-else-if="items.length" class="table-card">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Khách hàng</th>
            <th>CCCD</th>
            <th>Tên công ty</th>
            <th>MST</th>
            <th>Địa chỉ</th>
            <th>Email HĐ</th>
            <th>Đơn hàng</th>
            <th>Minvoice</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <strong>#{{ item.id }}</strong>
            </td>
            <td>
              <div>{{ item.user?.name || "—" }}</div>
              <div class="text-muted">{{ item.user?.email || "" }}</div>
              <div class="text-muted" v-if="item.user?.phone">
                📱 {{ item.user.phone }}
              </div>
            </td>
            <td>{{ item.user?.cccd || "—" }}</td>
            <td>
              <strong>{{ item.company_name }}</strong>
            </td>
            <td>
              <code>{{ item.tax_code }}</code>
            </td>
            <td class="address-cell">{{ item.company_address }}</td>
            <td>{{ item.invoice_email }}</td>
            <td>
              <div>{{ item.payment?.order_code || `#${item.payment_id}` }}</div>
              <div class="text-muted">
                {{ formatPrice(item.payment?.amount) }}
              </div>
              <div class="text-muted" v-if="item.payment?.course">
                🎓 {{ item.payment.course.title }}
              </div>
              <div class="text-muted" v-if="item.payment?.ebook">
                📚 {{ item.payment.ebook.title }}
              </div>
              <div class="text-muted" v-if="item.payment?.webinar">
                🎥 {{ item.payment.webinar.title }}
              </div>
            </td>
            <td>
              <div v-if="item.invoice_series || item.invoice_number">
                <strong>
                  {{ item.invoice_series || "" }}
                  <span v-if="item.invoice_series && item.invoice_number">
                    -
                  </span>
                  {{ item.invoice_number || "" }}
                </strong>
              </div>
              <div class="text-muted" v-if="item.provider_status">
                {{ item.provider_status }}
              </div>
              <div class="text-danger" v-if="item.last_error">
                {{ item.last_error }}
              </div>
              <div class="text-muted" v-if="!item.invoice_number && !item.provider_status && !item.last_error">
                —
              </div>
            </td>
            <td>
              <select
                :value="item.status"
                @change="
                  updateStatus(
                    item.id,
                    ($event.target as HTMLSelectElement).value,
                  )
                "
                class="status-select"
                :class="`status-${item.status}`"
              >
                <option value="pending">Chờ xử lý</option>
                <option value="processing">Đang xử lý</option>
                <option value="completed">Đã hoàn thành</option>
                <option value="rejected">Từ chối</option>
              </select>
            </td>
            <td class="date-cell">{{ formatDate(item.created_at) }}</td>
            <td>
              <div class="action-buttons">
                <button
                  class="btn-icon btn-note"
                  @click="openNote(item)"
                  title="Ghi chú"
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
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>
                <button
                  class="btn-icon btn-delete"
                  @click="deleteItem(item.id)"
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
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.lastPage > 1">
        <button
          :disabled="pagination.currentPage <= 1"
          @click="goPage(pagination.currentPage - 1)"
        >
          ← Trước
        </button>
        <span
          >Trang {{ pagination.currentPage }} / {{ pagination.lastPage }} ({{
            pagination.total
          }}
          kết quả)</span
        >
        <button
          :disabled="pagination.currentPage >= pagination.lastPage"
          @click="goPage(pagination.currentPage + 1)"
        >
          Sau →
        </button>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <svg
        width="48"
        height="48"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.5"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <p>Chưa có yêu cầu xuất hóa đơn nào</p>
    </div>

    <!-- Note Modal -->
    <div
      v-if="noteModal.open"
      class="modal-overlay"
      @click.self="noteModal.open = false"
    >
      <div class="modal-box">
        <h3 class="modal-title">Ghi chú admin</h3>
        <p class="text-muted" style="margin-bottom: 12px">
          Yêu cầu #{{ noteModal.itemId }} — {{ noteModal.companyName }}
        </p>
        <textarea
          v-model="noteModal.note"
          class="input"
          rows="4"
          placeholder="Nhập ghi chú cho yêu cầu này..."
        ></textarea>
        <div class="modal-actions">
          <button class="btn btn-secondary" @click="noteModal.open = false">
            Hủy
          </button>
          <button
            class="btn btn-primary"
            @click="saveNote"
            :disabled="noteModal.saving"
          >
            {{ noteModal.saving ? "Đang lưu..." : "Lưu ghi chú" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { apiFetch } from "../lib/api";
import { useToast } from "../lib/toast";

const toast = useToast();

interface InvoiceRequestItem {
  id: number;
  user_id: number;
  payment_id: number;
  company_name: string;
  tax_code: string;
  company_address: string;
  invoice_email: string;
  status: string;
  provider?: string | null;
  provider_status?: string | null;
  invoice_number?: string | null;
  invoice_series?: string | null;
  last_error?: string | null;
  admin_note: string | null;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email: string;
    phone?: string;
    cccd?: string;
  };
  payment?: {
    id: number;
    order_code: string;
    amount: number;
    status: string;
    product_type: string;
    course?: { id: number; title: string };
    ebook?: { id: number; title: string };
    webinar?: { id: number; title: string };
  };
}

interface Stats {
  total: number;
  pending: number;
  processing: number;
  completed: number;
  rejected: number;
}

const items = ref<InvoiceRequestItem[]>([]);
const stats = ref<Stats | null>(null);
const loading = ref(true);

const filters = reactive({ search: "", status: "" });
const pagination = reactive({ currentPage: 1, lastPage: 1, total: 0 });

const noteModal = reactive({
  open: false,
  itemId: 0,
  companyName: "",
  note: "",
  saving: false,
});

function formatPrice(price: number | undefined) {
  if (!price) return "0₫";
  return new Intl.NumberFormat("vi-VN").format(price) + "₫";
}

function formatDate(dateStr: string) {
  if (!dateStr) return "—";
  return new Date(dateStr).toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

async function fetchData(page = 1) {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    params.set("page", String(page));
    if (filters.search) params.set("search", filters.search);
    if (filters.status) params.set("status", filters.status);

    const data = await apiFetch<{ invoice_requests: any; stats: Stats }>(
      `/api/admin/invoice-requests?${params}`,
    );
    items.value = data.invoice_requests.data || [];
    pagination.currentPage = data.invoice_requests.current_page || 1;
    pagination.lastPage = data.invoice_requests.last_page || 1;
    pagination.total = data.invoice_requests.total || 0;
    stats.value = data.stats;
  } catch (e) {
    toast.error("Không thể tải danh sách yêu cầu hóa đơn");
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  fetchData(1);
}
function goPage(p: number) {
  fetchData(p);
}

async function updateStatus(id: number, status: string) {
  try {
    await apiFetch(`/api/admin/invoice-requests/${id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ status }),
    });
    const item = items.value.find((i) => i.id === id);
    if (item) item.status = status;
    toast.success("Cập nhật trạng thái thành công");
  } catch {
    toast.error("Không thể cập nhật trạng thái");
  }
}

function openNote(item: InvoiceRequestItem) {
  noteModal.itemId = item.id;
  noteModal.companyName = item.company_name;
  noteModal.note = item.admin_note || "";
  noteModal.open = true;
}

async function saveNote() {
  noteModal.saving = true;
  try {
    await apiFetch(`/api/admin/invoice-requests/${noteModal.itemId}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ admin_note: noteModal.note }),
    });
    const item = items.value.find((i) => i.id === noteModal.itemId);
    if (item) item.admin_note = noteModal.note;
    noteModal.open = false;
    toast.success("Đã lưu ghi chú");
  } catch {
    toast.error("Không thể lưu ghi chú");
  } finally {
    noteModal.saving = false;
  }
}

async function deleteItem(id: number) {
  if (!confirm("Bạn có chắc muốn xóa yêu cầu này?")) return;
  try {
    await apiFetch(`/api/admin/invoice-requests/${id}`, { method: "DELETE" });
    items.value = items.value.filter((i) => i.id !== id);
    toast.success("Đã xóa yêu cầu hóa đơn");
  } catch {
    toast.error("Không thể xóa");
  }
}

onMounted(() => fetchData());
</script>

<style scoped>
.invoice-page {
  max-width: 1600px;
  margin: 0 auto;
}

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
    rgba(249, 115, 22, 0.1) 0%,
    rgba(234, 88, 12, 0.1) 100%
  );
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #f97316;
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

.stats-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}
.stat-card {
  background: #fff;
  border: 1px solid var(--gray-100, #e5e7eb);
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--gray-900);
}
.stat-label {
  font-size: 12px;
  color: var(--gray-500);
  font-weight: 500;
}
.stat-pending .stat-value {
  color: #eab308;
}
.stat-processing .stat-value {
  color: #3b82f6;
}
.stat-completed .stat-value {
  color: #22c55e;
}
.stat-rejected .stat-value {
  color: #ef4444;
}

.filter-card {
  background: #fff;
  border: 1px solid var(--gray-100, #e5e7eb);
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
}
.filter-grid {
  display: flex;
  gap: 12px;
  align-items: flex-end;
}
.filter-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.filter-search {
  flex: 1;
}
.filter-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--gray-500);
}
.filter-actions {
  display: flex;
  gap: 8px;
}

.input {
  padding: 8px 12px;
  border: 1px solid var(--gray-200, #e5e7eb);
  border-radius: 8px;
  font-size: 14px;
  background: #fff;
  color: var(--gray-900);
  outline: none;
  width: 100%;
}
.input:focus {
  border-color: var(--primary, #6366f1);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

textarea.input {
  resize: vertical;
  min-height: 80px;
}

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: none;
}
.btn-secondary {
  background: var(--gray-100, #f3f4f6);
  color: var(--gray-700);
}
.btn-secondary:hover {
  background: var(--gray-200, #e5e7eb);
}
.btn-primary {
  background: var(--primary, #6366f1);
  color: #fff;
}
.btn-primary:hover {
  opacity: 0.9;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-state {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 40px;
  justify-content: center;
  color: var(--gray-500);
}
.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.table-card {
  background: #fff;
  border: 1px solid var(--gray-100, #e5e7eb);
  border-radius: 12px;
  overflow: hidden;
}
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.table th {
  background: var(--gray-50, #f9fafb);
  padding: 10px 14px;
  text-align: left;
  font-weight: 600;
  color: var(--gray-600);
  white-space: nowrap;
  border-bottom: 1px solid var(--gray-100, #e5e7eb);
}
.table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--gray-50, #f9fafb);
  vertical-align: top;
}
.table tbody tr:hover {
  background: var(--gray-50, #f9fafb);
}

.text-muted {
  color: var(--gray-500, #6b7280);
  font-size: 12px;
}
.text-danger {
  color: #dc2626;
  font-size: 12px;
}
.date-cell {
  white-space: nowrap;
  font-size: 12px;
  color: var(--gray-500);
}
.address-cell {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

code {
  background: var(--gray-100, #f3f4f6);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
}

.status-select {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid transparent;
  cursor: pointer;
  outline: none;
}
.status-pending {
  background: #fef9c3;
  color: #854d0e;
  border-color: #fde047;
}
.status-processing {
  background: #dbeafe;
  color: #1e40af;
  border-color: #93c5fd;
}
.status-completed {
  background: #dcfce7;
  color: #166534;
  border-color: #86efac;
}
.status-rejected {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fca5a5;
}

.action-buttons {
  display: flex;
  gap: 6px;
}
.btn-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--gray-200, #e5e7eb);
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--gray-500);
}
.btn-icon:hover {
  background: var(--gray-50, #f9fafb);
}
.btn-note:hover {
  color: #3b82f6;
  border-color: #93c5fd;
}
.btn-delete:hover {
  color: #ef4444;
  border-color: #fca5a5;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
}
.pagination button {
  padding: 6px 14px;
  border-radius: 6px;
  border: 1px solid var(--gray-200);
  background: #fff;
  cursor: pointer;
  font-size: 13px;
}
.pagination button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.pagination span {
  font-size: 13px;
  color: var(--gray-500);
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 60px 20px;
  color: var(--gray-400);
}

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}
.modal-box {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  max-width: 480px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.modal-title {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 700;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
}
</style>
