<template>
  <div class="registrations-page">
    <div class="page-header">
      <div class="page-header-content">
        <div class="page-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
          </svg>
        </div>
        <div>
          <h1 class="page-title">Đăng ký Webinar</h1>
          <p class="page-subtitle">Quản lý danh sách đăng ký tham dự webinar</p>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="stats">
      <div class="stat-card">
        <span class="stat-value">{{ stats.total }}</span>
        <span class="stat-label">Tổng đăng ký</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.registered }}</span>
        <span class="stat-label">Đã đăng ký</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.attended }}</span>
        <span class="stat-label">Đã tham dự</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.cancelled }}</span>
        <span class="stat-label">Đã hủy</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
      <div class="filter-grid">
        <div class="filter-item filter-search">
          <label class="filter-label">Tìm kiếm</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm theo tên, email..."
            class="input"
            @input="debouncedFetch"
          />
        </div>
        <div class="filter-item">
          <label class="filter-label">Webinar</label>
          <select v-model="filters.webinar_id" class="input" @change="applyFilters">
            <option value="">Tất cả webinar</option>
            <option v-for="w in webinars" :key="w.id" :value="w.id">{{ w.title }}</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label">Trạng thái</label>
          <select v-model="filters.status" class="input" @change="applyFilters">
            <option value="">Tất cả</option>
            <option value="registered">Đã đăng ký</option>
            <option value="attended">Đã tham dự</option>
            <option value="cancelled">Đã hủy</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <svg class="spin" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
      </svg>
      Đang tải...
    </div>

    <!-- Table -->
    <div v-else-if="registrations.length" class="table-card">
      <table class="table">
        <thead>
          <tr>
            <th>Học viên</th>
            <th>Email</th>
            <th>Webinar</th>
            <th>Lịch trình</th>
            <th>Trạng thái</th>
            <th>Ngày ĐK</th>
            <th style="width:100px">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="reg in registrations" :key="reg.id">
            <td>{{ reg.user?.name || '—' }}</td>
            <td>{{ reg.user?.email || '—' }}</td>
            <td>
              <RouterLink v-if="reg.webinar" :to="`/webinars/${reg.webinar.id}`" class="link">
                {{ reg.webinar.title }}
              </RouterLink>
              <span v-else>—</span>
            </td>
            <td class="date-cell">{{ reg.webinar?.scheduled_at ? formatDate(reg.webinar.scheduled_at) : '—' }}</td>
            <td>
              <select
                :value="reg.status"
                class="status-select"
                :class="`status-${reg.status}`"
                @change="updateStatus(reg, ($event.target as HTMLSelectElement).value)"
              >
                <option value="registered">Đã đăng ký</option>
                <option value="attended">Đã tham dự</option>
                <option value="cancelled">Đã hủy</option>
              </select>
            </td>
            <td class="date-cell">{{ formatDate(reg.created_at) }}</td>
            <td>
              <div class="actions">
                <button class="btn-icon btn-danger" title="Xóa" @click="confirmDelete(reg)">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination" v-if="pagination.lastPage > 1">
        <button :disabled="pagination.currentPage <= 1" @click="goPage(pagination.currentPage - 1)">← Trước</button>
        <span>Trang {{ pagination.currentPage }} / {{ pagination.lastPage }} ({{ pagination.total }} kết quả)</span>
        <button :disabled="pagination.currentPage >= pagination.lastPage" @click="goPage(pagination.currentPage + 1)">Sau →</button>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p>Chưa có đăng ký webinar nào</p>
    </div>

    <!-- Delete confirm modal -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal">
        <div class="modal-header">
          <h3>Xác nhận xóa</h3>
          <button class="modal-close" @click="deleteTarget = null">&times;</button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc muốn xóa đăng ký của <strong>{{ deleteTarget.user?.name }}</strong> cho webinar <strong>{{ deleteTarget.webinar?.title }}</strong>?</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="deleteTarget = null">Hủy</button>
          <button class="btn btn-danger" @click="doDelete">Xóa</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { apiFetch } from '../lib/api'
import { useToast } from '../lib/toast'

const toast = useToast()

interface User {
  id: number
  name: string
  email: string
  phone?: string
}

interface WebinarRef {
  id: number
  title: string
  slug: string
  scheduled_at: string
  status: string
}

interface Registration {
  id: number
  webinar_id: number
  user_id: number
  status: string
  note: string | null
  created_at: string
  user?: User
  webinar?: WebinarRef
}

interface PaginatedResult {
  data: Registration[]
  current_page: number
  last_page: number
  total: number
}

const registrations = ref<Registration[]>([])
const webinars = ref<{ id: number; title: string }[]>([])
const loading = ref(false)
const deleteTarget = ref<Registration | null>(null)

const filters = reactive({
  search: '',
  webinar_id: '' as string | number,
  status: '',
})

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

const stats = computed(() => {
  const total = pagination.total
  const registered = registrations.value.filter(r => r.status === 'registered').length
  const attended = registrations.value.filter(r => r.status === 'attended').length
  const cancelled = registrations.value.filter(r => r.status === 'cancelled').length
  return { total, registered, attended, cancelled }
})

let debounceTimer: ReturnType<typeof setTimeout>
function debouncedFetch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchData(), 400)
}

function applyFilters() {
  fetchData(1)
}

async function fetchData(page = 1) {
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.set('page', String(page))
    params.set('per_page', '20')
    if (filters.search) params.set('search', filters.search)
    if (filters.webinar_id) params.set('webinar_id', String(filters.webinar_id))
    if (filters.status) params.set('status', filters.status)

    const data = await apiFetch<{ registrations: PaginatedResult; webinars: { id: number; title: string }[] }>(
      `/api/admin/webinar-registrations?${params.toString()}`
    )
    registrations.value = data.registrations.data
    pagination.currentPage = data.registrations.current_page
    pagination.lastPage = data.registrations.last_page
    pagination.total = data.registrations.total
    webinars.value = data.webinars
  } catch (e) {
    toast.error('Không thể tải danh sách đăng ký')
    console.error(e)
  } finally {
    loading.value = false
  }
}

function goPage(page: number) {
  fetchData(page)
}

async function updateStatus(reg: Registration, newStatus: string) {
  try {
    await apiFetch(`/api/admin/webinar-registrations/${reg.id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus }),
    })
    reg.status = newStatus
    toast.success('Đã cập nhật trạng thái')
  } catch (e) {
    toast.error('Không thể cập nhật trạng thái')
    console.error(e)
    fetchData(pagination.currentPage)
  }
}

function confirmDelete(reg: Registration) {
  deleteTarget.value = reg
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await apiFetch(`/api/admin/webinar-registrations/${deleteTarget.value.id}`, { method: 'DELETE' })
    toast.success('Đã xóa đăng ký')
    deleteTarget.value = null
    fetchData(pagination.currentPage)
  } catch (e) {
    toast.error('Không thể xóa đăng ký')
    console.error(e)
  }
}

function formatDate(dateStr: string) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

onMounted(() => fetchData())
</script>

<style scoped>
.registrations-page {
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
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
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

/* Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}
.stat-card {
  background: #fff;
  border: 1px solid var(--gray-100, #e5e7eb);
  border-radius: 12px;
  padding: 18px 24px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.stat-value { font-size: 26px; font-weight: 700; color: var(--gray-900, #111); }
.stat-label { font-size: 13px; color: var(--gray-500, #6b7280); margin-top: 2px; }

/* Filter Card */
.filter-card {
  background: #fff;
  border-radius: var(--radius-xl, 12px);
  padding: 20px 24px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
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

/* Table Card */
.table-card {
  background: #fff;
  border-radius: var(--radius-xl, 12px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

/* Loading & Empty */
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 60px 20px;
  color: var(--gray-400, #9ca3af);
  font-size: 14px;
}
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 60px 24px;
  color: var(--gray-400, #9ca3af);
}
.empty-state svg { opacity: 0.5; }
.empty-state p { margin: 0; font-size: 14px; }

/* Status select */
.status-select {
  padding: 4px 8px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  font-size: 13px;
  cursor: pointer;
  background: #fff;
}
.status-select.status-registered {
  color: #2563eb;
  border-color: #93c5fd;
  background: #eff6ff;
}
.status-select.status-attended {
  color: #16a34a;
  border-color: #86efac;
  background: #f0fdf4;
}
.status-select.status-cancelled {
  color: #dc2626;
  border-color: #fca5a5;
  background: #fef2f2;
}

/* Links & cells */
.link {
  color: #2563eb;
  text-decoration: none;
}
.link:hover {
  text-decoration: underline;
}
.date-cell { font-size: 13px; }
.actions { display: flex; gap: 6px; }

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid var(--gray-100, #e5e7eb);
  font-size: 13px;
  color: var(--gray-500, #6b7280);
}

/* Modal */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 100;
}
.modal {
  background: #fff; border-radius: 12px; width: 90%; max-width: 480px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px; border-bottom: 1px solid #e5e7eb;
}
.modal-header h3 { margin: 0; font-size: 16px; }
.modal-body { padding: 20px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #e5e7eb; }

/* Spin */
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 1200px) {
  .filter-grid { flex-direction: column; align-items: stretch; }
  .filter-item { width: 100%; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .stats-row { grid-template-columns: 1fr; }
}
</style>
