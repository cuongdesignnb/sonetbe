<template>
  <div class="ebook-editor">
    <div class="editor-header">
      <h1 class="h1">
        {{ isNew ? "Tạo ebook mới" : `Sửa ebook #${ebookId}` }}
      </h1>
      <RouterLink class="btn btn-secondary" to="/ebooks">← Quay lại</RouterLink>
    </div>

    <div v-if="loadError" class="alert alert-danger">{{ loadError }}</div>

    <form class="editor-form" @submit.prevent="save">
      <!-- Basic Info -->
      <div class="card">
        <h2 class="card-title">Thông tin cơ bản</h2>

        <label class="label">
          <span>Tiêu đề <em>*</em></span>
          <input v-model="form.title" class="input" required />
        </label>

        <label class="label">
          <span>Mô tả (HTML)</span>
          <RichTextEditor
            v-model="form.description"
            placeholder="Mô tả chi tiết ebook..."
          />
        </label>

        <label class="label">
          <span>Thumbnail</span>
          <div class="input-with-btn">
            <input
              v-model="form.thumbnail"
              class="input"
              placeholder="/storage/ebooks/cover.jpg"
            />
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="pickMedia('thumbnail')"
            >
              Chọn ảnh
            </button>
          </div>
          <img
            v-if="form.thumbnail"
            :src="form.thumbnail"
            class="preview-img"
          />
        </label>

        <div class="grid grid-2">
          <label class="label">
            <span>Tác giả <em>*</em></span>
            <input
              v-model="form.author_name"
              class="input"
              required
              placeholder="VD: Nguyễn Văn A"
            />
          </label>
          <label class="label">
            <span>Danh mục</span>
            <select v-model="form.category_id" class="input">
              <option :value="null">-- Không chọn --</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </label>
        </div>

        <div class="grid grid-3">
          <label class="label">
            <span>Loại <em>*</em></span>
            <select v-model="form.type" class="input" required>
              <option value="ebook">Ebook</option>
              <option value="book">Sách</option>
              <option value="guide">Tài liệu</option>
            </select>
          </label>
          <label class="label">
            <span>Trạng thái <em>*</em></span>
            <select v-model="form.status" class="input" required>
              <option value="draft">Nháp</option>
              <option value="published">Đã xuất bản</option>
              <option value="coming_soon">Sắp ra mắt</option>
              <option value="archived">Lưu trữ</option>
            </select>
          </label>
          <label class="label">
            <span>Số trang</span>
            <input
              v-model.number="form.total_pages"
              class="input"
              type="number"
              min="1"
            />
          </label>
        </div>
      </div>

      <!-- Pricing -->
      <div class="card">
        <h2 class="card-title">Giá</h2>
        <div class="grid grid-2">
          <label class="label">
            <span>Giá bán (₫)</span>
            <input
              type="text"
              :value="formatPriceInput(form.price)"
              @input="onPriceInput($event, form, 'price')"
              class="input"
            />
          </label>
          <label class="label">
            <span>Giá gốc (₫)</span>
            <input
              type="text"
              :value="formatPriceInput(form.original_price)"
              @input="onPriceInput($event, form, 'original_price')"
              class="input"
            />
          </label>
        </div>
      </div>

      <!-- Files -->
      <div class="card">
        <h2 class="card-title">File</h2>
        <label class="label">
          <span>File ebook (URL)</span>
          <input
            v-model="form.file_url"
            class="input"
            placeholder="https://cdn.example.com/ebook.pdf"
          />
        </label>
        <label class="label">
          <span>Preview URL</span>
          <input
            v-model="form.preview_url"
            class="input"
            placeholder="https://cdn.example.com/preview.pdf"
          />
        </label>
      </div>

      <!-- Features -->
      <div class="card">
        <h2 class="card-title">Tính năng nổi bật</h2>
        <p class="card-desc">
          Các điểm nổi bật hiển thị trên trang chi tiết ebook
        </p>

        <div v-for="(f, idx) in form.features" :key="idx" class="feature-row">
          <input
            v-model="form.features[idx]"
            class="input"
            :placeholder="'Tính năng ' + (idx + 1)"
          />
          <button
            type="button"
            class="btn-remove"
            @click="form.features.splice(idx, 1)"
          >
            ✕
          </button>
        </div>

        <button
          type="button"
          class="btn btn-secondary btn-sm"
          @click="form.features.push('')"
        >
          + Thêm tính năng
        </button>
      </div>

      <!-- Tags -->
      <div class="card">
        <h2 class="card-title">Tags</h2>
        <div class="tags-input">
          <span v-for="(tag, idx) in form.tags" :key="idx" class="tag-chip">
            {{ tag }}
            <button type="button" @click="form.tags.splice(idx, 1)">✕</button>
          </span>
          <input
            v-model="newTag"
            class="input input-inline"
            placeholder="Nhập tag rồi Enter"
            @keydown.enter.prevent="addTag"
          />
        </div>
      </div>

      <!-- Submit -->
      <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg" :disabled="saving">
          {{ saving ? "Đang lưu..." : isNew ? "Tạo ebook" : "Lưu thay đổi" }}
        </button>
        <RouterLink class="btn btn-secondary btn-lg" to="/ebooks"
          >Hủy</RouterLink
        >
      </div>
    </form>

    <!-- Media Picker Modal -->
    <MediaPickerModal
      :open="mediaPicking"
      @select="onMediaPick"
      @close="mediaPicking = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { apiFetch } from "../lib/api";
import { useToast } from "../lib/toast";
import { formatPriceInput, parsePriceInput } from "../lib/utils";
import MediaPickerModal from "../components/MediaPickerModal.vue";
import RichTextEditor from "../components/RichTextEditor.vue";

const route = useRoute();
const router = useRouter();
const toast = useToast();

function onPriceInput(e: Event, obj: any, field: string) {
  const target = e.target as HTMLInputElement;
  obj[field] = parsePriceInput(target.value);
}

const ebookId = computed(() => route.params.id as string);
const isNew = computed(() => ebookId.value === "new");

const form = ref({
  title: "",
  description: "",
  thumbnail: "",
  author_name: "",
  category_id: null as number | null,
  type: "ebook",
  status: "published",
  price: 0,
  original_price: 0,
  total_pages: null as number | null,
  file_url: "",
  preview_url: "",
  features: [] as string[],
  tags: [] as string[],
});

const categories = ref<{ id: number; name: string }[]>([]);
const loadError = ref("");
const saving = ref(false);
const newTag = ref("");
const mediaPicking = ref(false);
const mediaTarget = ref("");

function addTag() {
  const tag = newTag.value.trim();
  if (tag && !form.value.tags.includes(tag)) {
    form.value.tags.push(tag);
  }
  newTag.value = "";
}

function pickMedia(target: string) {
  mediaTarget.value = target;
  mediaPicking.value = true;
}

function onMediaPick(asset: any) {
  const url =
    asset?.url || asset?.path || (typeof asset === "string" ? asset : "");
  if (mediaTarget.value === "thumbnail" && url) {
    form.value.thumbnail = url;
  }
  mediaPicking.value = false;
}

async function loadCategories() {
  try {
    const data = await apiFetch<any>("/api/admin/categories");
    categories.value = (data.data || data || []).map((c: any) => ({
      id: c.id,
      name: c.name,
    }));
  } catch {
    /* ignore */
  }
}

async function loadEbook() {
  if (isNew.value) return;
  try {
    const data = await apiFetch<any>(`/api/admin/ebooks/${ebookId.value}`);
    form.value.title = data.title || "";
    form.value.description = data.description || "";
    form.value.thumbnail = data.thumbnail || "";
    form.value.author_name = data.author_name || "";
    form.value.category_id = data.category_id || null;
    form.value.type = data.type || "ebook";
    form.value.status = data.status || "draft";
    form.value.price = Number(data.price) || 0;
    form.value.original_price = Number(data.original_price) || 0;
    form.value.total_pages = data.total_pages || null;
    form.value.file_url = data.file_url || "";
    form.value.preview_url = data.preview_url || "";
    form.value.features = data.features || [];
    form.value.tags = data.tags || [];
  } catch (e: any) {
    loadError.value = "Không thể tải ebook: " + (e.message || "");
  }
}

async function save() {
  saving.value = true;
  try {
    const payload = {
      ...form.value,
      features: form.value.features.filter((f) => f.trim()),
    };

    if (isNew.value) {
      await apiFetch("/api/admin/ebooks", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      toast.success("Tạo ebook thành công!");
      router.push("/ebooks");
    } else {
      await apiFetch(`/api/admin/ebooks/${ebookId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      toast.success("Cập nhật ebook thành công!");
    }
  } catch (e: any) {
    const msg = e.data?.errors
      ? Object.values(e.data.errors).flat().join(", ")
      : e.message || "Lỗi không xác định";
    toast.error(msg);
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadCategories();
  loadEbook();
});
</script>

<style scoped>
.ebook-editor {
  padding: 0;
}

.editor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 24px;
  margin-bottom: 20px;
}
.card-title {
  font-size: 16px;
  font-weight: 700;
  margin: 0 0 6px;
}
.card-desc {
  font-size: 13px;
  color: #6b7280;
  margin: 0 0 16px;
}

.label {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 14px;
}
.label span {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}
.label em {
  color: #ef4444;
  font-style: normal;
}

.grid {
  display: grid;
  gap: 16px;
}
.grid-2 {
  grid-template-columns: 1fr 1fr;
}
.grid-3 {
  grid-template-columns: 1fr 1fr 1fr;
}

.input-with-btn {
  display: flex;
  gap: 8px;
}
.input-with-btn .input {
  flex: 1;
}

.preview-img {
  margin-top: 8px;
  max-width: 200px;
  max-height: 280px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.textarea {
  resize: vertical;
  font-family: inherit;
}

/* Feature row */
.feature-row {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  align-items: center;
}
.feature-row .input {
  flex: 1;
}
.btn-remove {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 16px;
  font-weight: 700;
  padding: 4px 8px;
}

/* Tags */
.tags-input {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}
.tag-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #dbeafe;
  color: #1d4ed8;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
}
.tag-chip button {
  background: none;
  border: none;
  color: #1d4ed8;
  cursor: pointer;
  font-weight: 700;
  font-size: 14px;
}
.input-inline {
  flex: 1;
  min-width: 120px;
}

/* Form actions */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
  margin-bottom: 40px;
}

.btn-sm {
  font-size: 13px;
  padding: 6px 14px;
}
.btn-lg {
  font-size: 15px;
  padding: 12px 32px;
}
</style>
