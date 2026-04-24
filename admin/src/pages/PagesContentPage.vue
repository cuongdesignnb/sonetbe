<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Trang nội dung</h1>
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

    <div class="tabs" style="margin-top: 16px">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="tab-btn"
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        <span class="tab-icon">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <div class="tab-content" style="margin-top: 16px">
      <div class="card" style="margin-bottom: 16px">
        <h2 class="h2" style="margin-top: 0">🧭 Lưu ý</h2>
        <p class="muted" style="margin-top: -6px">
          Trang FAQ sẽ hiển thị danh sách <strong>FAQ chung</strong> từ mục FAQ
          hiện tại. Nội dung bên dưới là phần giới thiệu ở đầu trang.
        </p>
      </div>

      <div class="card">
        <h2 class="h2" style="margin-top: 0">{{ activeTabLabel }}</h2>
        <p class="muted" style="margin-top: -6px">
          Chỉnh sửa nội dung hiển thị trên trang.
        </p>
        <div class="form" style="margin-top: 12px">
          <label class="label">
            <span>Tiêu đề</span>
            <input
              v-model="activePage.title"
              class="input"
              placeholder="Nhập tiêu đề"
            />
          </label>
          <label class="label">
            <span>Mô tả ngắn</span>
            <textarea
              v-model="activePage.subtitle"
              class="input"
              rows="3"
              placeholder="Nhập mô tả"
            />
          </label>
          <label class="label">
            <span>Nội dung</span>
            <RichTextEditor
              v-model="activePage.content"
              placeholder="Nhập nội dung..."
              :minHeight="220"
            />
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import RichTextEditor from "../components/RichTextEditor.vue";

type PageContent = {
  title: string;
  subtitle: string;
  content: string;
};

type PagesPayload = {
  pages: {
    support: PageContent;
    faq: PageContent;
    feedback: PageContent;
    terms: PageContent;
    privacy: PageContent;
    refund: PageContent;
  };
};

const tabs = [
  { id: "support", label: "Trung tâm hỗ trợ", icon: "🧩" },
  { id: "faq", label: "FAQ", icon: "❓" },
  { id: "feedback", label: "Góp ý", icon: "💬" },
  { id: "terms", label: "Điều khoản", icon: "📄" },
  { id: "privacy", label: "Bảo mật", icon: "🔒" },
  { id: "refund", label: "Hoàn tiền", icon: "💸" },
];

const activeTab = ref("support");
const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const { success: toastSuccess } = useToast();

const form = ref<PagesPayload>({
  pages: {
    support: { title: "", subtitle: "", content: "" },
    faq: { title: "", subtitle: "", content: "" },
    feedback: { title: "", subtitle: "", content: "" },
    terms: { title: "", subtitle: "", content: "" },
    privacy: { title: "", subtitle: "", content: "" },
    refund: { title: "", subtitle: "", content: "" },
  },
});

const activePage = computed(
  () => form.value.pages[activeTab.value as keyof PagesPayload["pages"]],
);
const activeTabLabel = computed(() => {
  const match = tabs.find((t) => t.id === activeTab.value);
  return match ? match.label : "Trang";
});

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const res = await apiFetch<PagesPayload>("/api/admin/pages");
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
    await apiFetch("/api/admin/pages", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form.value),
    });
    toastSuccess("Đã lưu trang nội dung.");
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
