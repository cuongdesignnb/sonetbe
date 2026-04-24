<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Danh mục</h1>
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
            <th style="width: 90px">Ảnh</th>
            <th>Tên</th>
            <th>Slug</th>
            <th style="width: 130px">Khóa học</th>
            <th style="width: 130px">Trạng thái</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in categories" :key="c.id">
            <td>{{ c.id }}</td>
            <td>
              <div
                style="
                  width: 64px;
                  height: 40px;
                  border-radius: 8px;
                  overflow: hidden;
                  background: #f3f4f6;
                  display: flex;
                  align-items: center;
                  justify-content: center;
                "
              >
                <img
                  v-if="c.image"
                  :src="c.image"
                  alt="Category"
                  style="width: 100%; height: 100%; object-fit: cover"
                />
                <span v-else class="muted" style="font-size: 11px"
                  >No image</span
                >
              </div>
            </td>
            <td>
              <div style="font-weight: 600">{{ c.name }}</div>
              <div v-if="c.parent" class="muted" style="font-size: 12px">
                Parent: {{ c.parent.name }}
              </div>
            </td>
            <td class="muted">{{ c.slug }}</td>
            <td>{{ c.courses_count ?? 0 }}</td>
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
            <td colspan="6" class="muted">Chưa có danh mục nào</td>
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

        <form
          class="form modal-body"
          style="margin-top: 12px"
          @submit.prevent="save"
        >
          <label class="label">
            <span>Tên</span>
            <input v-model="form.name" class="input" required />
          </label>

          <label class="label">
            <span>Slug (để trống sẽ tự tạo)</span>
            <input
              v-model="form.slug"
              class="input"
              placeholder="lap-trinh-web"
            />
          </label>

          <label class="label">
            <span>Mô tả</span>
            <RichTextEditor
              v-model="form.description"
              placeholder="Mô tả danh mục..."
              :minHeight="160"
            />
          </label>

          <div class="grid">
            <label class="label">
              <span>Parent</span>
              <select v-model.number="form.parent_id" class="input">
                <option :value="null">(Không)</option>
                <option v-for="p in parentOptions" :key="p.id" :value="p.id">
                  {{ p.name }}
                </option>
              </select>
            </label>

            <label class="label">
              <span>Trạng thái</span>
              <select v-model="form.is_active" class="input">
                <option :value="true">Active</option>
                <option :value="false">Hidden</option>
              </select>
            </label>
          </div>

          <label class="label">
            <span>Hình ảnh danh mục (từ thư viện)</span>
            <div class="row" style="gap: 10px">
              <input
                :value="form.image"
                class="input"
                placeholder="Chọn hình từ thư viện"
                readonly
              />
              <button
                class="btn btn-secondary"
                type="button"
                @click="openImagePicker"
              >
                Chọn
              </button>
              <button
                class="btn btn-secondary"
                type="button"
                :disabled="!form.image"
                @click="form.image = ''"
              >
                Xóa
              </button>
            </div>
            <div v-if="form.image" class="muted" style="margin-top: 6px">
              <img
                :src="form.image"
                alt="Category preview"
                style="height: 72px; border-radius: 10px; object-fit: cover"
              />
            </div>
          </label>

          <button class="btn" :disabled="saving" type="submit">
            {{ saving ? "Đang lưu…" : "Lưu" }}
          </button>
        </form>
      </div>
    </div>

    <MediaPickerModal
      :open="openMediaPicker"
      @close="openMediaPicker = false"
      @select="onMediaSelected"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import RichTextEditor from "../components/RichTextEditor.vue";
import MediaPickerModal from "../components/MediaPickerModal.vue";
import type { MediaAsset } from "../lib/types";

type CategoryAdmin = {
  id: number;
  name: string;
  slug: string;
  description: string;
  image: string | null;
  parent_id: number | null;
  is_active: boolean;
  courses_count?: number;
  parent?: { id: number; name: string } | null;
};

const categories = ref<CategoryAdmin[]>([]);
const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();

const filterActive = ref<"" | "true" | "false">("");

const modalOpen = ref(false);
const editingId = ref<number | null>(null);
const openMediaPicker = ref(false);

const form = ref({
  name: "",
  slug: "",
  description: "",
  image: "" as string | null,
  parent_id: null as number | null,
  is_active: true,
});

const parentOptions = computed(() =>
  categories.value.filter((c) => c.parent_id == null),
);

function resetForm() {
  form.value = {
    name: "",
    slug: "",
    description: "",
    image: "",
    parent_id: null,
    is_active: true,
  };
}

function openCreate() {
  editingId.value = null;
  resetForm();
  modalOpen.value = true;
}

function openEdit(c: CategoryAdmin) {
  editingId.value = c.id;
  form.value.name = c.name;
  form.value.slug = c.slug;
  form.value.description = c.description || "";
  form.value.image = c.image || "";
  form.value.parent_id = c.parent_id;
  form.value.is_active = !!c.is_active;
  modalOpen.value = true;
}

function closeModal() {
  modalOpen.value = false;
}

function openImagePicker() {
  openMediaPicker.value = true;
}

function onMediaSelected(asset: MediaAsset | MediaAsset[]) {
  if (Array.isArray(asset)) return;
  if (!asset.url) return;
  form.value.image = asset.url;
  openMediaPicker.value = false;
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const qs = new URLSearchParams();
    if (filterActive.value !== "") qs.set("active", filterActive.value);
    const res = await apiFetch<{ data: CategoryAdmin[] }>(
      `/api/admin/categories?${qs.toString()}`,
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
      image: form.value.image?.trim() || null,
      parent_id: form.value.parent_id,
      is_active: form.value.is_active,
    };

    if (!editingId.value) {
      await apiFetch(`/api/admin/categories`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    } else {
      await apiFetch(`/api/admin/categories/${editingId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    }

    closeModal();
    toastSuccess("Đã lưu danh mục thành công.");
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

async function remove(c: CategoryAdmin) {
  if (!confirm(`Xóa danh mục "${c.name}"?`)) return;
  try {
    await apiFetch(`/api/admin/categories/${c.id}`, { method: "DELETE" });
    await load();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

watch(filterActive, () => {
  // user can click refresh; but auto-load keeps UX simple
  load();
});

onMounted(load);
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 9999;
}

.modal {
  width: 100%;
  max-width: 720px;
  max-height: 90vh;
  background: #fff;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}

.modal-body {
  overflow-y: auto;
  max-height: calc(90vh - 72px);
  padding-right: 6px;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 12px;
  background: #f3f4f6;
  color: #111827;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}
</style>
