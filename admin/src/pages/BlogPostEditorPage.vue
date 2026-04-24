<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">
        {{ isNew ? "Tạo bài viết" : `Sửa bài viết #${postId}` }}
      </h1>
      <div class="row" style="gap: 8px">
        <RouterLink class="btn btn-secondary" to="/blog-posts"
          >Quay lại</RouterLink
        >
        <button class="btn" :disabled="saving" @click="save">
          {{ saving ? "Đang lưu…" : "Lưu" }}
        </button>
      </div>
    </div>

    <div v-if="error" class="alert" style="margin-top: 12px">{{ error }}</div>

    <div class="card" style="margin-top: 16px">
      <div class="grid">
        <label class="label">
          <span>Tiêu đề</span>
          <input
            v-model="form.title"
            class="input"
            placeholder="Tiêu đề bài viết"
          />
        </label>
        <label class="label">
          <span>Slug (để trống sẽ tự tạo)</span>
          <input v-model="form.slug" class="input" placeholder="tin-tuc-hay" />
        </label>
      </div>

      <div class="grid">
        <label class="label">
          <span>Danh mục</span>
          <select v-model.number="form.category_id" class="input">
            <option :value="0">(Chưa chọn)</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </label>
        <label class="label">
          <span>Trạng thái</span>
          <select v-model="form.status" class="input">
            <option value="draft">Bản nháp</option>
            <option value="published">Đã xuất bản</option>
            <option value="archived">Lưu trữ</option>
          </select>
        </label>
      </div>

      <label class="label">
        <span>Ảnh đại diện</span>
        <div class="row" style="gap: 10px">
          <input
            :value="form.featured_image"
            class="input"
            readonly
            placeholder="Chọn từ Media Library"
          />
          <button
            class="btn btn-secondary"
            type="button"
            @click="openPicker('featured')"
          >
            Chọn
          </button>
          <button
            class="btn btn-secondary"
            type="button"
            :disabled="!form.featured_image"
            @click="form.featured_image = ''"
          >
            Xóa
          </button>
        </div>
        <div v-if="form.featured_image" style="margin-top: 8px">
          <img
            :src="form.featured_image"
            style="max-width: 240px; border-radius: 8px"
          />
        </div>
      </label>

      <label class="label">
        <span>Mô tả ngắn</span>
        <textarea
          v-model="form.excerpt"
          class="textarea"
          rows="3"
          placeholder="Tóm tắt ngắn cho bài viết"
        />
      </label>

      <div
        class="row"
        style="justify-content: space-between; align-items: center"
      >
        <span class="label" style="margin: 0">Nội dung</span>
      </div>
      <RichTextEditor
        ref="contentEditorRef"
        v-model="form.content"
        placeholder="Nhập nội dung bài viết..."
        :minHeight="320"
        :enableImage="true"
        @image-request="openPicker('content')"
      />

      <div class="grid" style="margin-top: 16px">
        <label class="label">
          <span>Meta Title</span>
          <input
            v-model="form.meta_title"
            class="input"
            placeholder="Meta Title"
          />
        </label>
        <label class="label">
          <span>Meta Keywords</span>
          <input
            v-model="form.meta_keywords"
            class="input"
            placeholder="keyword1, keyword2"
          />
        </label>
      </div>

      <label class="label">
        <span>Meta Description</span>
        <textarea
          v-model="form.meta_description"
          class="textarea"
          rows="3"
          placeholder="Meta description cho SEO"
        />
      </label>
    </div>

    <MediaPickerModal
      :open="openMediaPicker"
      @close="openMediaPicker = false"
      @select="onMediaSelected"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import type { BlogCategory, BlogPost, MediaAsset } from "../lib/types";
import MediaPickerModal from "../components/MediaPickerModal.vue";
import RichTextEditor from "../components/RichTextEditor.vue";

const route = useRoute();
const router = useRouter();

const postId = computed(() => Number(route.params.id));
const isNew = computed(() => route.params.id === "new");

const categories = ref<BlogCategory[]>([]);
const openMediaPicker = ref(false);
const mediaPickerTarget = ref<"featured" | "content">("featured");
const contentEditorRef = ref<InstanceType<typeof RichTextEditor> | null>(null);

const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();

const form = ref({
  title: "",
  slug: "",
  category_id: 0,
  status: "draft" as BlogPost["status"],
  excerpt: "",
  content: "",
  featured_image: "",
  meta_title: "",
  meta_description: "",
  meta_keywords: "",
});

function openPicker(target: "featured" | "content") {
  mediaPickerTarget.value = target;
  openMediaPicker.value = true;
}

function insertIntoContent(value: string) {
  if (contentEditorRef.value?.insertHtml) {
    contentEditorRef.value.insertHtml(value);
    return;
  }
  const current = form.value.content || "";
  form.value.content = current ? `${current}\n${value}` : value;
}

function onMediaSelected(assetOrAssets: MediaAsset | MediaAsset[]) {
  const asset = Array.isArray(assetOrAssets) ? assetOrAssets[0] : assetOrAssets;
  if (!asset?.url) return;

  if (mediaPickerTarget.value === "featured") {
    form.value.featured_image = asset.url;
  } else {
    const tag = `<img src=\"${asset.url}\" alt=\"blog-image\" />`;
    insertIntoContent(tag);
  }
}

async function loadCategories() {
  try {
    const res = await apiFetch<{ data: BlogCategory[] }>(
      "/api/admin/blog-categories",
    );
    categories.value = res.data || [];
  } catch {
    // ignore
  }
}

async function loadPost() {
  if (isNew.value) return;
  const res = await apiFetch<{ post: BlogPost }>(
    `/api/admin/blog-posts/${postId.value}`,
  );
  const p = res.post;
  form.value.title = p.title || "";
  form.value.slug = p.slug || "";
  form.value.category_id = p.category_id || 0;
  form.value.status = p.status || "draft";
  form.value.excerpt = p.excerpt || "";
  form.value.content = p.content || "";
  form.value.featured_image = p.featured_image || "";
  form.value.meta_title = p.meta_title || "";
  form.value.meta_description = p.meta_description || "";
  form.value.meta_keywords = p.meta_keywords || "";
}

async function save() {
  saving.value = true;
  error.value = null;
  try {
    const payload = {
      title: form.value.title.trim(),
      slug: form.value.slug?.trim() || null,
      category_id: form.value.category_id || null,
      status: form.value.status,
      excerpt: form.value.excerpt?.trim() || null,
      content: form.value.content || "",
      featured_image: form.value.featured_image?.trim() || null,
      meta_title: form.value.meta_title?.trim() || null,
      meta_description: form.value.meta_description?.trim() || null,
      meta_keywords: form.value.meta_keywords?.trim() || null,
    };

    if (isNew.value) {
      const res = await apiFetch<{ post: BlogPost }>("/api/admin/blog-posts", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      router.push(`/blog-posts/${res.post.id}`);
    } else {
      await apiFetch(`/api/admin/blog-posts/${postId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
    }
    toastSuccess("Đã lưu bài viết thành công.");
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

onMounted(async () => {
  await loadCategories();
  await loadPost();
});
</script>
