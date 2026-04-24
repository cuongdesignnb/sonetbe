<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Dịch vụ</h1>
      <RouterLink class="btn" to="/service-posts/new">Tạo mới</RouterLink>
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
          <input
            v-model="search"
            class="input"
            placeholder="Tìm kiếm…"
            style="max-width: 260px"
          />
          <select v-model="status" class="input" style="width: 160px">
            <option value="">Tất cả</option>
            <option value="draft">Bản nháp</option>
            <option value="published">Đã xuất bản</option>
            <option value="archived">Lưu trữ</option>
          </select>
          <select
            v-model.number="categoryId"
            class="input"
            style="min-width: 220px"
          >
            <option :value="0">Tất cả danh mục</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
          <button
            class="btn btn-secondary"
            :disabled="loadingCategories"
            @click="loadCategories"
          >
            {{ loadingCategories ? "Đang tải…" : "Làm mới danh mục" }}
          </button>
        </div>

        <button class="btn" :disabled="loading" @click="load">
          {{ loading ? "Đang tải…" : "Làm mới" }}
        </button>
      </div>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th style="width: 90px">ID</th>
            <th>Tiêu đề</th>
            <th>Danh mục</th>
            <th style="width: 120px">Trạng thái</th>
            <th style="width: 220px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in posts" :key="p.id">
            <td>{{ p.id }}</td>
            <td>
              <div style="font-weight: 600">{{ p.title }}</div>
              <div class="muted" style="font-size: 12px">/{{ p.slug }}</div>
            </td>
            <td>{{ p.category?.name || "(Chưa chọn)" }}</td>
            <td>
              <span
                :class="
                  p.status === 'published' ? 'badge badge-success' : 'badge'
                "
              >
                {{ p.status }}
              </span>
            </td>
            <td style="text-align: right; white-space: nowrap">
              <a
                v-if="p.status === 'published'"
                class="btn btn-secondary"
                :href="frontendUrl + '/dich-vu/' + p.slug"
                target="_blank"
              >
                View
              </a>
              <RouterLink
                class="btn btn-secondary"
                :to="`/service-posts/${p.id}`"
              >
                Sửa
              </RouterLink>
              <button
                class="btn btn-danger"
                style="margin-left: 6px"
                @click="remove(p)"
              >
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && posts.length === 0">
            <td colspan="5" class="muted">Chưa có bài viết dịch vụ nào</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import type { ServiceCategory, ServicePost } from "../lib/types";

const posts = ref<ServicePost[]>([]);
const categories = ref<ServiceCategory[]>([]);

const loading = ref(false);
const loadingCategories = ref(false);
const error = ref<string | null>(null);

const search = ref("");
const status = ref("");
const categoryId = ref(0);

const frontendUrl = (window as any).__FRONTEND_URL || "https://phamanhchien.vn";

async function loadCategories() {
  loadingCategories.value = true;
  error.value = null;
  try {
    const res = await apiFetch<{ data: ServiceCategory[] }>(
      "/api/admin/service-categories",
    );
    categories.value = res.data || [];
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  } finally {
    loadingCategories.value = false;
  }
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (search.value.trim()) qs.set("search", search.value.trim());
    if (status.value) qs.set("status", status.value);
    if (categoryId.value) qs.set("category_id", String(categoryId.value));
    const res = await apiFetch<{ data: ServicePost[] }>(
      `/api/admin/service-posts?${qs.toString()}`,
    );
    posts.value = res.data;
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

async function remove(p: ServicePost) {
  if (!confirm(`Xóa dịch vụ "${p.title}"?`)) return;
  try {
    await apiFetch(`/api/admin/service-posts/${p.id}`, { method: "DELETE" });
    await load();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

onMounted(async () => {
  await loadCategories();
  await load();
});
</script>
