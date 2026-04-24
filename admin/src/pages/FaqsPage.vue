<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">FAQ</h1>
      <button
        class="btn"
        :disabled="selectedCourseId === null || selectedCourseId < 0"
        @click="openCreate"
      >
        Tạo mới
      </button>
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
          <label class="muted">Chọn FAQ</label>
          <select
            v-model.number="selectedCourseId"
            class="input"
            style="min-width: 320px"
          >
            <option :value="-1">(Chọn loại FAQ)</option>
            <option :value="0">FAQ chung</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">
              #{{ c.id }} - {{ c.title }}
            </option>
          </select>
          <button
            class="btn btn-secondary"
            :disabled="loadingCourses"
            @click="loadCourses"
          >
            {{ loadingCourses ? "Đang tải…" : "Làm mới khóa học" }}
          </button>
        </div>

        <button
          class="btn"
          :disabled="loadingFaqs || !selectedCourseId"
          @click="loadFaqs"
        >
          {{ loadingFaqs ? "Đang tải…" : "Làm mới" }}
        </button>
      </div>

      <div
        v-if="selectedCourseId === null || selectedCourseId < 0"
        class="muted"
        style="margin-top: 12px"
      >
        Chọn loại FAQ để quản lý.
      </div>

      <table v-else class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th style="width: 90px">ID</th>
            <th>Câu hỏi</th>
            <th style="width: 110px">Thứ tự</th>
            <th style="width: 130px">Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="f in faqs" :key="f.id">
            <td>{{ f.id }}</td>
            <td>
              <div style="font-weight: 600">{{ f.question }}</div>
              <div
                class="muted"
                style="font-size: 12px; white-space: pre-wrap; margin-top: 4px"
              >
                {{ stripHtml(f.answer || "") }}
              </div>
            </td>
            <td>{{ f.order }}</td>
            <td>
              <span :class="f.is_active ? 'badge badge-success' : 'badge'">
                {{ f.is_active ? "Active" : "Hidden" }}
              </span>
            </td>
            <td style="text-align: right; white-space: nowrap">
              <button class="btn btn-secondary" @click="openEdit(f)">
                Sửa
              </button>
              <button class="btn btn-danger" @click="remove(f)">Xóa</button>
            </td>
          </tr>
          <tr v-if="!loadingFaqs && faqs.length === 0">
            <td colspan="5" class="muted">Chưa có FAQ nào</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="modalOpen" class="modal-backdrop" @click.self="closeModal">
      <div class="modal">
        <div
          class="row"
          style="justify-content: space-between; align-items: center"
        >
          <h2 class="h2" style="margin: 0">
            {{ editingId ? `Sửa FAQ #${editingId}` : "Tạo FAQ" }}
          </h2>
          <button class="btn btn-secondary" @click="closeModal">Đóng</button>
        </div>

        <form class="form" style="margin-top: 12px" @submit.prevent="save">
          <label class="label">
            <span>Câu hỏi</span>
            <input v-model="form.question" class="input" required />
          </label>

          <label class="label">
            <span>Trả lời</span>
            <RichTextEditor
              v-model="form.answer"
              placeholder="Nhập nội dung trả lời..."
              :minHeight="180"
            />
          </label>

          <div class="grid">
            <label class="label">
              <span>Thứ tự</span>
              <input
                v-model.number="form.order"
                class="input"
                type="number"
                min="0"
              />
            </label>
            <label class="label">
              <span>Trạng thái</span>
              <select v-model="form.is_active" class="input">
                <option :value="true">Active</option>
                <option :value="false">Hidden</option>
              </select>
            </label>
          </div>

          <button class="btn" :disabled="saving" type="submit">
            {{ saving ? "Đang lưu…" : "Lưu" }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import type { CourseFaq } from "../lib/types";
import RichTextEditor from "../components/RichTextEditor.vue";

type CourseOption = { id: number; title: string };

const courses = ref<CourseOption[]>([]);
const loadingCourses = ref(false);
const loadingFaqs = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();

const selectedCourseId = ref<number>(-1);
const faqs = ref<CourseFaq[]>([]);

const modalOpen = ref(false);
const editingId = ref<number | null>(null);

const form = ref({
  question: "",
  answer: "",
  order: 0,
  is_active: true,
});

function stripHtml(input: string) {
  return input
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function resetForm() {
  form.value = {
    question: "",
    answer: "",
    order: 0,
    is_active: true,
  };
}

function openCreate() {
  if (selectedCourseId.value === null || selectedCourseId.value < 0) return;
  editingId.value = null;
  resetForm();
  modalOpen.value = true;
}

function openEdit(f: CourseFaq) {
  editingId.value = f.id;
  form.value.question = f.question;
  form.value.answer = f.answer;
  form.value.order = f.order ?? 0;
  form.value.is_active = !!f.is_active;
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

async function loadFaqs() {
  if (selectedCourseId.value === null || selectedCourseId.value < 0) {
    faqs.value = [];
    return;
  }
  loadingFaqs.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    qs.set("course_id", String(selectedCourseId.value));
    const res = await apiFetch<{ data: CourseFaq[] }>(
      `/api/admin/faqs?${qs.toString()}`,
    );
    faqs.value = res.data;
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  } finally {
    loadingFaqs.value = false;
  }
}

async function save() {
  if (selectedCourseId.value === null || selectedCourseId.value < 0) return;
  saving.value = true;
  error.value = null;
  try {
    const payload = {
      course_id: selectedCourseId.value === 0 ? null : selectedCourseId.value,
      question: form.value.question.trim(),
      answer: form.value.answer.trim(),
      order: Number.isFinite(form.value.order) ? Number(form.value.order) : 0,
      is_active: !!form.value.is_active,
    };

    if (!editingId.value) {
      await apiFetch(`/api/admin/faqs`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } else {
      await apiFetch(`/api/admin/faqs/${editingId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          question: payload.question,
          answer: payload.answer,
          order: payload.order,
          is_active: payload.is_active,
        }),
      });
    }

    closeModal();
    toastSuccess("Đã lưu FAQ thành công.");
    await loadFaqs();
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

async function remove(f: CourseFaq) {
  if (!confirm(`Xóa FAQ #${f.id}?`)) return;
  error.value = null;
  try {
    await apiFetch(`/api/admin/faqs/${f.id}`, { method: "DELETE" });
    await loadFaqs();
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  }
}

watch(selectedCourseId, () => {
  loadFaqs();
});

onMounted(async () => {
  await loadCourses();
});
</script>
