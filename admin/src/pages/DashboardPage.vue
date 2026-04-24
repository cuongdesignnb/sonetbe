<template>
  <div class="dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">
          <span class="wave">👋</span> Chào mừng trở lại!
        </h1>
        <p class="dashboard-subtitle">
          Theo dõi hiệu suất và quản lý hệ thống của bạn
        </p>
      </div>
      <button class="btn btn-refresh" :disabled="loading" @click="loadStats">
        <svg v-if="!loading" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
        </svg>
        <svg v-else class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="20"/>
        </svg>
        {{ loading ? 'Đang tải...' : 'Làm mới' }}
      </button>
    </div>

    <div v-if="error" class="alert" style="margin-bottom: 24px">{{ error }}</div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card stat-card-primary">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.overview.total_courses }}</div>
          <div class="stat-label">Tổng khóa học</div>
          <div class="stat-sub">{{ stats.overview.published_courses }} đã xuất bản</div>
        </div>
      </div>

      <div class="stat-card stat-card-success">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.overview.total_users }}</div>
          <div class="stat-label">Người dùng</div>
          <div class="stat-sub">{{ stats.overview.total_students }} học viên</div>
        </div>
      </div>

      <div class="stat-card stat-card-warning">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ stats.overview.total_enrollments }}</div>
          <div class="stat-label">Đăng ký học</div>
          <div class="stat-sub">{{ stats.overview.active_enrollments }} đang học</div>
        </div>
      </div>

      <div class="stat-card stat-card-danger">
        <div class="stat-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-value">{{ formatCurrency(stats.overview.total_revenue) }}</div>
          <div class="stat-label">Doanh thu</div>
          <div class="stat-sub">{{ stats.overview.pending_payments }} chờ thanh toán</div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-grid">
      <!-- Revenue Chart -->
      <div class="chart-card chart-card-large">
        <div class="chart-header">
          <h3 class="chart-title">📈 Doanh thu 12 tháng gần nhất</h3>
        </div>
        <div class="chart-body">
          <Line v-if="revenueChartData" :data="revenueChartData" :options="revenueChartOptions" />
        </div>
      </div>

      <!-- Enrollments Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <h3 class="chart-title">📊 Đăng ký học theo tháng</h3>
        </div>
        <div class="chart-body">
          <Bar v-if="enrollmentsChartData" :data="enrollmentsChartData" :options="barChartOptions" />
        </div>
      </div>

      <!-- Users Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <h3 class="chart-title">👥 Người dùng mới theo tháng</h3>
        </div>
        <div class="chart-body">
          <Line v-if="usersChartData" :data="usersChartData" :options="lineChartOptions" />
        </div>
      </div>
    </div>

    <!-- Bottom Section -->
    <div class="bottom-grid">
      <!-- Top Courses -->
      <div class="list-card">
        <div class="list-header">
          <h3 class="list-title">🏆 Top khóa học</h3>
        </div>
        <div class="list-body">
          <div v-for="(course, index) in stats.top_courses" :key="course.id" class="list-item">
            <div class="list-rank" :class="`rank-${index + 1}`">{{ index + 1 }}</div>
            <div class="list-info">
              <div class="list-name">{{ course.title }}</div>
              <div class="list-meta">{{ course.enrollments }} đăng ký</div>
            </div>
            <div class="list-badge">
              <span class="badge badge-primary">{{ course.enrollments }}</span>
            </div>
          </div>
          <div v-if="stats.top_courses.length === 0" class="list-empty">
            Chưa có dữ liệu
          </div>
        </div>
      </div>

      <!-- Distribution Charts -->
      <div class="pie-charts-grid">
        <div class="chart-card chart-card-small">
          <div class="chart-header">
            <h3 class="chart-title">📋 Trạng thái đăng ký</h3>
          </div>
          <div class="chart-body chart-body-pie">
            <Doughnut v-if="enrollmentStatusChartData" :data="enrollmentStatusChartData" :options="doughnutOptions" />
          </div>
        </div>

        <div class="chart-card chart-card-small">
          <div class="chart-header">
            <h3 class="chart-title">💳 Trạng thái thanh toán</h3>
          </div>
          <div class="chart-body chart-body-pie">
            <Doughnut v-if="paymentStatusChartData" :data="paymentStatusChartData" :options="doughnutOptions" />
          </div>
        </div>
      </div>

      <!-- Recent Enrollments -->
      <div class="list-card list-card-wide">
        <div class="list-header">
          <h3 class="list-title">🕐 Đăng ký gần đây</h3>
          <RouterLink class="list-link" to="/enrollments">Xem tất cả →</RouterLink>
        </div>
        <div class="list-body">
          <table class="mini-table">
            <thead>
              <tr>
                <th>Học viên</th>
                <th>Khóa học</th>
                <th>Trạng thái</th>
                <th>Thời gian</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="enrollment in stats.recent_enrollments" :key="enrollment.id">
                <td class="cell-user">{{ enrollment.user || '--' }}</td>
                <td class="cell-course">{{ enrollment.course || '--' }}</td>
                <td>
                  <span :class="getStatusClass(enrollment.status)">
                    {{ getStatusLabel(enrollment.status) }}
                  </span>
                </td>
                <td class="cell-time">{{ formatDate(enrollment.created_at) }}</td>
              </tr>
              <tr v-if="stats.recent_enrollments.length === 0">
                <td colspan="4" class="list-empty">Chưa có đăng ký nào</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links">
      <h3 class="quick-links-title">⚡ Truy cập nhanh</h3>
      <div class="quick-links-grid">
        <RouterLink class="quick-link" to="/courses">
          <div class="quick-link-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
          </div>
          <span>Khóa học</span>
        </RouterLink>

        <RouterLink class="quick-link" to="/enrollments">
          <div class="quick-link-icon" style="background: linear-gradient(135deg, #10b981, #059669)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 12h6m-6 4h6m-8 4h10a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
          </div>
          <span>Đơn đăng ký</span>
        </RouterLink>

        <RouterLink class="quick-link" to="/users">
          <div class="quick-link-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <span>Người dùng</span>
        </RouterLink>

        <RouterLink class="quick-link" to="/media">
          <div class="quick-link-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
            </svg>
          </div>
          <span>Thư viện</span>
        </RouterLink>

        <RouterLink class="quick-link" to="/settings">
          <div class="quick-link-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed)">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
          </div>
          <span>Cài đặt</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  type ChartData,
  type ChartOptions,
} from "chart.js";
import { Line, Bar, Doughnut } from "vue-chartjs";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

type DashboardStats = {
  overview: {
    total_courses: number;
    published_courses: number;
    total_users: number;
    total_students: number;
    total_enrollments: number;
    active_enrollments: number;
    total_revenue: number;
    pending_payments: number;
  };
  charts: {
    months: string[];
    revenue: number[];
    enrollments: number[];
    users: number[];
  };
  top_courses: { id: number; title: string; enrollments: number }[];
  recent_enrollments: {
    id: number;
    user: string | null;
    course: string | null;
    status: string;
    created_at: string;
  }[];
  enrollment_status: Record<string, number>;
  payment_status: Record<string, number>;
};

const loading = ref(false);
const error = ref<string | null>(null);
const stats = ref<DashboardStats>({
  overview: {
    total_courses: 0,
    published_courses: 0,
    total_users: 0,
    total_students: 0,
    total_enrollments: 0,
    active_enrollments: 0,
    total_revenue: 0,
    pending_payments: 0,
  },
  charts: { months: [], revenue: [], enrollments: [], users: [] },
  top_courses: [],
  recent_enrollments: [],
  enrollment_status: {},
  payment_status: {},
});

function formatCurrency(value: number) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
    maximumFractionDigits: 0,
  }).format(value || 0);
}

function formatDate(value?: string) {
  if (!value) return "--";
  try {
    return new Date(value).toLocaleDateString("vi-VN", {
      day: "2-digit",
      month: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return value;
  }
}

function getStatusLabel(status: string) {
  const labels: Record<string, string> = {
    pending: "Chờ",
    active: "Đang học",
    completed: "Hoàn thành",
  };
  return labels[status] || status;
}

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    pending: "badge badge-warning",
    active: "badge badge-success",
    completed: "badge badge-primary",
  };
  return classes[status] || "badge";
}

const revenueChartData = computed<ChartData<"line"> | null>(() => {
  if (!stats.value.charts.months.length) return null;
  return {
    labels: stats.value.charts.months,
    datasets: [
      {
        label: "Doanh thu (VNĐ)",
        data: stats.value.charts.revenue,
        borderColor: "#6366f1",
        backgroundColor: "rgba(99, 102, 241, 0.1)",
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: "#6366f1",
      },
    ],
  };
});

const revenueChartOptions: ChartOptions<"line"> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "#1e293b",
      titleColor: "#fff",
      bodyColor: "#fff",
      cornerRadius: 8,
      padding: 12,
      callbacks: {
        label: (ctx: any) =>
          new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
            maximumFractionDigits: 0,
          }).format(ctx.raw),
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: "#64748b" },
    },
    y: {
      grid: { color: "rgba(0,0,0,0.05)" },
      ticks: {
        color: "#64748b",
        // Keep ticks readable for large numbers
        callback: (rawValue: string | number) => {
          const v = typeof rawValue === "string" ? Number(rawValue) : rawValue;
          if (Number.isNaN(v)) return rawValue;
          if (v >= 1_000_000) return `${(v / 1_000_000).toFixed(0)}M`;
          if (v >= 1_000) return `${(v / 1_000).toFixed(0)}K`;
          return v;
        },
      },
    },
  },
};

const enrollmentsChartData = computed<ChartData<"bar"> | null>(() => {
  if (!stats.value.charts.months.length) return null;
  return {
    labels: stats.value.charts.months,
    datasets: [
      {
        label: "Đăng ký",
        data: stats.value.charts.enrollments,
        backgroundColor: "rgba(16, 185, 129, 0.8)",
        borderRadius: 8,
        borderSkipped: false,
      },
    ],
  };
});

const usersChartData = computed<ChartData<"line"> | null>(() => {
  if (!stats.value.charts.months.length) return null;
  return {
    labels: stats.value.charts.months,
    datasets: [
      {
        label: "Người dùng mới",
        data: stats.value.charts.users,
        borderColor: "#f59e0b",
        backgroundColor: "rgba(245, 158, 11, 0.1)",
        fill: true,
        tension: 0.4,
        pointRadius: 3,
        pointHoverRadius: 5,
        pointBackgroundColor: "#f59e0b",
      },
    ],
  };
});

const barChartOptions: ChartOptions<"bar"> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "#1e293b",
      cornerRadius: 8,
      padding: 12,
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: "#64748b" },
    },
    y: {
      grid: { color: "rgba(0,0,0,0.05)" },
      ticks: { color: "#64748b", stepSize: 1 },
      beginAtZero: true,
    },
  },
};

const lineChartOptions: ChartOptions<"line"> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: "#1e293b",
      cornerRadius: 8,
      padding: 12,
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: "#64748b" },
    },
    y: {
      grid: { color: "rgba(0,0,0,0.05)" },
      ticks: { color: "#64748b", stepSize: 1 },
      beginAtZero: true,
    },
  },
};

const enrollmentStatusChartData = computed<ChartData<"doughnut"> | null>(() => {
  const data = stats.value.enrollment_status;
  if (!Object.keys(data).length) return null;
  const labels = Object.keys(data).map((k) => getStatusLabel(k));
  const values = Object.values(data);
  return {
    labels,
    datasets: [
      {
        data: values,
        backgroundColor: ["#f59e0b", "#10b981", "#6366f1", "#ef4444"],
        borderWidth: 0,
      },
    ],
  };
});

const paymentStatusChartData = computed<ChartData<"doughnut"> | null>(() => {
  const data = stats.value.payment_status;
  if (!Object.keys(data).length) return null;
  const labelMap: Record<string, string> = {
    pending: "Chờ TT",
    paid: "Đã TT",
    free: "Miễn phí",
  };
  const labels = Object.keys(data).map((k) => labelMap[k] || k);
  const values = Object.values(data);
  return {
    labels,
    datasets: [
      {
        data: values,
        backgroundColor: ["#f59e0b", "#10b981", "#8b5cf6"],
        borderWidth: 0,
      },
    ],
  };
});

const doughnutOptions: ChartOptions<"doughnut"> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "bottom" as const,
      labels: {
        padding: 16,
        usePointStyle: true,
        pointStyle: "circle",
        color: "#64748b",
      },
    },
    tooltip: {
      backgroundColor: "#1e293b",
      cornerRadius: 8,
      padding: 12,
    },
  },
  cutout: "65%",
};

async function loadStats() {
  loading.value = true;
  error.value = null;
  try {
    const res = await apiFetch<DashboardStats>("/api/admin/dashboard/stats");
    stats.value = res;
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

onMounted(loadStats);
</script>

<style scoped>
.dashboard {
  max-width: 1600px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 32px;
}

.dashboard-title {
  margin: 0;
  font-size: 32px;
  font-weight: 800;
  color: var(--gray-900);
  display: flex;
  align-items: center;
  gap: 12px;
}

.wave {
  display: inline-block;
  animation: wave 2s ease-in-out infinite;
}

@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(20deg); }
  75% { transform: rotate(-10deg); }
}

.dashboard-subtitle {
  margin: 8px 0 0;
  color: var(--gray-500);
  font-size: 16px;
}

.btn-refresh {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.btn-refresh:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 28px;
}

.stat-card {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  display: flex;
  align-items: flex-start;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.stat-card-primary::before { background: linear-gradient(90deg, #6366f1, #8b5cf6); }
.stat-card-success::before { background: linear-gradient(90deg, #10b981, #059669); }
.stat-card-warning::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
.stat-card-danger::before { background: linear-gradient(90deg, #ef4444, #dc2626); }

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-card-primary .stat-icon { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
.stat-card-success .stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.stat-card-warning .stat-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.stat-card-danger .stat-icon { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--gray-900);
  line-height: 1.2;
}

.stat-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--gray-600);
  margin-top: 4px;
}

.stat-sub {
  font-size: 12px;
  color: var(--gray-400);
  margin-top: 4px;
}

/* Charts Grid */
.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 20px;
  margin-bottom: 28px;
}

.chart-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  overflow: hidden;
}

.chart-card-large {
  grid-column: span 1;
}

.chart-header {
  padding: 20px 24px 0;
}

.chart-title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--gray-800);
}

.chart-body {
  padding: 20px 24px 24px;
  height: 280px;
}

.chart-body-pie {
  height: 220px;
}

/* Bottom Grid */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 2fr;
  gap: 20px;
  margin-bottom: 28px;
}

.pie-charts-grid {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.chart-card-small {
  flex: 1;
}

.chart-card-small .chart-body {
  height: 160px;
}

/* List Card */
.list-card {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  overflow: hidden;
}

.list-card-wide {
  grid-column: span 1;
}

.list-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--gray-100);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.list-title {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  color: var(--gray-800);
}

.list-link {
  font-size: 13px;
  font-weight: 600;
  color: var(--primary);
  text-decoration: none;
}

.list-link:hover {
  text-decoration: underline;
}

.list-body {
  padding: 12px 24px 20px;
}

.list-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--gray-50);
}

.list-item:last-child {
  border-bottom: none;
}

.list-rank {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 12px;
  background: var(--gray-100);
  color: var(--gray-600);
}

.rank-1 { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #fff; }
.rank-2 { background: linear-gradient(135deg, #9ca3af, #6b7280); color: #fff; }
.rank-3 { background: linear-gradient(135deg, #d97706, #b45309); color: #fff; }

.list-info {
  flex: 1;
  min-width: 0;
}

.list-name {
  font-weight: 600;
  color: var(--gray-800);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.list-meta {
  font-size: 12px;
  color: var(--gray-400);
  margin-top: 2px;
}

.list-empty {
  text-align: center;
  color: var(--gray-400);
  padding: 20px;
}

/* Mini Table */
.mini-table {
  width: 100%;
  border-collapse: collapse;
}

.mini-table th,
.mini-table td {
  padding: 10px 8px;
  text-align: left;
  font-size: 13px;
}

.mini-table th {
  font-weight: 600;
  color: var(--gray-500);
  border-bottom: 1px solid var(--gray-100);
}

.mini-table td {
  color: var(--gray-700);
  border-bottom: 1px solid var(--gray-50);
}

.mini-table tr:last-child td {
  border-bottom: none;
}

.cell-user {
  font-weight: 600;
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cell-course {
  max-width: 180px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.cell-time {
  color: var(--gray-400);
  font-size: 12px;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  background: var(--gray-100);
  color: var(--gray-600);
}

.badge-primary {
  background: rgba(99, 102, 241, 0.1);
  color: #6366f1;
}

.badge-success {
  background: rgba(16, 185, 129, 0.1);
  color: #059669;
}

.badge-warning {
  background: rgba(245, 158, 11, 0.1);
  color: #d97706;
}

/* Quick Links */
.quick-links {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.quick-links-title {
  margin: 0 0 20px;
  font-size: 16px;
  font-weight: 700;
  color: var(--gray-800);
}

.quick-links-grid {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.quick-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  background: var(--gray-50);
  border-radius: 12px;
  text-decoration: none;
  color: var(--gray-700);
  font-weight: 600;
  transition: all 0.2s;
}

.quick-link:hover {
  background: var(--gray-100);
  transform: translateY(-2px);
}

.quick-link-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

/* Responsive */
@media (max-width: 1400px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .charts-grid {
    grid-template-columns: 1fr 1fr;
  }
  .chart-card-large {
    grid-column: span 2;
  }
  .bottom-grid {
    grid-template-columns: 1fr 1fr;
  }
  .list-card-wide {
    grid-column: span 2;
  }
}

@media (max-width: 900px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .charts-grid {
    grid-template-columns: 1fr;
  }
  .chart-card-large {
    grid-column: span 1;
  }
  .bottom-grid {
    grid-template-columns: 1fr;
  }
  .list-card-wide {
    grid-column: span 1;
  }
  .pie-charts-grid {
    flex-direction: row;
  }
}
</style>
