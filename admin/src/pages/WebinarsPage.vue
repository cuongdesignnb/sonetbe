<template>
  <div class="webinars-page">
    <div class="page-header">
      <div class="page-header-content">
        <div class="page-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
        </div>
        <div>
          <h1 class="page-title">Quản lý Webinar</h1>
          <p class="page-subtitle">Tạo và quản lý các buổi webinar trực tuyến</p>
        </div>
      </div>
      <button class="btn btn-primary" @click="openCreate">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tạo webinar
      </button>
    </div>

    <!-- Filters -->
    <div class="filter-card">
      <div class="filter-grid">
        <div class="filter-item filter-search">
          <label class="filter-label">Tìm kiếm</label>
          <input v-model="search" class="input" placeholder="Tìm theo tiêu đề, diễn giả..." @keyup.enter="loadWebinars" />
        </div>
        <div class="filter-item">
          <label class="filter-label">Trạng thái</label>
          <select v-model="statusFilter" class="input" @change="loadWebinars">
            <option value="">Tất cả</option>
            <option value="upcoming">Sắp diễn ra</option>
            <option value="live">Đang live</option>
            <option value="completed">Đã kết thúc</option>
            <option value="cancelled">Đã hủy</option>
          </select>
        </div>
        <div class="filter-actions">
          <button class="btn btn-secondary" @click="loadWebinars">Lọc</button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="!loading">
      <div class="stat-card">
        <span class="stat-value">{{ totalWebinars }}</span>
        <span class="stat-label">Tổng webinar</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ upcomingCount }}</span>
        <span class="stat-label">Sắp diễn ra</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ completedCount }}</span>
        <span class="stat-label">Đã kết thúc</span>
      </div>
    </div>

    <!-- Table -->
    <div class="table-card">
      <div v-if="loading" class="loading-state">
        <svg class="spin" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="20"/>
        </svg>
        Đang tải...
      </div>

      <table v-else-if="webinars.length" class="table">
        <thead>
          <tr>
            <th>Webinar</th>
            <th>Diễn giả</th>
            <th>Thời gian</th>
            <th>Trạng thái</th>
            <th>Giá</th>
            <th>Đăng ký</th>
            <th>Lượt xem</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="w in webinars" :key="w.id">
            <td>
              <div class="webinar-cell">
                <img v-if="w.thumbnail" :src="w.thumbnail" class="webinar-thumb" />
                <div v-else class="webinar-thumb-placeholder">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <div class="webinar-title">{{ w.title }}</div>
                  <div class="webinar-slug">{{ w.slug }}</div>
                </div>
              </div>
            </td>
            <td>{{ w.instructor_name }}</td>
            <td>
              <div class="date-cell">
                {{ formatDate(w.scheduled_at) }}
                <span v-if="w.duration_minutes" class="text-muted">· {{ w.duration_minutes }} phút</span>
              </div>
            </td>
            <td>
              <span :class="['badge', 'badge-' + w.status]">{{ statusLabel(w.status) }}</span>
            </td>
            <td>
              <span v-if="w.is_free" class="badge badge-free">Miễn phí</span>
              <span v-else>{{ Number(w.price).toLocaleString('vi-VN') }}₫</span>
            </td>
            <td>
              <span class="registrations-count" @click="showRegistrations(w)">
                {{ w.registrations_count ?? 0 }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                </svg>
              </span>
            </td>
            <td>{{ (w.views_count || 0).toLocaleString() }}</td>
            <td>
              <div class="actions">
                <button class="btn-icon" title="Sửa" @click="$router.push('/webinars/' + w.id)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
                <button class="btn-icon btn-icon-danger" title="Xóa" @click="confirmDelete(w)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
        </svg>
        <p>Chưa có webinar nào</p>
        <button class="btn btn-primary" @click="openCreate">Tạo webinar đầu tiên</button>
      </div>

      <!-- Pagination -->
      <div v-if="lastPage > 1" class="pagination">
        <button class="btn btn-sm" :disabled="page <= 1" @click="page--; loadWebinars()">← Trước</button>
        <span>Trang {{ page }} / {{ lastPage }}</span>
        <button class="btn btn-sm" :disabled="page >= lastPage" @click="page++; loadWebinars()">Sau →</button>
      </div>
    </div>

    <!-- Registrations Modal -->
    <div v-if="regModal" class="modal-overlay" @click.self="regModal = false">
      <div class="modal" style="max-width: 600px">
        <div class="modal-header">
          <h3>Đăng ký webinar: {{ regWebinar?.title }}</h3>
          <button class="btn-icon" @click="regModal = false">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="regLoading" class="loading-state">Đang tải...</div>
          <table v-else-if="registrations.length" class="table">
            <thead>
              <tr>
                <th>Tên</th>
                <th>Email</th>
                <th>Trạng thái</th>
                <th>Ngày đăng ký</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in registrations" :key="r.id">
                <td>{{ r.user?.name || '—' }}</td>
                <td>{{ r.user?.email || '—' }}</td>
                <td><span :class="['badge', 'badge-' + r.status]">{{ r.status }}</span></td>
                <td>{{ formatDate(r.created_at) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="text-muted" style="text-align:center;padding:20px">Chưa có ai đăng ký</p>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal" style="max-width: 420px">
        <div class="modal-header">
          <h3>Xóa webinar</h3>
          <button class="btn-icon" @click="deleteTarget = null">&times;</button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc muốn xóa webinar <strong>{{ deleteTarget.title }}</strong>?</p>
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
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { apiFetch } from '../lib/api'
import { useToast } from '../lib/toast'

const router = useRouter()
const toast = useToast()

interface Webinar {
  id: number
  title: string
  slug: string
  thumbnail: string | null
  instructor_name: string
  scheduled_at: string
  duration_minutes: number | null
  status: string
  is_free: boolean
  price: string
  views_count: number
  registrations_count?: number
}

const webinars = ref<Webinar[]>([])
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const page = ref(1)
const lastPage = ref(1)
const totalWebinars = ref(0)

const upcomingCount = computed(() => webinars.value.filter(w => w.status === 'upcoming').length)
const completedCount = computed(() => webinars.value.filter(w => w.status === 'completed').length)

// Registrations modal
const regModal = ref(false)
const regLoading = ref(false)
const regWebinar = ref<Webinar | null>(null)
const registrations = ref<any[]>([])

// Delete
const deleteTarget = ref<Webinar | null>(null)

function statusLabel(s: string) {
  const map: Record<string, string> = {
    upcoming: 'Sắp diễn ra',
    live: 'Đang live',
    completed: 'Đã kết thúc',
    cancelled: 'Đã hủy',
  }
  return map[s] || s
}

function formatDate(d: string) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

async function loadWebinars() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)
    if (statusFilter.value) params.set('status', statusFilter.value)
    params.set('page', page.value.toString())
    const data = await apiFetch<any>(`/api/admin/webinars?${params}`)
    webinars.value = data.data || []
    lastPage.value = data.last_page || 1
    totalWebinars.value = data.total || 0
  } catch (e: any) {
    toast.error('Lỗi tải webinars: ' + (e.message || ''))
  } finally {
    loading.value = false
  }
}

function openCreate() {
  router.push('/webinars/new')
}

async function showRegistrations(w: Webinar) {
  regWebinar.value = w
  regModal.value = true
  regLoading.value = true
  try {
    const data = await apiFetch<any>(`/api/admin/webinars/${w.id}/registrations`)
    registrations.value = data.data || []
  } catch {
    registrations.value = []
  } finally {
    regLoading.value = false
  }
}

function confirmDelete(w: Webinar) {
  deleteTarget.value = w
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await apiFetch(`/api/admin/webinars/${deleteTarget.value.id}`, { method: 'DELETE' })
    toast.success('Đã xóa webinar')
    deleteTarget.value = null
    loadWebinars()
  } catch (e: any) {
    toast.error('Lỗi xóa: ' + (e.message || ''))
  }
}

onMounted(loadWebinars)
</script>

<style scoped>
.webinars-page {
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

.filter-actions {
  display: flex;
  gap: 8px;
  margin-left: auto;
}

/* Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
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

/* Table Card */
.table-card {
  background: #fff;
  border-radius: var(--radius-xl, 12px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

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

/* Table cells */
.webinar-cell { display: flex; align-items: center; gap: 12px; }
.webinar-thumb { width: 60px; height: 40px; border-radius: 6px; object-fit: cover; }
.webinar-thumb-placeholder {
  width: 60px; height: 40px; border-radius: 6px; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; color: #9ca3af;
}
.webinar-title { font-weight: 600; font-size: 14px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.webinar-slug { font-size: 12px; color: #9ca3af; }

.date-cell { font-size: 13px; }
.text-muted { color: #9ca3af; font-size: 12px; }

.badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-upcoming { background: #dbeafe; color: #1d4ed8; }
.badge-live { background: #fce7f3; color: #be185d; }
.badge-completed { background: #d1fae5; color: #065f46; }
.badge-cancelled { background: #fee2e2; color: #991b1b; }
.badge-free { background: #d1fae5; color: #065f46; }
.badge-registered { background: #dbeafe; color: #1d4ed8; }
.badge-attended { background: #d1fae5; color: #065f46; }

.registrations-count {
  display: inline-flex; align-items: center; gap: 4px; cursor: pointer;
  color: #2563eb; font-weight: 600;
}
.registrations-count:hover { text-decoration: underline; }

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
  background: #fff; border-radius: 12px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px; border-bottom: 1px solid #e5e7eb;
}
.modal-header h3 { margin: 0; font-size: 16px; }
.modal-body { padding: 20px; max-height: 60vh; overflow-y: auto; }
.modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 20px; border-top: 1px solid #e5e7eb; }

/* Spin */
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 1200px) {
  .filter-grid { flex-direction: column; align-items: stretch; }
  .filter-item { width: 100%; }
  .filter-actions { margin-left: 0; margin-top: 8px; }
  .stats-row { grid-template-columns: 1fr; }
}
</style>
