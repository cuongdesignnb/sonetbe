<template>
  <div>
    <div class="row" style="justify-content: space-between; align-items: center">
      <h1 class="h1">Bài viết</h1>
      <RouterLink class="btn" to="/blog-posts/new">Tạo mới</RouterLink>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>

    <div class="card" style="margin-top: 16px">
      <div class="row" style="justify-content: space-between; align-items: center">
        <div class="row" style="gap: 10px; align-items: center; flex-wrap: wrap">
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
          <select v-model.number="categoryId" class="input" style="min-width: 220px">
            <option :value="0">Tất cả danh mục</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
          <button class="btn btn-secondary" :disabled="loadingCategories" @click="loadCategories">
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
            <th style="width: 160px"></th>
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
              <span :class="p.status === 'published' ? 'badge badge-success' : 'badge'">
                {{ p.status }}
              </span>
            </td>
            <td style="text-align: right">
              <RouterLink class="btn btn-secondary" :to="`/blog-posts/${p.id}`">
                Sửa
              </RouterLink>
              <button class="btn btn-danger" style="margin-left: 6px" @click="remove(p)">
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="!loading && posts.length === 0">
            <td colspan="5" class="muted">Chưa có bài viết nào</td>
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
import type { BlogCategory, BlogPost } from "../lib/types";

const posts = ref<BlogPost[]>([]);
const categories = ref<BlogCategory[]>([]);

const loading = ref(false);
const loadingCategories = ref(false);
const error = ref<string | null>(null);

const search = ref("");
const status = ref("");
const categoryId = ref(0);

async function loadCategories() {
  loadingCategories.value = true;
  error.value = null;
  try {
    const res = await apiFetch<{ data: BlogCategory[] }>(
      "/api/admin/blog-categories"
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
    const res = await apiFetch<{ data: BlogPost[] }>(
      `/api/admin/blog-posts?${qs.toString()}`
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

async function remove(p: BlogPost) {
  if (!confirm(`Xóa bài viết "${p.title}"?`)) return;
  try {
    await apiFetch(`/api/admin/blog-posts/${p.id}`, { method: "DELETE" });
    await load();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

watch([search, status, categoryId], () => {
  // user can refresh manually
});

onMounted(async () => {
  await loadCategories();
  await load();
});
</script>
