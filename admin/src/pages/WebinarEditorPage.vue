<template>
  <div class="webinar-editor">
    <div class="editor-header">
      <h1 class="h1">
        {{ isNew ? "Tạo webinar mới" : `Sửa webinar #${webinarId}` }}
      </h1>
      <RouterLink class="btn btn-secondary" to="/webinars"
        >← Quay lại</RouterLink
      >
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
            placeholder="Mô tả chi tiết webinar..."
          />
        </label>

        <label class="label">
          <span>Thumbnail URL</span>
          <div class="input-with-btn">
            <input
              v-model="form.thumbnail"
              class="input"
              placeholder="/storage/webinars/banner.jpg"
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
            <span>Trạng thái <em>*</em></span>
            <select v-model="form.status" class="input" required>
              <option value="upcoming">Sắp diễn ra</option>
              <option value="live">Đang live</option>
              <option value="completed">Đã kết thúc</option>
              <option value="cancelled">Đã hủy</option>
            </select>
          </label>
          <label class="label">
            <span>Ngày giờ diễn ra <em>*</em></span>
            <input
              v-model="form.scheduled_at"
              class="input"
              type="datetime-local"
              required
            />
          </label>
        </div>

        <div class="grid grid-3">
          <label class="label">
            <span>Thời lượng (phút)</span>
            <input
              v-model.number="form.duration_minutes"
              class="input"
              type="number"
              min="1"
            />
          </label>
          <label class="label">
            <span>Miễn phí?</span>
            <select v-model="form.is_free" class="input">
              <option :value="true">Có - Miễn phí</option>
              <option :value="false">Không - Có phí</option>
            </select>
          </label>
          <label class="label">
            <span>Giá (₫)</span>
            <input
              type="text"
              :value="formatPriceInput(form.price)"
              @input="onPriceInput($event, form, 'price')"
              class="input"
              :disabled="form.is_free"
            />
          </label>
        </div>

        <label class="label">
          <span>Số lượng tối đa</span>
          <input
            v-model.number="form.max_attendees"
            class="input"
            type="number"
            min="1"
            placeholder="Không giới hạn nếu để trống"
          />
        </label>

        <label class="label">
          <span>Fake số người đăng ký</span>
          <input
            v-model.number="form.fake_registrations"
            class="input"
            type="number"
            min="0"
            placeholder="Để trống = hiển thị số thật"
          />
          <small style="color: #6b7280; font-size: 12px"
            >Nhập số để hiển thị thay cho số đăng ký thật trên trang chi
            tiết</small
          >
        </label>
      </div>

      <!-- Instructor / Main Speaker -->
      <div class="card">
        <h2 class="card-title">Diễn giả chính</h2>
        <div class="grid grid-2">
          <label class="label">
            <span>Tên diễn giả <em>*</em></span>
            <input
              v-model="form.instructor_name"
              class="input"
              required
              placeholder="VD: Ms Vũ Yến"
            />
          </label>
          <label class="label">
            <span>Avatar URL</span>
            <div class="input-with-btn">
              <input
                v-model="form.instructor_avatar"
                class="input"
                placeholder="/storage/avatars/speaker.jpg"
              />
              <button
                type="button"
                class="btn btn-secondary btn-sm"
                @click="pickMedia('instructor_avatar')"
              >
                Chọn ảnh
              </button>
            </div>
            <img
              v-if="form.instructor_avatar"
              :src="form.instructor_avatar"
              class="preview-img"
            />
          </label>
        </div>
      </div>

      <!-- Speakers -->
      <div class="card">
        <h2 class="card-title">Diễn giả khác</h2>
        <p class="card-desc">
          Thêm các diễn giả khách mời xuất hiện trên banner webinar
        </p>

        <div
          v-for="(speaker, idx) in form.speakers"
          :key="idx"
          class="speaker-row"
        >
          <div class="grid grid-2">
            <label class="label">
              <span>Tên</span>
              <input
                v-model="speaker.name"
                class="input"
                placeholder="VD: Dr Chúc"
              />
            </label>
            <label class="label">
              <span>Vai trò</span>
              <input
                v-model="speaker.role"
                class="input"
                placeholder="VD: Chuyên gia tài chính"
              />
            </label>
          </div>
          <label class="label">
            <span>Avatar URL</span>
            <div class="input-with-btn">
              <input
                v-model="speaker.avatar"
                class="input"
                placeholder="/storage/..."
              />
              <button
                type="button"
                class="btn btn-secondary btn-sm"
                @click="pickMedia('speaker_' + idx)"
              >
                Chọn ảnh
              </button>
            </div>
            <img
              v-if="speaker.avatar"
              :src="speaker.avatar"
              class="preview-img"
              style="max-width: 80px; max-height: 80px; border-radius: 50%"
            />
          </label>
          <button
            type="button"
            class="btn-remove"
            @click="form.speakers.splice(idx, 1)"
          >
            ✕
          </button>
        </div>

        <button
          type="button"
          class="btn btn-secondary btn-sm"
          @click="addSpeaker"
        >
          + Thêm diễn giả
        </button>
      </div>

      <!-- Benefits -->
      <div class="card">
        <h2 class="card-title">Bạn sẽ nhận được gì?</h2>
        <p class="card-desc">
          Danh sách lợi ích hiển thị trên trang chi tiết webinar
        </p>

        <div v-for="(b, idx) in form.benefits" :key="idx" class="benefit-row">
          <input
            v-model="form.benefits[idx]"
            class="input"
            :placeholder="'Lợi ích ' + (idx + 1)"
          />
          <button
            type="button"
            class="btn-remove"
            @click="form.benefits.splice(idx, 1)"
          >
            ✕
          </button>
        </div>

        <button
          type="button"
          class="btn btn-secondary btn-sm"
          @click="form.benefits.push('')"
        >
          + Thêm lợi ích
        </button>
      </div>

      <!-- Links -->
      <div class="card">
        <h2 class="card-title">Liên kết</h2>

        <label class="label">
          <span>Zoom Link</span>
          <input
            v-model="form.zoom_link"
            class="input"
            placeholder="https://zoom.us/j/..."
          />
        </label>
        <label class="label">
          <span>Replay URL (sau khi kết thúc)</span>
          <input
            v-model="form.replay_url"
            class="input"
            placeholder="https://youtube.com/..."
          />
        </label>

        <div class="label">
          <span style="font-size: 13px; font-weight: 600; color: #374151"
            >Bunny Video (replay)</span
          >
          <div class="input-with-btn">
            <input
              :value="form.replay_bunny_id || '(Chưa chọn)'"
              class="input"
              readonly
              style="background: #f9fafb"
            />
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="openBunnyPicker"
            >
              Chọn video từ Bunny
            </button>
            <button
              v-if="form.replay_bunny_id"
              type="button"
              class="btn btn-sm"
              style="color: #ef4444"
              @click="
                form.replay_bunny_id = '';
                form.replay_bunny_library_id = '';
              "
            >
              ✕
            </button>
          </div>
          <p
            v-if="form.replay_bunny_id"
            class="muted"
            style="font-size: 12px; margin-top: 4px"
          >
            Library: {{ form.replay_bunny_library_id }} — GUID:
            {{ form.replay_bunny_id }}
          </p>
        </div>
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
          {{ saving ? "Đang lưu..." : isNew ? "Tạo webinar" : "Lưu thay đổi" }}
        </button>
        <RouterLink class="btn btn-secondary btn-lg" to="/webinars"
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

    <!-- Bunny Video Picker Modal -->
    <div
      v-if="bunnyPickerOpen"
      class="modal-backdrop"
      @click.self="bunnyPickerOpen = false"
    >
      <div class="modal" style="width: min(980px, 95vw)">
        <div class="modal-header">
          <div class="modal-title">Chọn video từ Bunny</div>
          <button
            class="btn btn-secondary btn-sm"
            @click="bunnyPickerOpen = false"
          >
            Đóng
          </button>
        </div>
        <div class="modal-body">
          <div class="row" style="gap: 8px; flex-wrap: wrap">
            <select v-model.number="bunnyLibraryId" class="input">
              <option
                v-for="lib in bunnyLibraries"
                :key="lib.Id || lib.id"
                :value="lib.Id || lib.id"
              >
                {{ lib.name || lib.Name || lib.Id || lib.id }}
              </option>
            </select>
            <input
              v-model="bunnySearch"
              class="input"
              placeholder="Tìm theo tên video"
            />
            <select v-model.number="bunnyItemsPerPage" class="input">
              <option :value="10">10</option>
              <option :value="30">30</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <select v-model="bunnyOrderBy" class="input">
              <option value="date">Mới nhất</option>
              <option value="title">Tiêu đề</option>
            </select>
            <button
              class="btn btn-secondary btn-sm"
              @click="refreshBunnyVideos"
            >
              Tìm
            </button>
          </div>

          <div v-if="bunnyError" class="error" style="margin-top: 8px">
            {{ bunnyError }}
          </div>

          <div
            style="
              margin-top: 12px;
              overflow: auto;
              border: 1px solid #e2e8f0;
              border-radius: 8px;
            "
          >
            <table class="table" style="min-width: 720px">
              <thead>
                <tr>
                  <th style="width: 80px">Ảnh</th>
                  <th style="width: 200px">GUID</th>
                  <th>Tiêu đề</th>
                  <th style="width: 120px">Trạng thái</th>
                  <th style="width: 120px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="bunnyLoading">
                  <td colspan="5" class="muted">Đang tải...</td>
                </tr>
                <tr v-else-if="bunnyVideos.length === 0">
                  <td colspan="5" class="muted">Không có video</td>
                </tr>
                <tr
                  v-for="v in bunnyVideos"
                  :key="v.guid || v.videoGuid || v.id"
                >
                  <td>
                    <div
                      style="
                        width: 64px;
                        height: 36px;
                        background: #0f172a;
                        border-radius: 6px;
                        overflow: hidden;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                      "
                    >
                      <img
                        v-if="bunnyThumbnail(v)"
                        :src="bunnyThumbnail(v)"
                        style="width: 100%; height: 100%; object-fit: cover"
                        loading="lazy"
                        @error="
                          ($event: Event) => {
                            ($event.target as HTMLImageElement).style.display =
                              'none';
                          }
                        "
                      />
                      <span v-else class="muted" style="font-size: 10px"
                        >N/A</span
                      >
                    </div>
                  </td>
                  <td>{{ v.guid || v.videoGuid || v.id }}</td>
                  <td>{{ v.title || v.name || "Untitled" }}</td>
                  <td>
                    {{
                      v.status || v.processingStatus || v.encodeStatus || "-"
                    }}
                  </td>
                  <td style="text-align: right">
                    <button
                      class="btn btn-primary btn-sm"
                      @click="selectBunnyVideo(v)"
                    >
                      Chọn
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer" style="justify-content: space-between">
          <div class="muted">Trang {{ bunnyPage }} / {{ bunnyTotalPages }}</div>
          <div class="row" style="gap: 8px">
            <button
              class="btn btn-secondary btn-sm"
              :disabled="bunnyPage <= 1"
              @click="goBunnyPage(bunnyPage - 1)"
            >
              Trước
            </button>
            <button
              class="btn btn-secondary btn-sm"
              :disabled="bunnyPage >= bunnyTotalPages"
              @click="goBunnyPage(bunnyPage + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
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

const webinarId = computed(() => route.params.id as string);
const isNew = computed(() => webinarId.value === "new");

interface Speaker {
  name: string;
  role: string;
  avatar: string;
}

const form = ref({
  title: "",
  description: "",
  thumbnail: "",
  status: "upcoming",
  scheduled_at: "",
  duration_minutes: null as number | null,
  is_free: true,
  price: 0,
  max_attendees: null as number | null,
  fake_registrations: null as number | null,
  instructor_name: "",
  instructor_avatar: "",
  speakers: [] as Speaker[],
  benefits: [] as string[],
  zoom_link: "",
  replay_url: "",
  replay_bunny_id: "",
  replay_bunny_library_id: "",
  tags: [] as string[],
});

const loadError = ref("");
const saving = ref(false);
const newTag = ref("");
const mediaPicking = ref(false);
const mediaTarget = ref("");

// Bunny picker state
const bunnyPickerOpen = ref(false);
const bunnyLibraries = ref<any[]>([]);
const bunnyLibraryId = ref<number | null>(null);
const bunnyVideos = ref<any[]>([]);
const bunnyLoading = ref(false);
const bunnyError = ref<string | null>(null);
const bunnySearch = ref("");
const bunnyPage = ref(1);
const bunnyItemsPerPage = ref(30);
const bunnyTotalItems = ref(0);
const bunnyOrderBy = ref("date");
const bunnyPullZoneUrl = ref("");
const bunnyStreamHostname = ref("");
const bunnyCdnHostname = ref("");
const bunnyConfiguredLibraryId = ref<number | null>(null);

function addSpeaker() {
  form.value.speakers.push({ name: "", role: "", avatar: "" });
}

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
  if (!url) {
    mediaPicking.value = false;
    return;
  }
  if (mediaTarget.value === "thumbnail") {
    form.value.thumbnail = url;
  } else if (mediaTarget.value === "instructor_avatar") {
    form.value.instructor_avatar = url;
  } else if (mediaTarget.value.startsWith("speaker_")) {
    const idx = parseInt(mediaTarget.value.replace("speaker_", ""), 10);
    if (form.value.speakers[idx]) form.value.speakers[idx].avatar = url;
  }
  mediaPicking.value = false;
}

async function loadWebinar() {
  if (isNew.value) return;
  try {
    const data = await apiFetch<any>(`/api/admin/webinars/${webinarId.value}`);
    form.value.title = data.title || "";
    form.value.description = data.description || "";
    form.value.thumbnail = data.thumbnail || "";
    form.value.status = data.status || "upcoming";
    form.value.scheduled_at = data.scheduled_at
      ? new Date(data.scheduled_at).toISOString().slice(0, 16)
      : "";
    form.value.duration_minutes = data.duration_minutes || null;
    form.value.is_free = data.is_free ?? true;
    form.value.price = Number(data.price) || 0;
    form.value.max_attendees = data.max_attendees || null;
    form.value.fake_registrations = data.fake_registrations ?? null;
    form.value.instructor_name = data.instructor_name || "";
    form.value.instructor_avatar = data.instructor_avatar || "";
    form.value.speakers = data.speakers || [];
    form.value.benefits = data.benefits || [];
    form.value.zoom_link = data.zoom_link || "";
    form.value.replay_url = data.replay_url || "";
    form.value.replay_bunny_id = data.replay_bunny_id || "";
    form.value.replay_bunny_library_id = data.replay_bunny_library_id || "";
    form.value.tags = data.tags || [];
  } catch (e: any) {
    loadError.value = "Không thể tải webinar: " + (e.message || "");
  }
}

async function save() {
  saving.value = true;
  try {
    const payload = {
      ...form.value,
      benefits: form.value.benefits.filter((b) => b.trim()),
      speakers: form.value.speakers.filter((s) => s.name.trim()),
    };

    if (isNew.value) {
      await apiFetch("/api/admin/webinars", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      toast.success("Tạo webinar thành công!");
      router.push("/webinars");
    } else {
      await apiFetch(`/api/admin/webinars/${webinarId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      toast.success("Cập nhật webinar thành công!");
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

const bunnyTotalPages = computed(() => {
  if (!bunnyTotalItems.value || !bunnyItemsPerPage.value) return 1;
  return Math.max(
    1,
    Math.ceil(bunnyTotalItems.value / bunnyItemsPerPage.value),
  );
});

watch(bunnyLibraryId, (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    const lib = bunnyLibraries.value.find(
      (l: any) => Number(l.id || l.Id) === newVal,
    );
    bunnyCdnHostname.value = lib?.cdnHostname || "";
    if (bunnyPickerOpen.value) {
      bunnyPage.value = 1;
      refreshBunnyVideos();
    }
  }
});

async function openBunnyPicker() {
  bunnyPickerOpen.value = true;
  bunnyError.value = null;
  bunnyPage.value = 1;
  try {
    if (!bunnyStreamHostname.value || !bunnyConfiguredLibraryId.value) {
      const settingsRes = await apiFetch<{ settings: any }>(
        "/api/admin/settings",
      );
      bunnyPullZoneUrl.value = String(
        settingsRes.settings?.bunnycdn?.pull_zone_url || "",
      ).trim();
      bunnyStreamHostname.value = String(
        settingsRes.settings?.bunnycdn?.stream_hostname ||
          settingsRes.settings?.bunnycdn?.pull_zone_url ||
          "",
      ).trim();
      const cfgLibId = settingsRes.settings?.bunnycdn?.video_library_id;
      if (cfgLibId) bunnyConfiguredLibraryId.value = Number(cfgLibId);
    }
    if (bunnyLibraries.value.length === 0) {
      const res = await apiFetch<{
        libraries: any;
        configured_library_id?: number | null;
      }>("/api/admin/bunny/libraries");
      const raw = res.libraries;
      if (Array.isArray(raw)) bunnyLibraries.value = raw;
      else if (raw?.Items) bunnyLibraries.value = raw.Items;
      else if (raw?.items) bunnyLibraries.value = raw.items;
      else bunnyLibraries.value = [];
      if (res.configured_library_id)
        bunnyConfiguredLibraryId.value = Number(res.configured_library_id);
    }
    if (bunnyLibraries.value.length === 0) {
      bunnyError.value = "Không tìm thấy Video Library nào.";
      return;
    }
    if (!bunnyLibraryId.value) {
      if (bunnyConfiguredLibraryId.value) {
        const match = bunnyLibraries.value.find(
          (lib: any) =>
            Number(lib.id || lib.Id) === bunnyConfiguredLibraryId.value,
        );
        if (match) {
          bunnyLibraryId.value = Number(match.id || match.Id);
          bunnyCdnHostname.value = match.cdnHostname || "";
        }
      }
      if (!bunnyLibraryId.value && bunnyLibraries.value.length > 0) {
        const first = bunnyLibraries.value[0];
        bunnyLibraryId.value = Number(first.id || first.Id || first.libraryId);
        bunnyCdnHostname.value = first.cdnHostname || "";
      }
    }
    await refreshBunnyVideos();
  } catch (e: any) {
    bunnyError.value = e.message || "Lỗi không xác định";
  }
}

async function refreshBunnyVideos() {
  if (!bunnyLibraryId.value) {
    bunnyError.value = "Chưa chọn Video Library.";
    return;
  }
  bunnyLoading.value = true;
  bunnyError.value = null;
  try {
    const res = await apiFetch<{ videos: any; cdn_hostname?: string }>(
      `/api/admin/bunny/libraries/${bunnyLibraryId.value}/videos?` +
        new URLSearchParams({
          page: String(bunnyPage.value),
          itemsPerPage: String(bunnyItemsPerPage.value),
          search: bunnySearch.value || "",
          orderBy: bunnyOrderBy.value,
        }).toString(),
    );
    bunnyCdnHostname.value = res.cdn_hostname || "";
    const payload = res.videos;
    const items = Array.isArray(payload)
      ? payload
      : payload?.items || payload?.Items || payload?.data || [];
    bunnyVideos.value = items;
    bunnyTotalItems.value =
      payload?.totalItems || payload?.TotalItems || payload?.total || 0;
  } catch (e: any) {
    bunnyError.value = e.message || "Lỗi không xác định";
  } finally {
    bunnyLoading.value = false;
  }
}

function bunnyThumbnail(video: any) {
  const direct = video?.thumbnailUrl || video?.thumbnail || video?.preview;
  if (direct) return direct;
  const file = video?.thumbnailFileName || video?.thumbnail_filename || null;
  const guid = video?.guid || video?.videoGuid || video?.id || null;
  const host =
    bunnyCdnHostname.value ||
    bunnyPullZoneUrl.value ||
    bunnyStreamHostname.value;
  if (!guid || !host) return null;
  const base = host.startsWith("http") ? host : `https://${host}`;
  const normalized = base.replace(/\/+$/, "");
  if (file) return `${normalized}/${guid}/${file}`;
  return `${normalized}/${guid}/thumbnail.jpg`;
}

function selectBunnyVideo(video: any) {
  const guid = video?.guid || video?.videoGuid || video?.id || null;
  if (!guid) {
    bunnyError.value = "Không lấy được GUID của video.";
    return;
  }
  form.value.replay_bunny_id = guid;
  form.value.replay_bunny_library_id = bunnyLibraryId.value
    ? String(bunnyLibraryId.value)
    : "";
  toast.success("Đã chọn video Bunny cho replay.");
  bunnyPickerOpen.value = false;
}

function goBunnyPage(page: number) {
  bunnyPage.value = Math.min(Math.max(1, page), bunnyTotalPages.value);
  refreshBunnyVideos();
}

onMounted(loadWebinar);
</script>

<style scoped>
.webinar-editor {
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
  max-width: 300px;
  max-height: 180px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid #e5e7eb;
}

.textarea {
  resize: vertical;
  font-family: inherit;
}

/* Speaker row */
.speaker-row {
  position: relative;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  background: #fafafa;
}
.speaker-row .btn-remove {
  position: absolute;
  top: 8px;
  right: 8px;
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-size: 16px;
  font-weight: 700;
}

/* Benefit row */
.benefit-row {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  align-items: center;
}
.benefit-row .input {
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

/* Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal {
  background: #fff;
  border-radius: 12px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}
.modal-title {
  font-size: 16px;
  font-weight: 700;
}
.modal-body {
  padding: 20px;
  overflow-y: auto;
  flex: 1;
}
.modal-footer {
  padding: 12px 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
}
.row {
  display: flex;
  align-items: center;
}
.muted {
  color: #6b7280;
  font-size: 13px;
}
.error {
  color: #ef4444;
  font-size: 13px;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th,
.table td {
  padding: 8px 12px;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
}
.table thead th {
  background: #f8fafc;
  font-weight: 600;
  color: #374151;
}
</style>
