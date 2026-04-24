<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Trang liên hệ</h1>
      <div class="row" style="gap: 10px">
        <button class="btn btn-secondary" :disabled="loading" @click="load">
          {{ loading ? "Đang tải…" : "Làm mới" }}
        </button>
        <button class="btn" :disabled="saving" @click="save">
          {{ saving ? "Đang lưu…" : "Lưu thay đổi" }}
        </button>
      </div>
    </div>

    <div v-if="error" class="alert" style="margin-top: 12px">{{ error }}</div>

    <div
      class="grid"
      style="
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        margin-top: 16px;
      "
    >
      <div class="card">
        <h2 class="h2" style="margin-top: 0">🎯 Tiêu đề & giới thiệu</h2>
        <p class="muted" style="margin-top: -6px">
          Thông tin hiển thị ở đầu trang.
        </p>
        <div class="form" style="margin-top: 12px">
          <label class="label">
            <span>Tiêu đề</span>
            <input
              v-model="form.contact_page.title"
              class="input"
              placeholder="Liên hệ"
            />
          </label>
          <label class="label">
            <span>Mô tả ngắn</span>
            <textarea
              v-model="form.contact_page.subtitle"
              class="input"
              rows="3"
              placeholder="Nhập mô tả"
            />
          </label>
          <label class="label">
            <span>Banner URL (tuỳ chọn)</span>
            <div style="display:flex;gap:8px">
              <input
                v-model="form.contact_page.banner_url"
                class="input"
                placeholder="https://..."
              />
              <button type="button" class="btn btn-secondary" style="white-space:nowrap" @click="openMediaPicker = true">
                Chọn ảnh
              </button>
            </div>
            <img v-if="form.contact_page.banner_url" :src="form.contact_page.banner_url" style="max-width:200px;margin-top:8px;border-radius:8px" />
          </label>
        </div>
      </div>

      <div class="card">
        <h2 class="h2" style="margin-top: 0">🗺️ Bản đồ & giờ làm</h2>
        <p class="muted" style="margin-top: -6px">
          Nhúng bản đồ và thông tin giờ hỗ trợ.
        </p>
        <div class="form" style="margin-top: 12px">
          <label class="label">
            <span>Map Embed URL</span>
            <textarea
              v-model="form.contact_page.map_embed_url"
              class="input"
              rows="4"
              placeholder="https://www.google.com/maps/embed?..."
            />
          </label>
          <label class="label">
            <span>Giờ làm việc</span>
            <input
              v-model="form.contact_page.working_hours"
              class="input"
              placeholder="Thứ 2 - Thứ 6: 8:00 - 18:00"
            />
          </label>
        </div>
      </div>

      <div class="card" style="grid-column: 1 / -1">
        <h2 class="h2" style="margin-top: 0">✉️ Ghi chú form</h2>
        <p class="muted" style="margin-top: -6px">
          Thông điệp hiển thị dưới form liên hệ.
        </p>
        <div class="form" style="margin-top: 12px">
          <label class="label">
            <span>Ghi chú</span>
            <textarea
              v-model="form.contact_page.form_note"
              class="input"
              rows="3"
              placeholder="Chúng tôi phản hồi trong vòng 24 giờ..."
            />
          </label>
        </div>
      </div>

      <div class="card" style="grid-column: 1 / -1">
        <h2 class="h2" style="margin-top: 0">ℹ️ Thông tin liên hệ</h2>
        <p class="muted" style="margin-top: -6px">
          Email/SĐT/Địa chỉ lấy từ <strong>Cài đặt &gt; Thương hiệu</strong>.
        </p>
      </div>
    </div>

    <MediaPickerModal
      :open="openMediaPicker"
      @select="onMediaSelect"
      @close="openMediaPicker = false"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import MediaPickerModal from "../components/MediaPickerModal.vue";

type ContactPagePayload = {
  contact_page: {
    title: string;
    subtitle: string;
    banner_url: string;
    map_embed_url: string;
    working_hours: string;
    form_note: string;
  };
};

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();
const openMediaPicker = ref(false);

function onMediaSelect(asset: any) {
  const url = asset?.url || asset?.path || (typeof asset === 'string' ? asset : '');
  if (url) form.value.contact_page.banner_url = url;
  openMediaPicker.value = false;
}

const form = ref<ContactPagePayload>({
  contact_page: {
    title: "",
    subtitle: "",
    banner_url: "",
    map_embed_url: "",
    working_hours: "",
    form_note: "",
  },
});

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const res = await apiFetch<ContactPagePayload>("/api/admin/contact-page");
    form.value = res;
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
    await apiFetch("/api/admin/contact-page", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form.value),
    });
    toastSuccess("Đã lưu trang liên hệ.");
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

onMounted(() => {
  load();
});
</script>
