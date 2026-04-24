<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Khóa học</h1>
      <RouterLink class="btn" to="/courses/new">Tạo mới</RouterLink>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>

    <div class="card" style="margin-top: 16px">
      <div
        class="row"
        style="justify-content: space-between; align-items: center"
      >
        <input
          v-model="search"
          class="input"
          placeholder="Tìm kiếm…"
          style="max-width: 320px"
        />
        <button class="btn" :disabled="loading" @click="load">
          {{ loading ? "Đang tải…" : "Làm mới" }}
        </button>
      </div>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th style="width: 60px">Thứ tự</th>
            <th style="width: 60px">Ảnh</th>
            <th>Tiêu đề</th>
            <th>Trạng thái</th>
            <th>Giá</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in courses" :key="c.id">
            <td>
              <input
                v-model.number="c.sort_order"
                type="number"
                min="0"
                class="input"
                style="width: 60px; text-align: center; padding: 4px"
                @change="updateSortOrder(c)"
              />
            </td>
            <td>
              <img
                v-if="c.thumbnail"
                :src="c.thumbnail"
                alt=""
                style="
                  width: 48px;
                  height: 36px;
                  object-fit: cover;
                  border-radius: 4px;
                  background: #f3f4f6;
                "
              />
              <div
                v-else
                style="
                  width: 48px;
                  height: 36px;
                  background: #e5e7eb;
                  border-radius: 4px;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                  color: #9ca3af;
                  font-size: 10px;
                "
              >
                N/A
              </div>
            </td>
            <td>{{ c.title }}</td>
            <td>
              <span :class="['status-badge', `status-${c.status}`]">{{
                c.status
              }}</span>
            </td>
            <td>
              {{
                Number(c.price) === 0
                  ? "Miễn phí"
                  : Number(c.price).toLocaleString("vi-VN") + "đ"
              }}
            </td>
            <td style="text-align: right; white-space: nowrap">
              <a
                class="btn btn-secondary btn-sm"
                :href="getFrontendUrl(c)"
                target="_blank"
                title="Xem trên website"
                style="margin-right: 6px"
                >👁</a
              >
              <RouterLink
                class="btn btn-secondary btn-sm"
                :to="`/courses/${c.id}`"
                >Sửa</RouterLink
              >
              <button
                class="btn btn-secondary btn-sm"
                style="margin-left: 6px"
                :disabled="duplicating === c.id"
                @click="duplicateCourse(c)"
              >
                {{ duplicating === c.id ? "..." : "Nhân bản" }}
              </button>
              <button
                class="btn btn-danger btn-sm"
                style="margin-left: 6px"
                @click="deleteCourse(c)"
              >
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && courses.length === 0">
            <td colspan="6" class="muted">Chưa có khóa học nào</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import type { Course } from "../lib/types";

const courses = ref<Course[]>([]);

function getFrontendUrl(c: Course): string {
  const origin = window.location.origin;
  // admin.example.com → example.com
  const frontendOrigin = origin.replace(/\/\/admin\./, "//");
  return `${frontendOrigin}/courses/${c.slug || c.id}`;
}
const loading = ref(false);
const error = ref<string | null>(null);
const search = ref("");
const duplicating = ref<number | null>(null);

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (search.value.trim()) qs.set("search", search.value.trim());
    const res = await apiFetch<{ data: Course[] }>(
      `/api/admin/courses?${qs.toString()}`,
    );
    courses.value = res.data;
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

watch(search, () => {
  // keep it simple: no debounce, user can click Refresh
});

async function updateSortOrder(c: Course) {
  try {
    await apiFetch("/api/admin/courses/reorder", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        orders: [{ id: c.id, sort_order: c.sort_order }],
      }),
    });
  } catch (e) {
    alert(extractMessage(e));
  }
}

async function duplicateCourse(c: Course) {
  if (!confirm(`Nhân bản khóa học "${c.title}"?`)) return;
  duplicating.value = c.id;
  try {
    await apiFetch(`/api/admin/courses/${c.id}/duplicate`, { method: "POST" });
    await load();
  } catch (e) {
    alert(extractMessage(e));
  } finally {
    duplicating.value = null;
  }
}

async function deleteCourse(c: Course) {
  if (!confirm(`Xóa khóa học "${c.title}"? Hành động này không thể hoàn tác.`))
    return;
  try {
    await apiFetch(`/api/admin/courses/${c.id}`, { method: "DELETE" });
    await load();
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  }
}

onMounted(load);
</script>
