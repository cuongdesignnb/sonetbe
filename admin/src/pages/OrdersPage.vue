<template>
  <div class="orders-page">
    <div class="page-header">
      <div class="page-header-content">
        <div class="page-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
          </svg>
        </div>
        <div>
          <h1 class="page-title">Quản lý Đơn hàng</h1>
          <p class="page-subtitle">Tất cả đơn hàng khóa học và ebook</p>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="stats">
      <div class="stat-card">
        <span class="stat-value">{{ stats.total }}</span>
        <span class="stat-label">Tổng đơn</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.paid }}</span>
        <span class="stat-label">Đã thanh toán</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.pending }}</span>
        <span class="stat-label">Chờ TT</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.course_orders }}</span>
        <span class="stat-label">Đơn khóa học</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ stats.ebook_orders }}</span>
        <span class="stat-label">Đơn ebook</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ formatPrice(stats.total_revenue) }}</span>
        <span class="stat-label">Doanh thu</span>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
      <div class="filter-grid">
        <div class="filter-item filter-search">
          <label class="filter-label">Tìm kiếm</label>
          <input v-model="filters.search" class="input" placeholder="Mã đơn, tên, email..." @keyup.enter="applyFilters" />
        </div>
        <div class="filter-item">
          <label class="filter-label">Loại sản phẩm</label>
          <select v-model="filters.product_type" class="input" @change="applyFilters">
            <option value="">Tất cả</option>
            <option value="course">Khóa học</option>
            <option value="ebook">Ebook</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label">Trạng thái</label>
          <select v-model="filters.status" class="input" @change="applyFilters">
            <option value="">Tất cả</option>
            <option value="paid">Đã thanh toán</option>
            <option value="pending">Chờ thanh toán</option>
          </select>
        </div>
        <div class="filter-actions">
          <button class="btn btn-secondary" @click="applyFilters">Lọc</button>
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
    <div v-else-if="orders.length" class="table-card">
      <table class="table">
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Loại</th>
            <th>Sản phẩm</th>
            <th>Khách hàng</th>
            <th>Số tiền</th>
            <th>Trạng thái</th>
            <th>Ngày tạo</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in orders" :key="order.id">
            <td><strong>{{ order.order_code || `#${order.id}` }}</strong></td>
            <td>
              <span class="type-badge" :class="`type-${order.product_type}`">
                {{ order.product_type === 'ebook' ? '📚 Ebook' : '🎓 Khóa học' }}
              </span>
            </td>
            <td>
              <div class="product-name">
                {{ order.product_type === 'ebook' ? (order.ebook?.title || '—') : (order.course?.title || '—') }}
              </div>
            </td>
            <td>
              <div>{{ order.user?.name || '—' }}</div>
              <div class="text-muted">{{ order.user?.email || '' }}</div>
            </td>
            <td>
              <div v-if="order.discount_amount > 0">
                <strong>{{ formatPrice(order.amount) }}</strong>
                <div class="text-muted" style="text-decoration:line-through">{{ formatPrice(order.original_amount) }}</div>
              </div>
              <strong v-else>{{ formatPrice(order.amount) }}</strong>
            </td>
            <td>
              <span class="badge" :class="`badge-${order.status}`">{{ statusLabel(order.status) }}</span>
            </td>
            <td class="date-cell">{{ formatDate(order.created_at) }}</td>
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
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
      </svg>
      <p>Chưa có đơn hàng nào</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { apiFetch } from '../lib/api'
import { useToast } from '../lib/toast'

const toast = useToast()

interface Order {
  id: number
  product_type: string
  order_code: string
  status: string
  amount: number
  original_amount: number
  discount_amount: number
  created_at: string
  paid_at: string | null
  user?: { id: number; name: string; email: string }
  course?: { id: number; title: string }
  ebook?: { id: number; title: string }
}

interface Stats {
  total: number
  paid: number
  pending: number
  course_orders: number
  ebook_orders: number
  total_revenue: number
}

const orders = ref<Order[]>([])
const stats = ref<Stats | null>(null)
const loading = ref(true)

const filters = reactive({
  search: '',
  product_type: '',
  status: '',
})

const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
})

function statusLabel(s: string) {
  const map: Record<string, string> = {
    paid: 'Đã thanh toán',
    pending: 'Chờ thanh toán',
  }
  return map[s] || s
}

function formatPrice(price: number) {
  if (!price) return '0₫'
  return new Intl.NumberFormat('vi-VN').format(price) + '₫'
}

function formatDate(dateStr: string) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

async function fetchData(page = 1) {
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.set('page', String(typeof page === 'number' ? page : 1))
    if (filters.search) params.set('search', filters.search)
    if (filters.product_type) params.set('product_type', filters.product_type)
    if (filters.status) params.set('status', filters.status)

    const data = await apiFetch<{ orders: any; stats: Stats }>(`/api/admin/orders?${params}`)
    orders.value = data.orders.data || []
    pagination.currentPage = data.orders.current_page || 1
    pagination.lastPage = data.orders.last_page || 1
    pagination.total = data.orders.total || 0
    stats.value = data.stats
  } catch (e) {
    toast.error('Không thể tải đơn hàng')
    console.error(e)
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  fetchData(1)
}

function goPage(p: number) {
  fetchData(p)
}

onMounted(() => fetchData())
</script>

<style scoped>
.orders-page {
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
  grid-template-columns: repeat(6, 1fr);
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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.stat-value { font-size: 22px; font-weight: 700; color: var(--gray-900, #111); }
.stat-label { font-size: 12px; color: var(--gray-500, #6b7280); margin-top: 2px; }

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

/* Type badge */
.type-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}
.type-course {
  background: #dbeafe;
  color: #1d4ed8;
}
.type-ebook {
  background: #fce7f3;
  color: #be185d;
}

.product-name {
  font-weight: 500;
  font-size: 14px;
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.text-muted { color: #9ca3af; font-size: 12px; }
.date-cell { font-size: 13px; }

/* Badges */
.badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-paid { background: #d1fae5; color: #065f46; }
.badge-pending { background: #fef3c7; color: #92400e; }

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

/* Spin */
.spin { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 1200px) {
  .filter-grid { flex-direction: column; align-items: stretch; }
  .filter-item { width: 100%; }
  .filter-actions { margin-left: 0; margin-top: 8px; }
  .stats-row { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>
