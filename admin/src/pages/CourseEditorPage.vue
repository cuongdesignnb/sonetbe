<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">
        {{ isNew ? "Tạo khóa học mới" : `Sửa khóa học #${courseId}` }}
      </h1>
      <RouterLink class="btn btn-secondary" to="/courses">Quay lại</RouterLink>
    </div>

    <div v-if="error" class="alert">{{ error }}</div>

    <div class="tabs" style="margin-top: 16px">
      <button
        class="tab"
        :class="{ active: tab === 'course' }"
        @click="tab = 'course'"
      >
        Khóa học
      </button>
      <button
        class="tab"
        :class="{ active: tab === 'landing' }"
        :disabled="isNew"
        @click="tab = 'landing'"
      >
        Marketing / Landing
      </button>
      <button
        class="tab"
        :class="{ active: tab === 'sections' }"
        :disabled="isNew"
        @click="tab = 'sections'"
      >
        Chương
      </button>
      <button
        class="tab"
        :class="{ active: tab === 'lessons' }"
        :disabled="isNew"
        @click="tab = 'lessons'"
      >
        Bài học
      </button>
      <button
        class="tab"
        :class="{ active: tab === 'tiers' }"
        :disabled="isNew"
        @click="tab = 'tiers'; loadTiers()"
      >
        Gói thời gian
      </button>
    </div>

    <div class="card" style="margin-top: 12px" v-if="tab === 'course'">
      <h2 class="h2">Thông tin khóa học</h2>
      <form class="form" @submit.prevent="saveCourse">
        <label class="label">
          <span>Tiêu đề</span>
          <input v-model="form.title" class="input" required />
        </label>

        <label class="label">
          <span>Slug (URL)</span>
          <div style="display: flex; gap: 6px; align-items: center">
            <input
              v-model="form.slug"
              class="input"
              placeholder="tu-dong-tao-tu-tieu-de"
              style="flex: 1"
            />
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="generateSlug"
            >
              Tạo từ tiêu đề
            </button>
          </div>
        </label>

        <label class="label">
          <span>Mô tả</span>
          <RichTextEditor
            ref="descriptionEditorRef"
            v-model="form.description"
            placeholder="Nhập mô tả khóa học..."
            :minHeight="220"
            :enableImage="true"
            @image-request="openDescriptionPicker"
          />
        </label>

        <div class="grid">
          <label class="label">
            <span>Giá</span>
            <input
              v-model="form.price"
              class="input"
              type="number"
              min="0"
              step="0.01"
              required
            />
          </label>

          <label class="label">
            <span>Giá gốc (trước giảm)</span>
            <input
              v-model="form.original_price"
              class="input"
              type="number"
              min="0"
              step="0.01"
              placeholder="Để trống nếu không giảm giá"
            />
          </label>

          <label class="label">
            <span>Badge text</span>
            <input
              v-model="form.badge_text"
              class="input"
              type="text"
              maxlength="50"
              placeholder="VD: Nổi bật, Mới, Hot..."
            />
          </label>

          <label class="label">
            <span>Badge màu</span>
            <select v-model="form.badge_color" class="input">
              <option value="red">Đỏ</option>
              <option value="blue">Xanh dương</option>
              <option value="green">Xanh lá</option>
              <option value="orange">Cam</option>
              <option value="purple">Tím</option>
            </select>
          </label>

          <label class="label">
            <span>Cấp độ</span>
            <select v-model="form.level" class="input">
              <option value="beginner">Cơ bản</option>
              <option value="intermediate">Trung cấp</option>
              <option value="advanced">Nâng cao</option>
            </select>
          </label>

          <label class="label">
            <span>Trạng thái</span>
            <select v-model="form.status" class="input">
              <option value="draft">Bản nháp</option>
              <option value="published">Đã xuất bản</option>
              <option value="coming_soon">Sắp diễn ra</option>
              <option value="archived">Lưu trữ</option>
            </select>
          </label>

          <label class="label">
            <span>Danh mục</span>
            <select v-model.number="form.category_id" class="input" required>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </label>
        </div>

        <div class="row" style="align-items: center; gap: 12px">
          <div style="flex: 1">
            <label class="label">
              <span>Ảnh thu nhỏ</span>
              <div class="row" style="gap: 10px">
                <input
                  :value="form.thumbnail"
                  class="input"
                  readonly
                  placeholder="Chọn từ thư viện ảnh"
                  @focus="selectAll"
                />
                <button
                  class="btn btn-secondary"
                  type="button"
                  @click="
                    mediaPickerTarget = 'thumbnail';
                    openMediaPicker = true;
                  "
                >
                  Chọn
                </button>
                <button
                  class="btn btn-secondary"
                  type="button"
                  :disabled="!form.thumbnail"
                  @click="form.thumbnail = ''"
                >
                  Xóa
                </button>
              </div>
            </label>
          </div>
          <div v-if="form.thumbnail" class="thumb">
            <img :src="form.thumbnail" alt="thumbnail" />
          </div>
        </div>
        <hr style="margin: 18px 0; opacity: 0.5" />

        <h3 class="h2" style="margin-top: 0">Bạn sẽ học được gì</h3>
        <p class="muted" style="margin-bottom: 10px">
          Thêm từng dòng nội dung hiển thị ở mục "Bạn sẽ học được gì" trên trang
          chi tiết khóa học.
        </p>

        <div
          v-for="(item, idx) in form.marketing.what_you_learn"
          :key="idx"
          class="row"
          style="gap: 8px; margin-bottom: 8px; align-items: center"
        >
          <input
            v-model="form.marketing.what_you_learn[idx]"
            class="input"
            style="flex: 1"
            :placeholder="`Nội dung #${idx + 1}`"
          />
          <button
            class="btn btn-danger btn-sm"
            type="button"
            @click="form.marketing.what_you_learn.splice(idx, 1)"
          >
            Xóa
          </button>
        </div>

        <button
          class="btn btn-secondary btn-sm"
          type="button"
          @click="form.marketing.what_you_learn.push('')"
        >
          + Thêm dòng
        </button>

        <div style="margin-top: 16px">
          <button class="btn" :disabled="saving" type="submit">
            {{ saving ? "Đang lưu…" : "Lưu" }}
          </button>
        </div>
      </form>
    </div>

    <CourseLandingEditor
      v-if="tab === 'landing' && !isNew"
      :courseId="courseId"
    />

    <div class="card" style="margin-top: 12px" v-else-if="tab === 'sections'">
      <h2 class="h2">Danh sách chương</h2>

      <form
        class="row"
        style="gap: 12px; align-items: end"
        @submit.prevent="createSection"
      >
        <label class="label" style="flex: 1">
          <span>Tiêu đề</span>
          <input v-model="newSection.title" class="input" required />
        </label>
        <label class="label" style="width: 140px">
          <span>Thứ tự</span>
          <input
            v-model.number="newSection.order"
            class="input"
            type="number"
            min="1"
            required
          />
        </label>
        <button class="btn" :disabled="loadingSections" type="submit">
          Thêm
        </button>
      </form>

      <table class="table" style="margin-top: 12px">
        <thead>
          <tr>
            <th>Thứ tự</th>
            <th>Tiêu đề</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in sections" :key="s.id">
            <td style="width: 120px">
              <input
                v-model.number="s.order"
                class="input"
                type="number"
                min="1"
                @change="updateSection(s)"
              />
            </td>
            <td>
              <input
                v-model="s.title"
                class="input"
                @change="updateSection(s)"
              />
            </td>
            <td style="text-align: right; white-space: nowrap">
              <button class="btn btn-danger" @click="deleteSection(s)">
                Xóa
              </button>
            </td>
          </tr>
          <tr v-if="sections.length === 0">
            <td colspan="3" class="muted">Chưa có chương nào</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card" style="margin-top: 12px" v-else-if="tab === 'lessons'">
      <h2 class="h2">Danh sách bài học</h2>

      <form class="lesson-form" @submit.prevent="createLesson">
        <div class="lesson-form-row">
          <label class="label" style="flex: 2">
            <span>Thuộc chương</span>
            <select v-model.number="newLesson.section_id" class="input">
              <option :value="null">(Không thu?Tc chương nào)</option>
              <option v-for="s in sections" :key="s.id" :value="s.id">
                {{ s.order }}. {{ s.title }}
              </option>
            </select>
          </label>
          <label class="label" style="flex: 0 0 100px">
            <span>Thứ tự</span>
            <input
              v-model.number="newLesson.order"
              class="input"
              type="number"
              min="1"
              required
            />
          </label>
          <label class="label" style="flex: 3">
            <span>Tiêu đề bài học</span>
            <input
              v-model="newLesson.title"
              class="input"
              placeholder="Nhập tiêu đề bài học..."
              required
            />
          </label>
          <label class="label" style="flex: 0 0 120px">
            <span>Xem trước?</span>
            <select v-model="newLesson.is_preview" class="input">
              <option :value="false">Không</option>
              <option :value="true">Có</option>
            </select>
          </label>
          <button
            class="btn btn-primary"
            :disabled="loadingLessons"
            type="submit"
            style="align-self: flex-end; height: 42px"
          >
            + Thêm bài học
          </button>
        </div>
      </form>

      <table class="table lessons-table" style="margin-top: 16px">
        <thead>
          <tr>
            <th style="width: 200px">Chương</th>
            <th style="width: 80px; text-align: center">STT</th>
            <th style="min-width: 250px">Tiêu đề</th>
            <th style="width: 120px; text-align: center">Thumbnail</th>
            <th style="width: 100px; text-align: center">Xem trước</th>
            <th style="min-width: 300px">Video</th>
            <th style="width: 80px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="l in lessons" :key="l.id" class="lesson-row">
            <!-- Chương -->
            <td style="width: 200px">
              <select
                v-model.number="l.section_id"
                class="input input-sm"
                @change="updateLesson(l)"
              >
                <option :value="null">(Không)</option>
                <option v-for="s in sections" :key="s.id" :value="s.id">
                  {{ s.order }}. {{ s.title }}
                </option>
              </select>
            </td>
            <!-- Thứ tự -->
            <td style="width: 80px; text-align: center">
              <input
                v-model.number="l.order"
                class="input input-sm"
                type="number"
                min="1"
                style="text-align: center"
                @change="updateLesson(l)"
              />
            </td>
            <!-- Tiêu đề -->
            <td style="min-width: 250px">
              <input
                v-model="l.title"
                class="input lesson-title-input"
                placeholder="Nhập tiêu đề bài học..."
                @change="updateLesson(l)"
              />
            </td>
            <!-- Thumbnail -->
            <td style="width: 120px; text-align: center">
              <div class="lesson-thumb-cell">
                <div
                  v-if="l.thumbnail || lessonThumbnail(l)"
                  class="lesson-thumb-preview"
                >
                  <img
                    :src="l.thumbnail || lessonThumbnail(l)"
                    alt="thumb"
                    @error="onLessonThumbError(l.id)"
                  />
                </div>
                <div class="lesson-thumb-actions">
                  <button
                    class="btn btn-outline btn-xs"
                    type="button"
                    @click="openLessonThumbPicker(l.id)"
                    title="Chọn ảnh"
                  >
                    🖼️
                  </button>
                  <button
                    v-if="l.thumbnail"
                    class="btn btn-outline btn-xs"
                    type="button"
                    @click="clearLessonThumbnail(l)"
                    title="Xóa thumbnail"
                  >
                    ✕
                  </button>
                </div>
              </div>
            </td>
            <!-- Xem trước -->
            <td style="width: 100px; text-align: center">
              <select
                v-model="l.is_preview"
                class="input input-sm"
                @change="updateLesson(l)"
              >
                <option :value="false">Không</option>
                <option :value="true">Có</option>
              </select>
            </td>
            <!-- Video -->
            <td style="min-width: 300px">
              <div class="video-upload-section">
                <input
                  class="input input-sm"
                  type="file"
                  accept="video/*"
                  @change="(e) => uploadLessonVideo(l.id, e)"
                />
                <button
                  class="btn btn-outline btn-sm"
                  @click="openBunnyPicker(l.id)"
                  type="button"
                >
                  📹 Bunny
                </button>
              </div>
              <div v-if="uploadNames[l.id]" class="upload-filename">
                📚 {{ uploadNames[l.id] }}
              </div>
              <div v-if="uploadStatus[l.id]" class="upload-status">
                <span
                  v-if="uploadStatus[l.id] === 'success'"
                  class="status-success"
                >
                  ✅ Upload thành công
                </span>
                <span v-else class="status-error"> ❌ Upload lỗi </span>
              </div>
              <div v-if="uploading[l.id]" class="upload-progress">
                <div class="progress-header">
                  <span>Đang tải lên</span>
                  <span>{{ uploadProgress[l.id] || 0 }}%</span>
                </div>
                <div class="progress-bar">
                  <div
                    class="progress-fill"
                    :style="{ width: `${uploadProgress[l.id] || 0}%` }"
                  ></div>
                </div>
              </div>
              <div class="video-inputs">
                <input
                  v-model="l.video_bunny_id"
                  class="input input-sm"
                  placeholder="Bunny GUID..."
                  @change="onManualGuidChange(l)"
                />
                <input
                  v-model="l.embed_url"
                  class="input input-sm"
                  placeholder="Embed URL..."
                  @change="updateLesson(l)"
                />
              </div>
              <div class="video-status">
                <span v-if="l.embed_url" class="status-badge status-embed"
                  >📹 Embed</span
                >
                <span
                  v-else-if="l.video_bunny_id"
                  class="status-badge status-bunny"
                  title="Library: {{ l.video_bunny_library_id || bunnyConfiguredLibraryId || '?' }}"
                  >🐰 Bunny (Lib:
                  {{
                    l.video_bunny_library_id || bunnyConfiguredLibraryId || "?"
                  }})</span
                >
                <span
                  v-else-if="l.video_local_path"
                  class="status-badge status-local"
                  >💾 Local</span
                >
                <span v-else class="status-badge status-none"
                  >❌ Chưa có video</span
                >
              </div>
            </td>
            <!-- Action -->
            <td style="width: 80px; text-align: center">
              <button
                class="btn btn-danger btn-sm"
                @click="deleteLesson(l)"
                title="Xóa bài học"
              >
                🗑️
              </button>
            </td>
          </tr>
          <tr v-if="lessons.length === 0">
            <td
              colspan="7"
              class="muted"
              style="text-align: center; padding: 24px"
            >
              Chưa có bài học nào
            </td>
          </tr>
        </tbody>
      </table>

      <div class="card" style="margin-top: 12px">
        <h3 class="h3" style="margin-bottom: 8px">Hướng dẫn video Bunny CDN</h3>
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 12px">
          <div>
            <div class="label" style="margin-bottom: 6px">Cách upload</div>
            <p class="muted">
              Chọn file video để upload trực tiếp lên Bunny Stream (nếu đã cấu
              hình). Nếu bạn đã upload trên Bunny trước đó, chỉ cần dán Bunny
              Video GUID để gán vào bài học.
            </p>
          </div>
          <div>
            <div class="label" style="margin-bottom: 6px">Lưu trữ video</div>
            <select v-model="videoStorage" class="input">
              <option value="bunny">Bunny Stream (khuyến nghị)</option>
              <option value="local">Lưu local (storage/public)</option>
            </select>
            <p class="muted" style="margin-top: 6px">
              Cấu hình Bunny tại phần Cài đặt → Bunny CDN.
            </p>
            <RouterLink
              class="btn btn-secondary btn-sm"
              to="/settings"
              style="margin-top: 8px"
            >
              Mở Cài đặt Bunny
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Duration Tiers Tab -->
    <div class="card" style="margin-top: 12px" v-if="tab === 'tiers'">
      <h2 class="h2">Gói thời gian & Giá</h2>
      <p class="muted" style="margin-bottom: 16px">
        Thiết lập các gói thời gian truy cập khóa học với mức giá khác nhau. Nếu không tạo gói nào, khóa học sẽ sử dụng giá mặc định (vĩnh viễn).
      </p>

      <!-- Add tier form -->
      <form
        class="tier-form"
        @submit.prevent="createTier"
      >
        <div class="tier-form-row">
          <label class="label" style="flex: 2">
            <span>Tên gói</span>
            <input
              v-model="newTier.label"
              class="input"
              placeholder="VD: 3 tháng, 6 tháng, Vĩnh viễn"
              required
            />
          </label>
          <label class="label" style="flex: 1">
            <span>Thời hạn (ngày)</span>
            <input
              v-model.number="newTier.duration_days"
              class="input"
              type="number"
              min="1"
              placeholder="Trống = Vĩnh viễn"
            />
          </label>
          <label class="label" style="flex: 1">
            <span>Giá</span>
            <input
              v-model.number="newTier.price"
              class="input"
              type="number"
              min="0"
              step="1000"
              required
            />
          </label>
          <label class="label" style="flex: 1">
            <span>Giá gốc</span>
            <input
              v-model.number="newTier.original_price"
              class="input"
              type="number"
              min="0"
              step="1000"
              placeholder="Không bắt buộc"
            />
          </label>
          <button
            class="btn btn-primary"
            :disabled="loadingTiers"
            type="submit"
            style="align-self: flex-end; height: 42px"
          >
            + Thêm gói
          </button>
        </div>
      </form>

      <!-- Tiers table -->
      <table class="table tiers-table" style="margin-top: 16px">
        <thead>
          <tr>
            <th style="width: 60px; text-align: center">STT</th>
            <th style="min-width: 150px">Tên gói</th>
            <th style="width: 120px; text-align: center">Thời hạn</th>
            <th style="width: 140px; text-align: right">Giá</th>
            <th style="width: 140px; text-align: right">Giá gốc</th>
            <th style="width: 100px; text-align: center">Mặc định</th>
            <th style="width: 100px; text-align: center">Hoạt động</th>
            <th style="width: 200px">Đối tượng</th>
            <th style="width: 80px"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in tiers" :key="t.id" class="tier-row">
            <td style="text-align: center">
              <input
                v-model.number="t.sort_order"
                class="input input-sm"
                type="number"
                min="0"
                style="width: 50px; text-align: center"
                @change="updateTier(t)"
              />
            </td>
            <td>
              <input
                v-model="t.label"
                class="input input-sm"
                placeholder="Tên gói"
                @change="updateTier(t)"
              />
            </td>
            <td style="text-align: center">
              <input
                v-model.number="t.duration_days"
                class="input input-sm"
                type="number"
                min="1"
                :placeholder="'Vĩnh viễn'"
                style="width: 80px; text-align: center"
                @change="updateTier(t)"
              />
              <div class="muted" style="font-size: 11px; margin-top: 2px">
                {{ t.duration_days ? formatDuration(t.duration_days) : 'Vĩnh viễn' }}
              </div>
            </td>
            <td style="text-align: right">
              <input
                v-model.number="t.price"
                class="input input-sm"
                type="number"
                min="0"
                step="1000"
                style="width: 120px; text-align: right"
                @change="updateTier(t)"
              />
            </td>
            <td style="text-align: right">
              <input
                v-model.number="t.original_price"
                class="input input-sm"
                type="number"
                min="0"
                step="1000"
                style="width: 120px; text-align: right"
                placeholder="-"
                @change="updateTier(t)"
              />
            </td>
            <td style="text-align: center">
              <input
                type="checkbox"
                :checked="t.is_default"
                @change="setDefaultTier(t)"
              />
            </td>
            <td style="text-align: center">
              <input
                type="checkbox"
                v-model="t.is_active"
                @change="updateTier(t)"
              />
            </td>
            <td>
              <div class="tier-targets">
                <div v-for="target in (t.targets || [])" :key="target.id" class="tier-target-chip" :style="target.target_type === 'group' && target.target_color ? `border-color: ${target.target_color}` : ''">
                  <span class="tier-target-type">{{ target.target_type === 'user' ? '👤' : '👥' }}</span>
                  <span class="tier-target-name">{{ target.target_name }}</span>
                  <button class="tier-target-remove" @click="removeTierTarget(t.id, target.target_type, target.target_id)" title="Xóa">×</button>
                </div>
                <button class="btn btn-outline btn-xs" type="button" @click="openTierTargetModal(t)">+ Thêm</button>
              </div>
            </td>
            <td style="text-align: center">
              <button
                class="btn btn-danger btn-sm"
                @click="deleteTier(t)"
                title="Xóa gói"
              >
                🗑️
              </button>
            </td>
          </tr>
          <tr v-if="tiers.length === 0">
            <td colspan="9" class="muted" style="text-align: center; padding: 24px">
              Chưa có gói thời gian nào. Khóa học sẽ sử dụng giá mặc định (truy cập vĩnh viễn).
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Quick add presets -->
      <div style="margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap">
        <span class="muted" style="align-self: center">Thêm nhanh:</span>
        <button
          class="btn btn-secondary btn-sm"
          type="button"
          @click="addPresetTier('3 tháng', 90)"
        >
          3 tháng
        </button>
        <button
          class="btn btn-secondary btn-sm"
          type="button"
          @click="addPresetTier('6 tháng', 180)"
        >
          6 tháng
        </button>
        <button
          class="btn btn-secondary btn-sm"
          type="button"
          @click="addPresetTier('12 tháng', 365)"
        >
          12 tháng
        </button>
        <button
          class="btn btn-secondary btn-sm"
          type="button"
          @click="addPresetTier('Vĩnh viễn', null)"
        >
          Vĩnh viễn
        </button>
      </div>

      <!-- Tier Target Modal -->
      <div v-if="tierTargetModal.open" class="modal-backdrop" @click.self="closeTierTargetModal">
        <div class="modal" style="width: min(560px, 95vw)">
          <div class="modal-header">
            <div class="modal-title">Chỉ định đối tượng - {{ tierTargetModal.tierLabel }}</div>
            <button class="btn btn-secondary btn-sm" @click="closeTierTargetModal">Đóng</button>
          </div>
          <div class="modal-body">
            <p class="muted" style="margin-bottom: 12px">Chỉ người dùng hoặc nhóm được chỉ định mới thấy và mua được gói này. Nếu không chỉ định, tất cả đều thấy.</p>
            
            <!-- Add user -->
            <div class="form-group">
              <label class="label">Thêm người dùng</label>
              <div style="position: relative">
                <input v-model="tierTargetSearch" class="input" placeholder="Tìm theo tên hoặc email..." @input="debouncedSearchTargetUsers" />
                <div v-if="tierTargetSearchResults.length > 0" class="search-dropdown" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 10">
                  <div v-for="u in tierTargetSearchResults" :key="u.id" class="search-dropdown-item" @click="addTierTarget(tierTargetModal.tierId!, 'user', u.id)">
                    <span>👤 {{ u.name }} ({{ u.email }})</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Add group -->
            <div class="form-group" style="margin-top: 12px">
              <label class="label">Thêm nhóm người dùng</label>
              <div style="display: flex; flex-wrap: wrap; gap: 6px">
                <button
                  v-for="g in userGroups.filter(ug => !getTierTargets(tierTargetModal.tierId!).some(t => t.target_type === 'group' && t.target_id === ug.id))"
                  :key="g.id"
                  class="btn btn-outline btn-sm"
                  type="button"
                  @click="addTierTarget(tierTargetModal.tierId!, 'group', g.id)"
                  :style="`border-color: ${g.color}; color: ${g.color}`"
                >
                  👥 {{ g.name }} ({{ g.members_count }})
                </button>
                <span v-if="userGroups.length === 0" class="muted">Chưa có nhóm nào. Tạo nhóm tại mục "Nhóm người dùng".</span>
              </div>
            </div>

            <!-- Current targets -->
            <div style="margin-top: 16px">
              <label class="label">Đối tượng hiện tại</label>
              <div v-if="getTierTargets(tierTargetModal.tierId!).length === 0" class="muted" style="padding: 8px 0">Chưa chỉ định. Tất cả người dùng đều thấy gói này.</div>
              <div v-else style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px">
                <div
                  v-for="target in getTierTargets(tierTargetModal.tierId!)"
                  :key="target.id"
                  class="tier-target-chip"
                  :style="target.target_type === 'group' && target.target_color ? `border-color: ${target.target_color}` : ''"
                >
                  <span>{{ target.target_type === 'user' ? '👤' : '👥' }} {{ target.target_name }}</span>
                  <button class="tier-target-remove" @click="removeTierTarget(tierTargetModal.tierId!, target.target_type, target.target_id)">×</button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeTierTargetModal">Đóng</button>
          </div>
        </div>
      </div>
    </div>

    <MediaPickerModal
      :open="openMediaPicker"
      :multiple="false"
      @close="openMediaPicker = false"
      @select="onMediaSelected"
    />

    <div
      v-if="bunnyPickerOpen"
      class="modal-backdrop"
      @click.self="closeBunnyPicker"
    >
      <div class="modal" style="width: min(980px, 95vw)">
        <div class="modal-header">
          <div class="modal-title">Chọn video từ Bunny</div>
          <button class="btn btn-secondary btn-sm" @click="closeBunnyPicker">
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
              <option value="views">Lượt xem</option>
            </select>
            <button
              class="btn btn-secondary btn-sm"
              @click="refreshBunnyVideos"
            >
              Tìm
            </button>
          </div>

          <div class="row" style="gap: 8px; margin-top: 12px">
            <input
              class="input"
              type="file"
              accept="video/*"
              @change="uploadBunnyFromModal"
            />
            <div v-if="modalUploadName" class="muted">
              {{ modalUploadName }}
            </div>
          </div>
          <div v-if="modalUploading" style="margin-top: 6px">
            <div class="row" style="justify-content: space-between">
              <span class="muted">Đang tải lên</span>
              <span class="muted">{{ modalUploadProgress }}%</span>
            </div>
            <div
              style="
                height: 6px;
                background: #e2e8f0;
                border-radius: 999px;
                overflow: hidden;
                margin-top: 4px;
              "
            >
              <div
                :style="{
                  width: `${modalUploadProgress}%`,
                  height: '100%',
                  background: '#6366f1',
                  transition: 'width 0.2s ease',
                }"
              ></div>
            </div>
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
                  <th style="width: 80px">?nh</th>
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
                      <span v-else class="muted" style="font-size: 10px">
                        N/A
                      </span>
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
import { computed, onMounted, ref, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { apiFetch, apiForm, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import type {
  Category,
  Course,
  CourseSection,
  Lesson,
  MediaAsset,
} from "../lib/types";
import MediaPickerModal from "../components/MediaPickerModal.vue";
import RichTextEditor from "../components/RichTextEditor.vue";
import CourseLandingEditor from "../components/CourseLandingEditor.vue";

const route = useRoute();
const router = useRouter();

const courseId = computed(() => Number(route.params.id));
const isNew = computed(() => route.params.id === "new");

const tab = ref<"course" | "sections" | "lessons" | "landing" | "tiers">("course");

const categories = ref<Category[]>([]);
const sections = ref<CourseSection[]>([]);
const lessons = ref<Lesson[]>([]);
const openMediaPicker = ref(false);
const mediaPickerTarget = ref<"thumbnail" | "description" | "lesson_thumbnail">(
  "thumbnail",
);
const lessonThumbPickerLessonId = ref<number | null>(null);
const descriptionEditorRef = ref<InstanceType<typeof RichTextEditor> | null>(
  null,
);

const error = ref<string | null>(null);
const saving = ref(false);
const loadingSections = ref(false);
const loadingLessons = ref(false);
const { success: toastSuccess, error: toastError } = useToast();
const videoStorage = ref<"bunny" | "local">("bunny");
const uploadProgress = ref<Record<number, number>>({});
const uploadNames = ref<Record<number, string>>({});
const uploading = ref<Record<number, boolean>>({});
const uploadStatus = ref<Record<number, "success" | "error" | null>>({});

const bunnyPickerOpen = ref(false);
const bunnyPickerLessonId = ref<number | null>(null);
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
const bunnyLibCdnMap = ref<Record<string, string>>({});
const lessonThumbs = ref<Record<number, string>>({});
const lessonThumbErrors = ref<Record<number, boolean>>({});
const modalUploadProgress = ref(0);
const modalUploading = ref(false);
const modalUploadName = ref("");

// Auto-refresh videos when library changes
watch(bunnyLibraryId, (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    // Update CDN hostname for the selected library
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

const bunnyTotalPages = computed(() => {
  if (!bunnyTotalItems.value || !bunnyItemsPerPage.value) return 1;
  return Math.max(
    1,
    Math.ceil(bunnyTotalItems.value / bunnyItemsPerPage.value),
  );
});

function selectAll(e: Event) {
  const el = e.target as HTMLInputElement;
  el.select();
}

const form = ref({
  title: "",
  slug: "",
  description: "",
  price: 0,
  original_price: null as number | null,
  category_id: 0,
  level: "beginner" as Course["level"],
  status: "draft" as Course["status"],
  thumbnail: "" as string,
  badge_text: "" as string,
  badge_color: "red" as string,
  marketing: {
    what_you_learn: [] as string[],
  },
});

const newSection = ref({ title: "", order: 1 });
const newLesson = ref({
  title: "",
  order: 1,
  section_id: null as number | null,
  is_preview: false,
});

// Duration tiers
type TierTarget = {
  id: number;
  duration_tier_id: number;
  target_type: 'user' | 'group';
  target_id: number;
  target_name?: string;
  target_email?: string;
  target_color?: string;
};

type DurationTier = {
  id: number;
  course_id: number;
  label: string;
  duration_days: number | null;
  price: number;
  original_price: number | null;
  sort_order: number;
  is_default: boolean;
  is_active: boolean;
  targets?: TierTarget[];
};
const tiers = ref<DurationTier[]>([]);
const loadingTiers = ref(false);

// Tier targets
type UserGroupOption = { id: number; name: string; color: string; members_count: number };
const userGroups = ref<UserGroupOption[]>([]);
const tierTargetModal = ref<{ open: boolean; tierId: number | null; tierLabel: string }>({ open: false, tierId: null, tierLabel: '' });
const tierTargetSearch = ref('');
const tierTargetSearchResults = ref<{ id: number; name: string; email: string }[]>([]);
let tierTargetSearchTimer: ReturnType<typeof setTimeout> | null = null;
const newTier = ref({
  label: "",
  duration_days: null as number | null,
  price: 0,
  original_price: null as number | null,
});

async function loadCategories() {
  const res = await apiFetch<Category[]>("/api/categories?flat=1");
  categories.value = res;
}

async function loadCourse() {
  if (isNew.value) return;
  const res = await apiFetch<{ course: Course }>(
    `/api/admin/courses/${courseId.value}`,
  );
  const c = res.course;
  form.value.title = c.title;
  form.value.slug = (c as any).slug || "";
  form.value.description = c.description;
  form.value.price = Number(c.price);
  form.value.category_id = c.category_id;
  form.value.level = c.level;
  form.value.status = c.status;
  form.value.thumbnail = c.thumbnail || "";
  form.value.original_price = c.original_price
    ? Number(c.original_price)
    : null;
  form.value.badge_text = (c as any).badge_text || "";
  form.value.badge_color = (c as any).badge_color || "red";

  const m: any = (c as any).marketing || {};
  const whatYouLearn: string[] = Array.isArray(m?.what_you_learn)
    ? m.what_you_learn
    : [];
  form.value.marketing.what_you_learn = whatYouLearn
    .map((s: any) => String(s || "").trim())
    .filter(Boolean);
}

async function loadSections() {
  if (isNew.value) return;
  loadingSections.value = true;
  try {
    const res = await apiFetch<{ sections: CourseSection[] }>(
      `/api/admin/courses/${courseId.value}/sections`,
    );
    sections.value = res.sections;
  } finally {
    loadingSections.value = false;
  }
}

async function loadLessons() {
  if (isNew.value) return;
  loadingLessons.value = true;
  try {
    const res = await apiFetch<{ lessons: Lesson[] }>(
      `/api/admin/courses/${courseId.value}/lessons`,
    );
    lessons.value = res.lessons;
  } finally {
    loadingLessons.value = false;
  }
}

function onMediaSelected(assetOrAssets: MediaAsset | MediaAsset[]) {
  const assets = Array.isArray(assetOrAssets) ? assetOrAssets : [assetOrAssets];
  const urls = assets.map((a) => a.url).filter(Boolean) as string[];
  if (urls.length === 0) return;

  if (mediaPickerTarget.value === "description") {
    const tag = `<img src="${urls[0]}" alt="course-image" />`;
    if (descriptionEditorRef.value?.insertHtml) {
      descriptionEditorRef.value.insertHtml(tag);
    } else {
      form.value.description = `${form.value.description || ""}${tag}`.trim();
    }
    return;
  }

  if (mediaPickerTarget.value === "thumbnail") {
    form.value.thumbnail = urls[0];
    return;
  }

  if (mediaPickerTarget.value === "lesson_thumbnail") {
    const lid = lessonThumbPickerLessonId.value;
    if (lid) {
      const idx = lessons.value.findIndex((l) => l.id === lid);
      if (idx >= 0) {
        lessons.value[idx] = { ...lessons.value[idx], thumbnail: urls[0] };
        updateLesson(lessons.value[idx]);
      }
    }
    return;
  }
}

function openDescriptionPicker() {
  mediaPickerTarget.value = "description";
  openMediaPicker.value = true;
}

function generateSlug() {
  const title = form.value.title.trim();
  if (!title) return;
  // Simple slug generation (Vietnamese-friendly)
  form.value.slug = title
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

async function saveCourse() {
  saving.value = true;
  error.value = null;
  try {
    const payload = {
      title: form.value.title,
      slug: form.value.slug || null,
      description: form.value.description,
      price: Number(form.value.price),
      original_price: form.value.original_price
        ? Number(form.value.original_price)
        : null,
      category_id: Number(form.value.category_id),
      level: form.value.level,
      status: form.value.status,
      thumbnail: form.value.thumbnail || null,
      badge_text: form.value.badge_text || null,
      badge_color: form.value.badge_color || "red",
      marketing: {
        what_you_learn: (form.value.marketing.what_you_learn || [])
          .map((s) => String(s || "").trim())
          .filter(Boolean),
      },
    };

    if (isNew.value) {
      const res = await apiFetch<{ course: Course }>("/api/admin/courses", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      toastSuccess("Đã lưu khóa học thành công.");
      await router.replace(`/courses/${res.course.id}`);
      tab.value = "sections";
      await loadSections();
      await loadLessons();
    } else {
      await apiFetch(`/api/admin/courses/${courseId.value}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      toastSuccess("Đã lưu khóa học thành công.");
    }
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

async function createSection() {
  if (isNew.value) return;
  try {
    await apiFetch(`/api/admin/courses/${courseId.value}/sections`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(newSection.value),
    });
    newSection.value.title = "";
    newSection.value.order = 1;
    await loadSections();
    toastSuccess("Đã thêm chương mới.");
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function updateSection(s: CourseSection) {
  try {
    await apiFetch(`/api/admin/sections/${s.id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ title: s.title, order: s.order }),
    });
    toastSuccess("Đã lưu chương.");
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function deleteSection(s: CourseSection) {
  if (!confirm("Xóa chương này?")) return;
  try {
    await apiFetch(`/api/admin/sections/${s.id}`, { method: "DELETE" });
    await loadSections();
    await loadLessons();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function createLesson() {
  if (isNew.value) return;
  try {
    const res = await apiFetch<{ lesson?: Lesson }>(
      `/api/admin/courses/${courseId.value}/lessons`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newLesson.value),
      },
    );
    newLesson.value.title = "";
    newLesson.value.order = 1;
    newLesson.value.section_id = null;
    newLesson.value.is_preview = false;
    await loadLessons();
    if (lessons.value.length === 0 && res?.lesson?.id) {
      lessons.value = [res.lesson];
    }
    toastSuccess("Đã thêm bài học mới.");
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function onManualGuidChange(l: Lesson) {
  // When admin manually enters a Bunny GUID, auto-set library_id if empty
  if (l.video_bunny_id && !l.video_bunny_library_id) {
    l.video_bunny_library_id = bunnyConfiguredLibraryId.value
      ? String(bunnyConfiguredLibraryId.value)
      : null;
  }
  if (!l.video_bunny_id) {
    l.video_bunny_library_id = null;
  }
  await updateLesson(l);
}

function openLessonThumbPicker(lessonId: number) {
  lessonThumbPickerLessonId.value = lessonId;
  mediaPickerTarget.value = "lesson_thumbnail";
  openMediaPicker.value = true;
}

async function clearLessonThumbnail(l: Lesson) {
  l.thumbnail = null;
  await updateLesson(l);
}

async function updateLesson(l: Lesson) {
  try {
    await apiFetch(`/api/admin/lessons/${l.id}`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        title: l.title,
        order: l.order,
        section_id: l.section_id,
        is_preview: l.is_preview,
        thumbnail: l.thumbnail || null,
        video_bunny_id: l.video_bunny_id || null,
        video_bunny_library_id: l.video_bunny_library_id || null,
        embed_url: l.embed_url || null,
      }),
    });
    toastSuccess("Đã lưu bài học.");
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function deleteLesson(l: Lesson) {
  if (!confirm("Xóa bài học này?")) return;
  try {
    await apiFetch(`/api/admin/lessons/${l.id}`, { method: "DELETE" });
    await loadLessons();
  } catch (e) {
    error.value = extractMessage(e);
  }
}

async function uploadLessonVideo(lessonId: number, event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  try {
    uploadNames.value = { ...uploadNames.value, [lessonId]: file.name };
    uploadProgress.value = { ...uploadProgress.value, [lessonId]: 0 };
    uploading.value = { ...uploading.value, [lessonId]: true };
    uploadStatus.value = { ...uploadStatus.value, [lessonId]: null };

    const form = new FormData();
    form.append("video", file);
    form.append("storage_type", videoStorage.value);

    await new Promise<void>((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", `/api/lessons/${lessonId}/upload-video`);
      const token = localStorage.getItem("admin_token");
      if (token) xhr.setRequestHeader("Authorization", `Bearer ${token}`);
      xhr.setRequestHeader("Accept", "application/json");

      xhr.upload.onprogress = (e) => {
        if (!e.lengthComputable) return;
        const percent = Math.round((e.loaded / e.total) * 100);
        uploadProgress.value = { ...uploadProgress.value, [lessonId]: percent };
      };

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const json = xhr.responseText ? JSON.parse(xhr.responseText) : null;
            const uploadedLesson = json?.lesson;
            if (uploadedLesson?.id) {
              const idx = lessons.value.findIndex(
                (l) => l.id === uploadedLesson.id,
              );
              if (idx >= 0) {
                lessons.value[idx] = {
                  ...lessons.value[idx],
                  video_bunny_id: uploadedLesson.video_bunny_id || null,
                  video_bunny_library_id:
                    uploadedLesson.video_bunny_library_id || null,
                  video_local_path: uploadedLesson.video_local_path || null,
                  video_url: uploadedLesson.video_url || null,
                  embed_url: uploadedLesson.embed_url || null,
                };
                const thumb = lessonThumbnail(lessons.value[idx]);
                if (thumb) {
                  lessonThumbs.value = {
                    ...lessonThumbs.value,
                    [lessonId]: thumb,
                  };
                  lessonThumbErrors.value = {
                    ...lessonThumbErrors.value,
                    [lessonId]: false,
                  };
                }
              }
            }
          } catch {
            // ignore parse errors
          }
          resolve();
        } else {
          reject(new Error(`Upload failed (${xhr.status})`));
        }
      };

      xhr.onerror = () => reject(new Error("Upload failed"));
      xhr.send(form);
    });

    await loadLessons();
    toastSuccess("Đã tải video bài học.");
    uploadStatus.value = { ...uploadStatus.value, [lessonId]: "success" };
  } catch (e) {
    error.value = extractMessage(e);
    toastError(error.value || "Tải video thất bại.");
    uploadStatus.value = { ...uploadStatus.value, [lessonId]: "error" };
  } finally {
    uploading.value = { ...uploading.value, [lessonId]: false };
    input.value = "";
  }
}

async function openBunnyPicker(lessonId: number) {
  bunnyPickerLessonId.value = lessonId;
  bunnyPickerOpen.value = true;
  bunnyError.value = null;
  bunnyPage.value = 1;
  try {
    // Load settings if not yet loaded (stream_hostname + pull_zone_url + video_library_id)
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
      if (cfgLibId) {
        bunnyConfiguredLibraryId.value = Number(cfgLibId);
      }
    }

    if (bunnyLibraries.value.length === 0) {
      const res = await apiFetch<{
        libraries: any;
        configured_library_id?: number | null;
      }>("/api/admin/bunny/libraries");
      // Handle both flat array and paginated { Items: [...] } response
      const raw = res.libraries;
      if (Array.isArray(raw)) {
        bunnyLibraries.value = raw;
      } else if (raw?.Items) {
        bunnyLibraries.value = raw.Items;
      } else if (raw?.items) {
        bunnyLibraries.value = raw.items;
      } else {
        bunnyLibraries.value = [];
      }
      // Use configured_library_id from backend if available
      if (res.configured_library_id) {
        bunnyConfiguredLibraryId.value = Number(res.configured_library_id);
      }
    }

    if (bunnyLibraries.value.length === 0) {
      bunnyError.value =
        "Không tìm thấy Video Library nào. Kiểm tra lại Account API Key tại Cài đặt → Bunny Storage → API Key.";
      return;
    }

    if (!bunnyLibraryId.value) {
      // Pre-select the CONFIGURED library (critical: video_api_key only works for this library)
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
      // Fallback to first library if configured one not found
      if (!bunnyLibraryId.value && bunnyLibraries.value.length > 0) {
        const first = bunnyLibraries.value[0];
        bunnyLibraryId.value = Number(first.id || first.Id || first.libraryId);
        bunnyCdnHostname.value = first.cdnHostname || "";
      }
    }

    await refreshBunnyVideos();
  } catch (e) {
    bunnyError.value = extractMessage(e);
  }
}

function closeBunnyPicker() {
  bunnyPickerOpen.value = false;
}

async function refreshBunnyVideos() {
  if (!bunnyLibraryId.value) {
    bunnyError.value =
      "Chưa chọn Video Library. Kiểm tra cấu hình Bunny tại Cài đặt.";
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

    // Capture CDN hostname from backend (fetched via Pull Zone API)
    bunnyCdnHostname.value = res.cdn_hostname || "";

    const payload = res.videos;
    const items = Array.isArray(payload)
      ? payload
      : payload?.items || payload?.Items || payload?.data || [];
    bunnyVideos.value = items;
    bunnyTotalItems.value =
      payload?.totalItems || payload?.TotalItems || payload?.total || 0;
  } catch (e) {
    bunnyError.value = extractMessage(e);
  } finally {
    bunnyLoading.value = false;
  }
}

async function uploadBunnyFromModal(event: Event) {
  const lessonId = bunnyPickerLessonId.value;
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!lessonId || !file) return;

  try {
    modalUploadName.value = file.name;
    modalUploadProgress.value = 0;
    modalUploading.value = true;

    const form = new FormData();
    form.append("video", file);
    form.append("storage_type", "bunny");

    await new Promise<void>((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("POST", `/api/lessons/${lessonId}/upload-video`);
      const token = localStorage.getItem("admin_token");
      if (token) xhr.setRequestHeader("Authorization", `Bearer ${token}`);
      xhr.setRequestHeader("Accept", "application/json");

      xhr.upload.onprogress = (e) => {
        if (!e.lengthComputable) return;
        modalUploadProgress.value = Math.round((e.loaded / e.total) * 100);
      };

      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const json = xhr.responseText ? JSON.parse(xhr.responseText) : null;
            const uploadedLesson = json?.lesson;
            if (uploadedLesson?.id) {
              const idx = lessons.value.findIndex(
                (l) => l.id === uploadedLesson.id,
              );
              if (idx >= 0) {
                lessons.value[idx] = {
                  ...lessons.value[idx],
                  video_bunny_id: uploadedLesson.video_bunny_id || null,
                  video_bunny_library_id:
                    uploadedLesson.video_bunny_library_id || null,
                  video_local_path: uploadedLesson.video_local_path || null,
                  video_url: uploadedLesson.video_url || null,
                  embed_url: uploadedLesson.embed_url || null,
                };
                const thumb = lessonThumbnail(lessons.value[idx]);
                if (thumb) {
                  lessonThumbs.value = {
                    ...lessonThumbs.value,
                    [lessonId]: thumb,
                  };
                  lessonThumbErrors.value = {
                    ...lessonThumbErrors.value,
                    [lessonId]: false,
                  };
                }
              }
            }
          } catch {
            // ignore parse errors
          }
          resolve();
        } else {
          reject(new Error(`Upload failed (${xhr.status})`));
        }
      };

      xhr.onerror = () => reject(new Error("Upload failed"));
      xhr.send(form);
    });

    await loadLessons();
    await refreshBunnyVideos();
    toastSuccess("Đã tải video bài học.");
  } catch (e) {
    bunnyError.value = extractMessage(e);
    toastError(bunnyError.value || "Tải video thất bại.");
  } finally {
    modalUploading.value = false;
    input.value = "";
  }
}

function goBunnyPage(page: number) {
  const next = Math.min(Math.max(1, page), bunnyTotalPages.value);
  bunnyPage.value = next;
  refreshBunnyVideos();
}

function bunnyThumbnail(video: any) {
  const direct = video?.thumbnailUrl || video?.thumbnail || video?.preview;
  if (direct) return direct;

  const file =
    video?.thumbnailFileName ||
    video?.thumbnail_filename ||
    video?.thumbnail_file_name ||
    null;
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

function lessonThumbnail(lesson: Lesson) {
  if (lessonThumbErrors.value[lesson.id]) return undefined;
  // Use saved thumbnail first
  if (lesson.thumbnail) return lesson.thumbnail;
  if (lessonThumbs.value[lesson.id]) return lessonThumbs.value[lesson.id];
  if (!lesson.video_bunny_id) return undefined;

  // Pick CDN hostname: per-library first, then global fallback
  const libId =
    lesson.video_bunny_library_id ||
    String(bunnyConfiguredLibraryId.value || "");
  let host = libId ? bunnyLibCdnMap.value[libId] : "";
  if (!host) {
    host =
      bunnyCdnHostname.value ||
      bunnyPullZoneUrl.value ||
      bunnyStreamHostname.value;
  }
  if (!host) return undefined;

  const base = host.startsWith("http") ? host : `https://${host}`;
  const normalized = base.replace(/\/+$/, "");
  return `${normalized}/${lesson.video_bunny_id}/thumbnail.jpg`;
}

function onLessonThumbError(lessonId: number) {
  lessonThumbErrors.value = { ...lessonThumbErrors.value, [lessonId]: true };
}

async function selectBunnyVideo(video: any) {
  const lessonId = bunnyPickerLessonId.value;
  if (!lessonId) return;

  const guid = video?.guid || video?.videoGuid || video?.id || null;
  if (!guid) {
    bunnyError.value = "Không lấy được GUID của video.";
    return;
  }

  // Build thumbnail URL from Bunny CDN
  const thumbUrl = bunnyThumbnail(video);

  const idx = lessons.value.findIndex((l) => l.id === lessonId);
  if (idx < 0) return;
  lessons.value[idx] = {
    ...lessons.value[idx],
    video_bunny_id: guid,
    video_bunny_library_id: bunnyLibraryId.value
      ? String(bunnyLibraryId.value)
      : null,
    thumbnail: thumbUrl || lessons.value[idx].thumbnail || null,
  } as Lesson;

  await updateLesson(lessons.value[idx]);
  const thumb = lessonThumbnail(lessons.value[idx]);
  if (thumb) {
    lessonThumbs.value = { ...lessonThumbs.value, [lessonId]: thumb };
    lessonThumbErrors.value = { ...lessonThumbErrors.value, [lessonId]: false };
  }
  toastSuccess("Đã gán Bunny GUID cho bài học.");
  closeBunnyPicker();
}

// image upload/selection happens via Media Library

async function init() {
  error.value = null;
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
      if (cfgLibId) {
        bunnyConfiguredLibraryId.value = Number(cfgLibId);
      }
    }
    // Load Bunny libraries to get CDN hostnames for thumbnails
    try {
      const libRes = await apiFetch<{
        libraries: any[];
        configured_library_id?: number | null;
      }>("/api/admin/bunny/libraries");
      if (libRes.libraries?.length) {
        bunnyLibraries.value = libRes.libraries;
        const map: Record<string, string> = {};
        for (const lib of libRes.libraries) {
          const id = String(lib.id || lib.Id || "");
          const cdn = lib.cdnHostname || "";
          if (id && cdn) map[id] = cdn;
        }
        bunnyLibCdnMap.value = map;
        // Set default CDN hostname
        const cfgId = String(bunnyConfiguredLibraryId.value || "");
        if (cfgId && map[cfgId]) {
          bunnyCdnHostname.value = map[cfgId];
        } else if (libRes.libraries[0]?.cdnHostname) {
          bunnyCdnHostname.value = libRes.libraries[0].cdnHostname;
        }
      }
    } catch {
      // Non-critical: thumbnails may not show but editing still works
    }
    await loadCategories();
    await loadCourse();
    await loadSections();
    await loadLessons();
    loadUserGroups();
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    error.value = extractMessage(e);
  }
}

onMounted(init);

// Duration tier functions
async function loadTiers() {
  if (isNew.value) return;
  loadingTiers.value = true;
  try {
    const res = await apiFetch<{ data: DurationTier[] }>(
      `/api/admin/courses/${courseId.value}/duration-tiers`,
    );
    tiers.value = res.data;
  } catch (e) {
    toastError(extractMessage(e));
  } finally {
    loadingTiers.value = false;
  }
}

async function createTier() {
  if (isNew.value) return;
  try {
    await apiFetch(`/api/admin/courses/${courseId.value}/duration-tiers`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        label: newTier.value.label,
        duration_days: newTier.value.duration_days || null,
        price: newTier.value.price,
        original_price: newTier.value.original_price || null,
        sort_order: tiers.value.length + 1,
      }),
    });
    newTier.value = { label: "", duration_days: null, price: 0, original_price: null };
    await loadTiers();
    toastSuccess("Đã thêm gói thời gian.");
  } catch (e) {
    toastError(extractMessage(e));
  }
}

async function updateTier(t: DurationTier) {
  try {
    await apiFetch(
      `/api/admin/courses/${courseId.value}/duration-tiers/${t.id}`,
      {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          label: t.label,
          duration_days: t.duration_days || null,
          price: t.price,
          original_price: t.original_price || null,
          sort_order: t.sort_order,
          is_default: t.is_default,
          is_active: t.is_active,
        }),
      },
    );
    toastSuccess("Đã cập nhật gói.");
  } catch (e) {
    toastError(extractMessage(e));
    await loadTiers();
  }
}

async function setDefaultTier(t: DurationTier) {
  // Unset all others, set this as default
  tiers.value.forEach((tier) => (tier.is_default = tier.id === t.id));
  try {
    await apiFetch(
      `/api/admin/courses/${courseId.value}/duration-tiers/${t.id}`,
      {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ is_default: true }),
      },
    );
    toastSuccess("Đã đặt gói mặc định.");
  } catch (e) {
    toastError(extractMessage(e));
    await loadTiers();
  }
}

async function deleteTier(t: DurationTier) {
  if (!confirm(`Xóa gói "${t.label}"?`)) return;
  try {
    await apiFetch(
      `/api/admin/courses/${courseId.value}/duration-tiers/${t.id}`,
      { method: "DELETE" },
    );
    await loadTiers();
    toastSuccess("Đã xóa gói.");
  } catch (e) {
    toastError(extractMessage(e));
  }
}

async function addPresetTier(label: string, days: number | null) {
  newTier.value.label = label;
  newTier.value.duration_days = days;
  newTier.value.price = 0;
  newTier.value.original_price = null;
  // Don't auto-submit, let user set price
}

// Tier target functions
async function loadUserGroups() {
  try {
    const res = await apiFetch<{ data: UserGroupOption[] }>('/api/admin/user-groups');
    userGroups.value = res.data || [];
  } catch {
    // ignore
  }
}

function openTierTargetModal(t: DurationTier) {
  tierTargetModal.value = { open: true, tierId: t.id, tierLabel: t.label };
  tierTargetSearch.value = '';
  tierTargetSearchResults.value = [];
}

function closeTierTargetModal() {
  tierTargetModal.value = { open: false, tierId: null, tierLabel: '' };
}

function getTierTargets(tierId: number): TierTarget[] {
  const tier = tiers.value.find(t => t.id === tierId);
  return tier?.targets || [];
}

function debouncedSearchTargetUsers() {
  if (tierTargetSearchTimer) clearTimeout(tierTargetSearchTimer);
  const q = tierTargetSearch.value.trim();
  if (q.length < 2) { tierTargetSearchResults.value = []; return; }
  tierTargetSearchTimer = setTimeout(() => searchTargetUsers(q), 300);
}

async function searchTargetUsers(q: string) {
  try {
    const res = await apiFetch<{ data: { id: number; name: string; email: string }[] }>(
      `/api/admin/users?search=${encodeURIComponent(q)}`
    );
    tierTargetSearchResults.value = res.data || [];
  } catch {
    tierTargetSearchResults.value = [];
  }
}

async function addTierTarget(tierId: number, targetType: 'user' | 'group', targetId: number) {
  const tier = tiers.value.find(t => t.id === tierId);
  if (!tier) return;
  const currentTargets = tier.targets || [];
  // Check duplicate
  if (currentTargets.some(t => t.target_type === targetType && t.target_id === targetId)) {
    toastError('Đối tượng này đã được thêm.');
    return;
  }
  const newTargets = [...currentTargets.map(t => ({ target_type: t.target_type, target_id: t.target_id })), { target_type: targetType, target_id: targetId }];
  try {
    await apiFetch(
      `/api/admin/courses/${courseId.value}/duration-tiers/${tierId}`,
      {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ targets: newTargets }),
      }
    );
    await loadTiers();
    toastSuccess('Đã thêm đối tượng.');
    tierTargetSearch.value = '';
    tierTargetSearchResults.value = [];
  } catch (e) {
    toastError(extractMessage(e));
  }
}

async function removeTierTarget(tierId: number, targetType: string, targetId: number) {
  const tier = tiers.value.find(t => t.id === tierId);
  if (!tier) return;
  const newTargets = (tier.targets || []).filter(t => !(t.target_type === targetType && t.target_id === targetId)).map(t => ({ target_type: t.target_type, target_id: t.target_id }));
  try {
    await apiFetch(
      `/api/admin/courses/${courseId.value}/duration-tiers/${tierId}`,
      {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ targets: newTargets }),
      }
    );
    await loadTiers();
    toastSuccess('Đã xóa đối tượng.');
  } catch (e) {
    toastError(extractMessage(e));
  }
}

function formatDuration(days: number): string {
  if (days >= 365) {
    const years = Math.round(days / 365 * 10) / 10;
    return years === 1 ? '1 năm' : `${years} năm`;
  }
  if (days >= 30) {
    const months = Math.round(days / 30);
    return `${months} tháng`;
  }
  return `${days} ngày`;
}
</script>

<style scoped>
/* Lesson Form Styles */
.lesson-form {
  background: #f8fafc;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 8px;
}

.lesson-form-row {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.lesson-form-row .label {
  margin: 0;
}

/* Lessons Table Styles */
.lessons-table {
  border-collapse: separate;
  border-spacing: 0;
}

.lessons-table thead th {
  background: #f1f5f9;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #475569;
  padding: 12px 10px;
  border-bottom: 2px solid #e2e8f0;
}

.lessons-table tbody tr {
  transition: background-color 0.15s ease;
}

.lessons-table tbody tr:hover {
  background-color: #f8fafc;
}

.lessons-table td {
  padding: 12px 10px;
  vertical-align: top;
  border-bottom: 1px solid #e2e8f0;
}

/* Input Styles */
.input-sm {
  padding: 6px 10px;
  font-size: 13px;
  height: 34px;
}

.lesson-title-input {
  width: 100%;
  font-weight: 500;
  font-size: 14px;
}

/* Video Upload Section */
.video-upload-section {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.video-upload-section input[type="file"] {
  flex: 1;
  min-width: 150px;
}

.upload-filename {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
}

.upload-status {
  margin-top: 6px;
}

.status-success {
  color: #16a34a;
  font-size: 12px;
}

.status-error {
  color: #dc2626;
  font-size: 12px;
}

.upload-progress {
  margin-top: 8px;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.progress-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  transition: width 0.2s ease;
}

.video-inputs {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-top: 8px;
}

.video-status {
  margin-top: 8px;
}

.lesson-thumb-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.lesson-thumb-preview {
  width: 80px;
  height: 45px;
  border-radius: 4px;
  overflow: hidden;
  background: #f1f5f9;
}

.lesson-thumb-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.lesson-thumb-actions {
  display: flex;
  gap: 4px;
}

.btn-xs {
  padding: 2px 6px;
  font-size: 11px;
  line-height: 1.2;
}

.status-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-embed {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-bunny {
  background: #fef3c7;
  color: #b45309;
}

.status-local {
  background: #dcfce7;
  color: #16a34a;
}

.status-none {
  background: #fef2f2;
  color: #dc2626;
}

/* Button Styles */
.btn-outline {
  background: white;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-outline:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}
</style>

<style scoped>
/* Tier Form Styles */
.tier-form {
  background: #f0fdf4;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 8px;
  border: 1px solid #bbf7d0;
}

.tier-form-row {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  flex-wrap: wrap;
}

.tier-form-row .label {
  margin: 0;
}

/* Tiers Table Styles */
.tiers-table {
  border-collapse: separate;
  border-spacing: 0;
}

.tiers-table thead th {
  background: #f0fdf4;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #166534;
  padding: 12px 10px;
  border-bottom: 2px solid #bbf7d0;
}

.tiers-table tbody tr {
  transition: background-color 0.15s ease;
}

.tiers-table tbody tr:hover {
  background-color: #f0fdf4;
}

.tiers-table td {
  padding: 10px 8px;
  vertical-align: middle;
  border-bottom: 1px solid #e2e8f0;
}
.tier-targets {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
}
.tier-target-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 999px;
  border: 1px solid var(--gray-300, #d1d5db);
  background: var(--gray-50, #f9fafb);
  font-size: 11px;
  line-height: 1.4;
}
.tier-target-remove {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--gray-400, #9ca3af);
  font-size: 14px;
  line-height: 1;
  padding: 0 2px;
}
.tier-target-remove:hover {
  color: #ef4444;
}
.search-dropdown {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  max-height: 200px;
  overflow-y: auto;
}
.search-dropdown-item {
  padding: 8px 12px;
  cursor: pointer;
  font-size: 13px;
}
.search-dropdown-item:hover {
  background: #f1f5f9;
}
</style>
