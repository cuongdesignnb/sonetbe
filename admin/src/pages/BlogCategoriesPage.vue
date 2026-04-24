<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Danh mục tin tức</h1>
      <button class="btn" @click="openCreate">Tạo mới</button>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>

    <div class="card" style="margin-top: 16px">
      <div
        class="row"
        style="justify-content: space-between; align-items: center"
      >
        <div class="row" style="gap: 10px; align-items: center">
          <label class="muted">Trạng thái</label>
          <select v-model="filterActive" class="input" style="width: 180px">
            <option :value="''">Tất cả</option>
            <option :value="'true'">Đang hoạt động</option>
            <option :value="'false'">Tạm ẩn</option>
          </select>
        </div>
        <button class="btn" :disabled="loading" @click="load">
          {{ loading ? "Đang tải…" : "Làm mới" }}
        </button>
      </div>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th style="width: 90px">ID</th>
            <th>Tên</th>
            <th>Slug</th>
            <th style="width: 130px">Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in categories" :key="c.id">
            <td>{{ c.id }}</td>
            <td>
              <div style="font-weight: 600">{{ c.name }}</div>
              <div class="muted" style="font-size: 12px">
                {{ stripHtml(c.description || "") }}
              </div>
            </td>
            <td class="muted">{{ c.slug }}</td>
            <td>
              <span :class="c.is_active ? 'badge badge-success' : 'badge'">
                {{ c.is_active ? "Active" : "Hidden" }}
              </span>
            </td>
            <td style="text-align: right; white-space: nowrap">
              <button class="btn btn-secondary" @click="openEdit(c)">
                Sửa
              </button>
              <button class="btn btn-danger" @click="remove(c)">Xóa</button>
            </td>
          </tr>
          <tr v-if="!loading && categories.length === 0">
            <td colspan="5" class="muted">Chưa có danh mục nào</td>
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
            {{ editingId ? `Sửa danh mục #${editingId}` : "Tạo danh mục" }}
          </h2>
          <button class="btn btn-secondary" @click="closeModal">Đóng</button>
        </div>

        <form class="form" style="margin-top: 12px" @submit.prevent="save">
          <label class="label">
            <span>Tên</span>
            <input v-model="form.name" class="input" required />
          </label>

          <label class="label">
            <span>Slug (để trống sẽ tự tạo)</span>
            <input v-model="form.slug" class="input" placeholder="tin-tuc" />
          </label>

          <label class="label">
            <span>Mô tả</span>
            <RichTextEditor
              v-model="form.description"
              placeholder="Mô tả danh mục tin tức..."
              :minHeight="160"
            />
          </label>

          <label class="label">
            <span>Trạng thái</span>
            <select v-model="form.is_active" class="input">
              <option :value="true">Active</option>
              <option :value="false">Hidden</option>
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
import { onMounted, ref, watch } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import type { BlogCategory } from "../lib/types";
import RichTextEditor from "../components/RichTextEditor.vue";

const categories = ref<BlogCategory[]>([]);
const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();

const filterActive = ref<"" | "true" | "false">("");

const modalOpen = ref(false);
const editingId = ref<number | null>(null);

const form = ref({
  name: "",
  slug: "",
  description: "",
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
    name: "",
    slug: "",
    description: "",
    is_active: true,
  };
}

function openCreate() {
  editingId.value = null;
  resetForm();
  modalOpen.value = true;
}

function openEdit(c: BlogCategory) {
  editingId.value = c.id;
  form.value.name = c.name;
  form.value.slug = c.slug;
  form.value.description = c.description || "";
  form.value.is_active = !!c.is_active;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (filterActive.value !== "") qs.set("active", filterActive.value);
    const res = await apiFetch<{ data: BlogCategory[] }>(
      `/api/admin/blog-categories?${qs.toString()}`,
    );
    categories.value = res.data;
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
  saving.value = true;
  error.value = null;
  try {
    const payload = {
      name: form.value.name,
      slug: form.value.slug?.trim() || null,
      description: form.value.description?.trim() || null,
      is_active: form.value.is_active,
    };

    if (!editingId.value) {
      await apiFetch(`/api/admin/blog-categories`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } else {
      await apiFetch(`/api/admin/blog-categories/${editingId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    }

    closeModal();
    toastSuccess("Đã lưu danh mục tin tức.");
    await load();
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

async function remove(c: BlogCategory) {
  if (!confirm(`Xóa danh mục "${c.name}"?`)) return;
  try {
    await apiFetch(`/api/admin/blog-categories/${c.id}`, { method: "DELETE" });
    await load();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

watch(filterActive, () => {
  load();
});

onMounted(load);
</script>
