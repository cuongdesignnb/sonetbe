<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Đánh giá</h1>
      <button class="btn" :disabled="loading" @click="loadReviews">
        {{ loading ? "Đang tải…" : "Làm mới" }}
      </button>
    </div>

    <div v-if="error" class="alert" style="margin-top: 12px">{{ error }}</div>

    <div class="card" style="margin-top: 16px">
      <div
        class="row"
        style="justify-content: space-between; align-items: center"
      >
        <div
          class="row"
          style="gap: 10px; align-items: center; flex-wrap: wrap"
        >
          <label class="muted">Khóa học</label>
          <select
            v-model.number="selectedCourseId"
            class="input"
            style="min-width: 320px"
          >
            <option :value="0">(Tất cả)</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">
              #{{ c.id }} - {{ c.title }}
            </option>
          </select>
          <label class="muted">Trạng thái</label>
          <select v-model="statusFilter" class="input" style="min-width: 160px">
            <option value="all">Tất cả</option>
            <option value="approved">Đã duyệt</option>
            <option value="pending">Chờ duyệt</option>
          </select>
          <button
            class="btn btn-secondary"
            :disabled="loadingCourses"
            @click="loadCourses"
          >
            {{ loadingCourses ? "Đang tải…" : "Làm mới khóa học" }}
          </button>
        </div>
      </div>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th style="width: 70px">ID</th>
            <th style="width: 260px">Khóa học</th>
            <th>Người đánh giá</th>
            <th style="width: 110px">Sao</th>
            <th>Nhận xét</th>
            <th style="width: 120px">Trạng thái</th>
            <th style="width: 160px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in reviews" :key="r.id">
            <td>{{ r.id }}</td>
            <td>
              <div style="font-weight: 600">{{ r.course?.title || "--" }}</div>
              <div class="muted" style="font-size: 12px">
                #{{ r.course_id }}
              </div>
            </td>
            <td>
              <div style="font-weight: 600">
                {{ r.user?.name || r.reviewer_name || "Ẩn danh" }}
              </div>
              <div class="muted" style="font-size: 12px">
                {{ r.user ? "Tài khoản" : "Khách" }}
              </div>
            </td>
            <td>
              <span class="badge">{{ r.rating }} ★</span>
            </td>
            <td style="max-width: 420px">
              <div style="white-space: pre-wrap">{{ r.comment }}</div>
              <div class="muted" style="font-size: 12px; margin-top: 6px">
                {{ formatDate(r.created_at) }}
              </div>
            </td>
            <td>
              <span :class="r.is_approved ? 'badge badge-success' : 'badge'">
                {{ r.is_approved ? "Đã duyệt" : "Chờ duyệt" }}
              </span>
            </td>
            <td style="text-align: right; white-space: nowrap">
              <button class="btn btn-secondary" @click="openEdit(r)">
                Sửa
              </button>
              <button
                v-if="!r.is_approved"
                class="btn"
                style="margin-left: 6px"
                @click="approve(r)"
              >
                Duyệt
              </button>
              <button
                class="btn btn-danger"
                style="margin-left: 6px"
                @click="remove(r)"
              >
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && reviews.length === 0">
            <td colspan="7" class="muted">Chưa có đánh giá nào</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="modalOpen" class="modal-backdrop" @click.self="closeModal">
      <div class="modal" style="max-width: 720px">
        <div
          class="row"
          style="justify-content: space-between; align-items: center"
        >
          <h2 class="h2" style="margin: 0">Sửa đánh giá #{{ editingId }}</h2>
          <button class="btn btn-secondary" @click="closeModal">Đóng</button>
        </div>

        <form class="form" style="margin-top: 12px" @submit.prevent="save">
          <div class="grid">
            <label class="label">
              <span>Người đánh giá</span>
              <input
                v-model="form.reviewer_name"
                class="input"
                placeholder="Tên hiển thị"
              />
            </label>
            <label class="label">
              <span>Sao</span>
              <select v-model.number="form.rating" class="input">
                <option v-for="r in [1, 2, 3, 4, 5]" :key="r" :value="r">
                  {{ r }}
                </option>
              </select>
            </label>
          </div>

          <label class="label">
            <span>Nội dung</span>
            <textarea
              v-model="form.comment"
              class="textarea"
              rows="5"
              required
            />
          </label>

          <label class="label">
            <span>Trạng thái</span>
            <select v-model="form.is_approved" class="input">
              <option :value="true">Đã duyệt</option>
              <option :value="false">Chờ duyệt</option>
            </select>
          </label>

          <button class="btn" :disabled="saving" type="submit">
            {{ saving ? "Đang lưu…" : "Lưu" }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import type { Review } from "../lib/types";

type CourseOption = { id: number; title: string };

const reviews = ref<Review[]>([]);
const courses = ref<CourseOption[]>([]);
const selectedCourseId = ref(0);
const statusFilter = ref<"all" | "approved" | "pending">("all");

const loading = ref(false);
const loadingCourses = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();

const modalOpen = ref(false);
const editingId = ref<number | null>(null);

const form = ref({
  reviewer_name: "",
  rating: 5,
  comment: "",
  is_approved: false,
});

function formatDate(value?: string | null) {
  if (!value) return "";
  try {
    return new Date(value).toLocaleString();
  } catch {
    return value;
  }
}

function resetForm() {
  form.value = {
    reviewer_name: "",
    rating: 5,
    comment: "",
    is_approved: false,
  };
}

function openEdit(r: Review) {
  editingId.value = r.id;
  form.value.reviewer_name = r.reviewer_name || r.user?.name || "";
  form.value.rating = r.rating || 5;
  form.value.comment = r.comment || "";
  form.value.is_approved = !!r.is_approved;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
}

async function loadCourses() {
  loadingCourses.value = true;
  error.value = null;
  try {
    const res = await apiFetch<{ data: CourseOption[] }>(
      "/api/admin/courses/options",
    );
    courses.value = res.data;
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

async function loadReviews() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (selectedCourseId.value)
      qs.set("course_id", String(selectedCourseId.value));
    if (statusFilter.value !== "all") qs.set("status", statusFilter.value);
    const res = await apiFetch<{ data: Review[] }>(
      `/api/admin/reviews?${qs.toString()}`,
    );
    reviews.value = res.data || [];
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
  if (!editingId.value) return;
  saving.value = true;
  error.value = null;
  try {
    const payload = {
      reviewer_name: form.value.reviewer_name || null,
      rating: Number(form.value.rating || 5),
      comment: String(form.value.comment || "").trim(),
      is_approved: !!form.value.is_approved,
    };
    await apiFetch(`/api/admin/reviews/${editingId.value}`, {
      method: "PUT",
      body: JSON.stringify(payload),
    });
    modalOpen.value = false;
    toastSuccess("Đã lưu đánh giá thành công.");
    await loadReviews();
  } catch (e) {
    error.value = extractMessage(e);
  } finally {
    saving.value = false;
  }
}

async function approve(r: Review) {
  try {
    await apiFetch(`/api/admin/reviews/${r.id}`, {
      method: "PUT",
      body: JSON.stringify({ is_approved: true }),
    });
    toastSuccess("Đã duyệt đánh giá.");
    await loadReviews();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function remove(r: Review) {
  if (!confirm(`Xóa đánh giá #${r.id}?`)) return;
  try {
    await apiFetch(`/api/admin/reviews/${r.id}`, { method: "DELETE" });
    reviews.value = reviews.value.filter((x) => x.id !== r.id);
  } catch (e) {
    error.value = extractMessage(e);
  }
}

onMounted(async () => {
  resetForm();
  await loadCourses();
  await loadReviews();
});
</script>
