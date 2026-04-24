<template>
  <div class="ebooks-page">
    <div class="page-header">
      <div class="page-header-content">
        <div class="page-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
        </div>
        <div>
          <h1 class="page-title">Quản lý Ebook</h1>
          <p class="page-subtitle">Tạo và quản lý sách điện tử, tài liệu</p>
        </div>
      </div>
      <button class="btn btn-primary" @click="$router.push('/ebooks/new')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Tạo ebook
      </button>
    </div>

    <!-- Filters -->
    <div class="filter-card">
      <div class="filter-grid">
        <div class="filter-item filter-search">
          <label class="filter-label">Tìm kiếm</label>
          <input v-model="search" class="input" placeholder="Tìm theo tiêu đề, tác giả..." @keyup.enter="loadEbooks" />
        </div>
        <div class="filter-item">
          <label class="filter-label">Loại</label>
          <select v-model="typeFilter" class="input" @change="loadEbooks">
            <option value="">Tất cả</option>
            <option value="ebook">Ebook</option>
            <option value="book">Sách</option>
            <option value="guide">Tài liệu</option>
          </select>
        </div>
        <div class="filter-item">
          <label class="filter-label">Trạng thái</label>
          <select v-model="statusFilter" class="input" @change="loadEbooks">
            <option value="">Tất cả</option>
            <option value="published">Đã xuất bản</option>
            <option value="draft">Nháp</option>
            <option value="coming_soon">Sắp ra mắt</option>
            <option value="archived">Lưu trữ</option>
          </select>
        </div>
        <div class="filter-actions">
          <button class="btn btn-secondary" @click="loadEbooks">Lọc</button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row" v-if="!loading">
      <div class="stat-card">
        <span class="stat-value">{{ totalEbooks }}</span>
        <span class="stat-label">Tổng ebook</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ publishedCount }}</span>
        <span class="stat-label">Đã xuất bản</span>
      </div>
      <div class="stat-card">
        <span class="stat-value">{{ draftCount }}</span>
        <span class="stat-label">Bản nháp</span>
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
    <div v-else-if="ebooks.length" class="table-card">
      <table class="table">
        <thead>
          <tr>
            <th>Ebook</th>
            <th>Tác giả</th>
            <th>Loại</th>
            <th>Trạng thái</th>
            <th>Giá</th>
            <th>Lượt tải</th>
            <th style="width:120px">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ebook in ebooks" :key="ebook.id">
            <td>
              <div class="ebook-cell">
                <img v-if="ebook.thumbnail" :src="ebook.thumbnail" class="ebook-thumb" :alt="ebook.title" />
                <div v-else class="ebook-thumb-placeholder">
                  <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                  </svg>
                </div>
                <div>
                  <div class="ebook-title">{{ ebook.title }}</div>
                  <div class="ebook-slug">{{ ebook.slug }}</div>
                </div>
              </div>
            </td>
            <td>{{ ebook.author_name }}</td>
            <td><span class="badge" :class="`badge-${ebook.type}`">{{ typeLabel(ebook.type) }}</span></td>
            <td><span class="badge" :class="`badge-${ebook.status}`">{{ statusLabel(ebook.status) }}</span></td>
            <td>
              <div v-if="ebook.price > 0">
                <strong>{{ formatPrice(ebook.price) }}</strong>
                <div v-if="ebook.original_price > ebook.price" class="text-muted" style="text-decoration:line-through">{{ formatPrice(ebook.original_price) }}</div>
              </div>
              <span v-else class="badge badge-free">Miễn phí</span>
            </td>
            <td>{{ ebook.download_count || 0 }}</td>
            <td>
              <div class="actions">
                <button class="btn-icon" title="Sửa" @click="$router.push(`/ebooks/${ebook.id}`)">
                  <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <button class="btn-icon btn-danger" title="Xóa" @click="confirmDelete(ebook)">
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
      <div class="pagination" v-if="lastPage > 1">
        <button :disabled="page <= 1" @click="goPage(page - 1)">← Trước</button>
        <span>Trang {{ page }} / {{ lastPage }} ({{ totalEbooks }} kết quả)</span>
        <button :disabled="page >= lastPage" @click="goPage(page + 1)">Sau →</button>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
      </svg>
      <p>Chưa có ebook nào. Hãy tạo ebook đầu tiên!</p>
    </div>

    <!-- Delete confirm modal -->
    <div v-if="deleteTarget" class="modal-overlay" @click.self="deleteTarget = null">
      <div class="modal">
        <div class="modal-header">
          <h3>Xác nhận xóa</h3>
          <button class="modal-close" @click="deleteTarget = null">&times;</button>
        </div>
        <div class="modal-body">
          <p>Bạn có chắc muốn xóa ebook <strong>{{ deleteTarget.title }}</strong>?</p>
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
import { ref, computed, onMounted } from 'vue'
import { apiFetch } from '../lib/api'
import { useToast } from '../lib/toast'

const toast = useToast()

interface Ebook {
  id: number
  title: string
  slug: string
  thumbnail: string | null
  author_name: string
  type: string
  status: string
  price: number
  original_price: number
  download_count: number
}

const ebooks = ref<Ebook[]>([])
const loading = ref(true)
const search = ref('')
const typeFilter = ref('')
const statusFilter = ref('')
const page = ref(1)
const lastPage = ref(1)
const totalEbooks = ref(0)
const deleteTarget = ref<Ebook | null>(null)

const publishedCount = computed(() => ebooks.value.filter(e => e.status === 'published').length)
const draftCount = computed(() => ebooks.value.filter(e => e.status === 'draft').length)

function typeLabel(t: string) {
  const map: Record<string, string> = { ebook: 'Ebook', book: 'Sách', guide: 'Tài liệu' }
  return map[t] || t
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    published: 'Đã xuất bản',
    draft: 'Nháp',
    coming_soon: 'Sắp ra mắt',
    archived: 'Lưu trữ',
  }
  return map[s] || s
}

function formatPrice(price: number) {
  return new Intl.NumberFormat('vi-VN').format(price) + '₫'
}

async function loadEbooks() {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (search.value) params.set('search', search.value)
    if (typeFilter.value) params.set('type', typeFilter.value)
    if (statusFilter.value) params.set('status', statusFilter.value)
    params.set('page', page.value.toString())
    const data = await apiFetch<any>(`/api/admin/ebooks?${params}`)
    ebooks.value = data.data || []
    lastPage.value = data.last_page || 1
    totalEbooks.value = data.total || 0
  } catch (e: any) {
    toast.error('Lỗi tải ebook: ' + (e.message || ''))
  } finally {
    loading.value = false
  }
}

function goPage(p: number) {
  page.value = p
  loadEbooks()
}

function confirmDelete(ebook: Ebook) {
  deleteTarget.value = ebook
}

async function doDelete() {
  if (!deleteTarget.value) return
  try {
    await apiFetch(`/api/admin/ebooks/${deleteTarget.value.id}`, { method: 'DELETE' })
    toast.success('Đã xóa ebook')
    deleteTarget.value = null
    loadEbooks()
  } catch (e: any) {
    toast.error('Lỗi xóa: ' + (e.message || ''))
  }
}

onMounted(loadEbooks)
</script>

<style scoped>
.ebooks-page {
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

/* Ebook cells */
.ebook-cell { display: flex; align-items: center; gap: 12px; }
.ebook-thumb { width: 48px; height: 64px; border-radius: 6px; object-fit: cover; }
.ebook-thumb-placeholder {
  width: 48px; height: 64px; border-radius: 6px; background: #f3f4f6;
  display: flex; align-items: center; justify-content: center; color: #9ca3af;
}
.ebook-title { font-weight: 600; font-size: 14px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ebook-slug { font-size: 12px; color: #9ca3af; }
.text-muted { color: #9ca3af; font-size: 12px; }

/* Badges */
.badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-published { background: #d1fae5; color: #065f46; }
.badge-draft { background: #e0e7ff; color: #3730a3; }
.badge-coming_soon { background: #fef3c7; color: #92400e; }
.badge-archived { background: #f3f4f6; color: #6b7280; }
.badge-ebook { background: #dbeafe; color: #1d4ed8; }
.badge-book { background: #fce7f3; color: #be185d; }
.badge-guide { background: #fef3c7; color: #92400e; }
.badge-free { background: #d1fae5; color: #065f46; }

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
  .filter-actions { margin-left: 0; margin-top: 8px; }
  .stats-row { grid-template-columns: 1fr; }
}
</style>
