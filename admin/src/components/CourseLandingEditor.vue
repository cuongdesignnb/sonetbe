<template>
  <div class="card" style="margin-top: 12px">
    <h2 class="h2">Landing Page</h2>
    <p class="hint" style="margin-bottom: 16px">
      Cấu hình nội dung landing page cho khóa học. Mỗi section có thể bật/tắt
      riêng.
    </p>

    <div
      v-if="loading"
      class="text-muted"
      style="padding: 20px; text-align: center"
    >
      Đang tải...
    </div>

    <div v-else>
      <!-- Section tabs -->
      <div class="section-tabs">
        <button
          v-for="sec in sections"
          :key="sec.key"
          class="section-tab"
          :class="{ active: activeSection === sec.key }"
          @click="activeSection = sec.key"
        >
          {{ sec.label }}
        </button>
      </div>

      <!-- ═══════ Promo / Ưu đãi ═══════ -->
      <div v-if="activeSection === 'promo'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.promo.enabled" />
          <span>Bật banner ưu đãi (frontend sẽ animate nhảy)</span>
        </label>
        <label class="label">
          <span>Nội dung ưu đãi</span>
          <input
            v-model="marketing.promo.text"
            class="input"
            placeholder="🎁 ƯU ĐÃI: Giảm thêm 100K vì đã quay lại!"
          />
        </label>
      </div>

      <!-- ═══════ Landing Nav ═══════ -->
      <div v-if="activeSection === 'landing_nav'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.landing_nav.enabled" />
          <span>Bật landing nav (thay navbar mặc định)</span>
        </label>
        <label class="label">
          <span>Logo URL</span>
          <div style="display: flex; gap: 8px">
            <input
              v-model="marketing.landing_nav.logo_url"
              class="input"
              placeholder="URL logo"
            />
            <button
              type="button"
              class="btn btn-secondary"
              @click="pickImage('landing_nav_logo')"
            >
              Chọn ảnh
            </button>
          </div>
        </label>
      </div>

      <!-- ═══════ Hero ═══════ -->
      <div v-if="activeSection === 'hero'" class="section-form">
        <label class="label">
          <span>Tiêu đề chính (headline)</span>
          <input v-model="marketing.hero.headline" class="input" />
        </label>
        <label class="label">
          <span>Phụ đề (subheadline)</span>
          <input v-model="marketing.hero.subheadline" class="input" />
        </label>
        <label class="label">
          <span>Ảnh nền giảng viên (background_image)</span>
          <div style="display: flex; gap: 8px">
            <input
              v-model="marketing.hero.background_image"
              class="input"
              placeholder="URL ảnh nền"
            />
            <button
              type="button"
              class="btn btn-secondary"
              @click="pickImage('hero_bg')"
            >
              Chọn ảnh
            </button>
          </div>
        </label>
        <label class="label">
          <span>Bullet points (mỗi dòng 1 ý)</span>
          <textarea
            v-model="heroBulletsText"
            class="input"
            rows="4"
            placeholder="Hướng dẫn A-Z...&#10;Chiến lược content...&#10;Chạy quảng cáo..."
          ></textarea>
        </label>
        <label class="label">
          <span>Ảnh cards</span>
          <div style="display: flex; gap: 8px; margin-bottom: 8px">
            <button
              type="button"
              class="btn btn-secondary"
              @click="pickHeroCards"
            >
              + Chọn ảnh từ thư viện
            </button>
          </div>
          <div
            v-if="marketing.hero.images && marketing.hero.images.length > 0"
            class="hero-cards-grid"
          >
            <div
              v-for="(img, idx) in marketing.hero.images"
              :key="idx"
              class="hero-card-item"
            >
              <img :src="img" class="hero-card-thumb" />
              <button
                type="button"
                class="hero-card-remove"
                @click="removeHeroCard(Number(idx))"
                title="Xóa ảnh này"
              >
                ×
              </button>
            </div>
          </div>
          <p v-else class="muted" style="margin: 0; font-size: 13px">
            Chưa có ảnh nào. Nhấn nút trên để chọn từ thư viện.
          </p>
        </label>
        <label class="label">
          <span>Ticker texts (mỗi dòng 1 text)</span>
          <textarea
            v-model="tickerTextsText"
            class="input"
            rows="3"
            placeholder="Mỗi dòng 1 ticker text"
          ></textarea>
        </label>
        <label class="label">
          <span>CTA chính (nút đăng ký)</span>
          <input
            v-model="marketing.hero.cta_primary"
            class="input"
            placeholder="BẮT ĐẦU HÀNH TRÌNH"
          />
        </label>
        <label class="label">
          <span>CTA phụ (nút xem giới thiệu)</span>
          <input
            v-model="marketing.hero.cta_secondary"
            class="input"
            placeholder="Giới thiệu khóa học"
          />
        </label>
        <hr style="margin: 16px 0; opacity: 0.3" />
        <h4 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600">
          Social Proof (Rating)
        </h4>
        <div class="row" style="gap: 12px">
          <label class="label" style="flex: 1">
            <span>Số học viên (fake)</span>
            <input
              v-model="marketing.hero.fake_students"
              class="input"
              placeholder="1,000+"
            />
          </label>
          <label class="label" style="flex: 1">
            <span>Đánh giá (fake)</span>
            <input
              v-model="marketing.hero.fake_rating"
              class="input"
              type="number"
              step="0.1"
              min="0"
              max="5"
              placeholder="4.9"
            />
          </label>
        </div>
      </div>

      <!-- ═══════ Pain Point ═══════ -->
      <div v-if="activeSection === 'pain_point'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.pain_point.enabled" />
          <span>Bật section Pain Point</span>
        </label>
        <label class="label">
          <span>Tiêu đề</span>
          <input v-model="marketing.pain_point.title" class="input" />
        </label>
        <label class="label">
          <span>Highlight (từ sẽ tô đậm trong tiêu đề)</span>
          <input v-model="marketing.pain_point.highlight" class="input" />
        </label>
        <label class="label">
          <span>Mô tả</span>
          <textarea
            v-model="marketing.pain_point.description"
            class="input"
            rows="3"
          ></textarea>
        </label>
        <label class="label">
          <span>Video URL (YouTube embed)</span>
          <input
            v-model="marketing.pain_point.video_url"
            class="input"
            placeholder="https://www.youtube.com/embed/..."
          />
        </label>
        <label class="label">
          <span>Callout title</span>
          <input v-model="marketing.pain_point.callout_title" class="input" />
        </label>
        <label class="label">
          <span>Callout text</span>
          <textarea
            v-model="marketing.pain_point.callout_text"
            class="input"
            rows="2"
          ></textarea>
        </label>
        <label class="label">
          <span>CTA text</span>
          <input v-model="marketing.pain_point.cta_text" class="input" />
        </label>
        <label class="label">
          <span>Badges (JSON array)</span>
          <textarea
            v-model="painPointBadgesText"
            class="input"
            rows="3"
            placeholder='[{"text":"10K+","position":"top-left"}]'
          ></textarea>
        </label>
      </div>

      <!-- ═══════ Benefits ═══════ -->
      <div v-if="activeSection === 'benefits'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.benefits.enabled" />
          <span>Bật section Benefits</span>
        </label>
        <label class="label">
          <span>Badge</span>
          <input v-model="marketing.benefits.badge" class="input" />
        </label>
        <label class="label">
          <span>Tiêu đề</span>
          <input v-model="marketing.benefits.title" class="input" />
        </label>
        <label class="label">
          <span>Highlight</span>
          <input v-model="marketing.benefits.highlight" class="input" />
        </label>
        <label class="label">
          <span>Subtitle</span>
          <textarea
            v-model="marketing.benefits.subtitle"
            class="input"
            rows="2"
          ></textarea>
        </label>
        <label class="label">
          <span>CTA text</span>
          <input v-model="marketing.benefits.cta_text" class="input" />
        </label>
        <h3 class="h3" style="margin-top: 12px">Benefit items</h3>
        <div
          v-for="(item, idx) in marketing.benefits.items"
          :key="idx"
          class="nested-card"
        >
          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px">
            <label class="label"
              ><span>Title</span><input v-model="item.title" class="input"
            /></label>
            <label class="label"
              ><span>Color</span>
              <select v-model="item.color" class="input">
                <option value="purple">Purple</option>
                <option value="green">Green</option>
                <option value="blue">Blue</option>
                <option value="pink">Pink</option>
                <option value="orange">Orange</option>
                <option value="yellow">Yellow</option>
              </select>
            </label>
          </div>
          <label class="label"
            ><span>Description</span
            ><textarea
              v-model="item.description"
              class="input"
              rows="2"
            ></textarea>
          </label>
          <button
            type="button"
            class="btn btn-danger btn-sm"
            @click="marketing.benefits.items.splice(idx, 1)"
          >
            Xóa
          </button>
        </div>
        <button
          type="button"
          class="btn btn-secondary"
          @click="
            marketing.benefits.items.push({
              title: '',
              description: '',
              color: 'purple',
            })
          "
        >
          + Thêm benefit
        </button>
      </div>

      <!-- ═══════ Target Audience ═══════ -->
      <div v-if="activeSection === 'target_audience'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.target_audience.enabled" />
          <span>Bật section Target Audience</span>
        </label>
        <label class="label">
          <span>Tiêu đề</span>
          <input v-model="marketing.target_audience.title" class="input" />
        </label>
        <label class="label">
          <span>Highlight</span>
          <input v-model="marketing.target_audience.highlight" class="input" />
        </label>
        <label class="label">
          <span>Subtitle</span>
          <textarea
            v-model="marketing.target_audience.subtitle"
            class="input"
            rows="2"
          ></textarea>
        </label>
        <label class="label">
          <span>Closing quote</span>
          <textarea
            v-model="marketing.target_audience.closing_quote"
            class="input"
            rows="2"
          ></textarea>
        </label>
        <label class="label">
          <span>CTA text</span>
          <input v-model="marketing.target_audience.cta_text" class="input" />
        </label>
        <h3 class="h3" style="margin-top: 12px">Personas</h3>
        <div
          v-for="(p, idx) in marketing.target_audience.personas"
          :key="idx"
          class="nested-card"
        >
          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px">
            <label class="label"
              ><span>Title</span><input v-model="p.title" class="input"
            /></label>
            <label class="label"
              ><span>Color</span>
              <select v-model="p.color" class="input">
                <option value="orange">Orange</option>
                <option value="blue">Blue</option>
                <option value="green">Green</option>
                <option value="purple">Purple</option>
                <option value="pink">Pink</option>
              </select>
            </label>
          </div>
          <label class="label"
            ><span>Description</span
            ><textarea
              v-model="p.description"
              class="input"
              rows="2"
            ></textarea>
          </label>
          <button
            type="button"
            class="btn btn-danger btn-sm"
            @click="marketing.target_audience.personas.splice(idx, 1)"
          >
            Xóa
          </button>
        </div>
        <button
          type="button"
          class="btn btn-secondary"
          @click="
            marketing.target_audience.personas.push({
              title: '',
              description: '',
              color: 'orange',
            })
          "
        >
          + Thêm persona
        </button>
      </div>

      <!-- ═══════ Before/After ═══════ -->
      <div v-if="activeSection === 'before_after'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.before_after.enabled" />
          <span>Bật section Before/After</span>
        </label>
        <label class="label">
          <span>Tiêu đề</span>
          <input v-model="marketing.before_after.title" class="input" />
        </label>
        <label class="label">
          <span>Subtitle</span>
          <input v-model="marketing.before_after.subtitle" class="input" />
        </label>
        <div
          class="grid"
          style="grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 12px"
        >
          <div>
            <h3 class="h3">Before</h3>
            <label class="label"
              ><span>Title</span
              ><input
                v-model="marketing.before_after.before.title"
                class="input"
            /></label>
            <label class="label"
              ><span>Items (mỗi dòng 1 item)</span>
              <textarea
                v-model="beforeItemsText"
                class="input"
                rows="5"
              ></textarea>
            </label>
          </div>
          <div>
            <h3 class="h3">After</h3>
            <label class="label"
              ><span>Title</span
              ><input
                v-model="marketing.before_after.after.title"
                class="input"
            /></label>
            <label class="label"
              ><span>Items (mỗi dòng 1 item)</span>
              <textarea
                v-model="afterItemsText"
                class="input"
                rows="5"
              ></textarea>
            </label>
            <label class="label"
              ><span>Recommended badge</span
              ><input
                v-model="marketing.before_after.after.recommended_badge"
                class="input"
            /></label>
          </div>
        </div>
        <label class="label" style="margin-top: 12px">
          <span>Bottom note</span>
          <input v-model="marketing.before_after.bottom_note" class="input" />
        </label>
      </div>

      <!-- ═══════ Workflow ═══════ -->
      <div v-if="activeSection === 'workflow'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.workflow.enabled" />
          <span>Bật module workflow (lộ trình 3 pha)</span>
        </label>
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px">
          <label class="label"
            ><span>Badge</span
            ><input
              v-model="marketing.workflow.badge"
              class="input"
              placeholder="Workflow tăng trưởng"
          /></label>
          <label class="label"
            ><span>Title</span
            ><input
              v-model="marketing.workflow.title"
              class="input"
              placeholder="Lộ trình 3 pha để nhân doanh thu"
          /></label>
        </div>
        <label class="label">
          <span>Subtitle</span>
          <textarea
            v-model="marketing.workflow.subtitle"
            class="input"
            rows="2"
            placeholder="Mỗi pha có checklist, KPI..."
          ></textarea>
        </label>
        <label class="label">
          <span>Album ảnh bằng chứng (mỗi dòng 1 URL)</span>
          <textarea
            v-model="workflowAlbumText"
            class="input"
            rows="3"
            placeholder="Mỗi dòng 1 URL ảnh"
          ></textarea>
        </label>
        <h3 class="h3" style="margin-top: 12px">3 bước Workflow</h3>
        <div
          v-for="(step, idx) in marketing.workflow.steps"
          :key="idx"
          class="nested-card"
        >
          <div
            class="grid"
            style="grid-template-columns: 1fr 1fr 1fr; gap: 8px"
          >
            <label class="label"
              ><span>Step {{ Number(idx) + 1 }} title</span
              ><input v-model="step.title" class="input"
            /></label>
            <label class="label"
              ><span>Desc</span><input v-model="step.desc" class="input"
            /></label>
            <label class="label"
              ><span>Tag</span><input v-model="step.tag" class="input"
            /></label>
          </div>
        </div>
      </div>

      <!-- ═══════ Instructor Extra ═══════ -->
      <div v-if="activeSection === 'instructor_extra'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.instructor_extra.enabled" />
          <span>Bật section Instructor</span>
        </label>
        <label class="label"
          ><span>Badge</span
          ><input v-model="marketing.instructor_extra.badge" class="input"
        /></label>
        <label class="label"
          ><span>Label</span
          ><input v-model="marketing.instructor_extra.label" class="input"
        /></label>
        <label class="label"
          ><span>Title (Name - Role)</span
          ><input v-model="marketing.instructor_extra.title" class="input"
        /></label>
        <label class="label"
          ><span>Bio</span
          ><textarea
            v-model="marketing.instructor_extra.bio_extended"
            class="input"
            rows="3"
          ></textarea>
        </label>
        <label class="label"
          ><span>Expertise (mỗi dòng 1 item)</span>
          <textarea v-model="expertiseText" class="input" rows="3"></textarea>
        </label>
        <label class="label"
          ><span>Closing quote</span
          ><input
            v-model="marketing.instructor_extra.closing_quote"
            class="input"
        /></label>
        <label class="label">
          <span>Image URL</span>
          <div style="display: flex; gap: 8px">
            <input v-model="marketing.instructor_extra.image" class="input" />
            <button
              type="button"
              class="btn btn-secondary"
              @click="pickImage('instructor_image')"
            >
              Chọn ảnh
            </button>
          </div>
        </label>
        <label class="label"
          ><span>CTA text</span
          ><input v-model="marketing.instructor_extra.cta_text" class="input"
        /></label>
        <h3 class="h3" style="margin-top: 12px">Thành tựu</h3>
        <div
          v-for="(a, idx) in marketing.instructor_extra.achievements"
          :key="idx"
          class="nested-card"
          style="display: flex; gap: 8px; align-items: end"
        >
          <label class="label" style="flex: 1"
            ><span>Value</span><input v-model="a.value" class="input"
          /></label>
          <label class="label" style="flex: 1"
            ><span>Label</span><input v-model="a.label" class="input"
          /></label>
          <button
            type="button"
            class="btn btn-danger btn-sm"
            @click="marketing.instructor_extra.achievements.splice(idx, 1)"
          >
            Xóa
          </button>
        </div>
        <button
          type="button"
          class="btn btn-secondary"
          @click="
            marketing.instructor_extra.achievements.push({
              value: '',
              label: '',
            })
          "
        >
          + Thêm thành tựu
        </button>
      </div>

      <!-- ═══════ Curriculum (Nội dung khóa học) ═══════ -->
      <div v-if="activeSection === 'curriculum'" class="section-form">
        <p class="muted" style="margin-top: 0">
          Tiêu đề "Nội dung khóa học" và đoạn mô tả hiển thị trên trang chi
          tiết khóa học.
        </p>
        <label class="label">
          <span>Tiêu đề phụ (subtitle)</span>
          <input
            v-model="marketing.curriculum.subtitle"
            class="input"
            placeholder="Lộ trình học từ A-Z để làm video marketing chuyên nghiệp"
          />
        </label>
      </div>

      <!-- ═══════ Testimonials ═══════ -->
      <div v-if="activeSection === 'testimonials'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.testimonials.enabled" />
          <span>Bật section Testimonials</span>
        </label>
        <label class="label"
          ><span>Tiêu đề</span
          ><input v-model="marketing.testimonials.title" class="input"
        /></label>
        <label class="label"
          ><span>Feedback title</span
          ><input v-model="marketing.testimonials.feedback_title" class="input"
        /></label>
        <label class="label"
          ><span>Gallery title</span
          ><input v-model="marketing.testimonials.gallery_title" class="input"
        /></label>
        <label class="label"
          ><span>CTA Button Text (phía dưới)</span
          ><input
            v-model="marketing.testimonials.cta_text"
            class="input"
            placeholder="ĐĂNG KÝ NGAY ĐỂ CÓ KẾT QUẢ TƯƠNG TỰ"
        /></label>

        <h3 class="h3" style="margin-top: 12px">Video testimonials</h3>
        <div
          v-for="(v, idx) in marketing.testimonials.videos"
          :key="idx"
          class="nested-card"
        >
          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px">
            <label class="label"
              ><span>Thumbnail</span>
              <div style="display: flex; gap: 8px">
                <input v-model="v.thumbnail" class="input" />
                <button
                  type="button"
                  class="btn btn-secondary btn-sm"
                  @click="pickImage('testimonial_thumb_' + idx)"
                >
                  Ảnh
                </button>
              </div>
            </label>
            <label class="label"
              ><span>Video URL</span>
              <div style="display: flex; gap: 8px">
                <input v-model="v.video_url" class="input" />
                <button
                  type="button"
                  class="btn btn-secondary btn-sm"
                  @click="pickVideo('testimonial_video_' + idx)"
                >
                  Video
                </button>
              </div>
            </label>
          </div>
          <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px">
            <label class="label"
              ><span>Caption</span><input v-model="v.caption" class="input"
            /></label>
            <label class="label"
              ><span>Subcaption</span
              ><input v-model="v.subcaption" class="input"
            /></label>
          </div>
          <button
            type="button"
            class="btn btn-danger btn-sm"
            @click="marketing.testimonials.videos.splice(idx, 1)"
          >
            Xóa
          </button>
        </div>
        <button
          type="button"
          class="btn btn-secondary"
          @click="
            marketing.testimonials.videos.push({
              thumbnail: '',
              video_url: '',
              caption: '',
              subcaption: '',
            })
          "
        >
          + Thêm video
        </button>

        <label class="label" style="margin-top: 12px">
          <span>Feedback images</span>
        </label>
        <div
          v-if="marketing.testimonials.feedback_images?.length"
          class="media-thumb-grid"
        >
          <div
            v-for="(url, idx) in marketing.testimonials.feedback_images"
            :key="'fb_' + idx + '_' + url"
            class="media-thumb"
          >
            <img :src="url" alt="feedback" />
            <button
              type="button"
              class="media-thumb-remove"
              title="Xóa"
              @click="marketing.testimonials.feedback_images.splice(idx, 1)"
            >
              ×
            </button>
          </div>
        </div>
        <div class="row" style="gap: 8px; margin-top: 8px">
          <button
            type="button"
            class="btn btn-secondary"
            @click="feedbackImagesPicker = true"
          >
            Chọn ảnh từ Media Library
          </button>
          <button
            v-if="marketing.testimonials.feedback_images?.length"
            type="button"
            class="btn btn-secondary"
            @click="marketing.testimonials.feedback_images = []"
          >
            Xóa tất cả
          </button>
        </div>

        <label class="label" style="margin-top: 16px">
          <span>Gallery images</span>
        </label>
        <div
          v-if="marketing.testimonials.gallery_images?.length"
          class="media-thumb-grid"
        >
          <div
            v-for="(url, idx) in marketing.testimonials.gallery_images"
            :key="'gl_' + idx + '_' + url"
            class="media-thumb"
          >
            <img :src="url" alt="gallery" />
            <button
              type="button"
              class="media-thumb-remove"
              title="Xóa"
              @click="marketing.testimonials.gallery_images.splice(idx, 1)"
            >
              ×
            </button>
          </div>
        </div>
        <div class="row" style="gap: 8px; margin-top: 8px">
          <button
            type="button"
            class="btn btn-secondary"
            @click="galleryImagesPicker = true"
          >
            Chọn ảnh từ Media Library
          </button>
          <button
            v-if="marketing.testimonials.gallery_images?.length"
            type="button"
            class="btn btn-secondary"
            @click="marketing.testimonials.gallery_images = []"
          >
            Xóa tất cả
          </button>
        </div>
      </div>

      <!-- ═══════ Final CTA ═══════ -->
      <div v-if="activeSection === 'final_cta'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.final_cta.enabled" />
          <span>Bật section Final CTA</span>
        </label>
        <label class="label"
          ><span>Title</span
          ><input v-model="marketing.final_cta.title" class="input"
        /></label>
        <label class="label"
          ><span>Subtitle</span
          ><input v-model="marketing.final_cta.subtitle" class="input"
        /></label>
        <label class="label"
          ><span>CTA text</span
          ><input v-model="marketing.final_cta.cta_text" class="input"
        /></label>
        <label class="label"
          ><span>Social proof note</span
          ><input v-model="marketing.final_cta.social_proof" class="input"
        /></label>
      </div>

      <!-- ═══════ Urgency ═══════ -->
      <div v-if="activeSection === 'urgency'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.urgency.enabled" />
          <span>Bật urgency (thông báo khẩn cấp)</span>
        </label>
        <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 8px">
          <label class="label"
            ><span>Total spots</span
            ><input
              type="number"
              v-model.number="marketing.urgency.total_spots"
              class="input"
          /></label>
          <label class="label"
            ><span>Remaining spots</span
            ><input
              type="number"
              v-model.number="marketing.urgency.remaining_spots"
              class="input"
          /></label>
        </div>
        <label class="label"
          ><span>Countdown to (ISO date)</span
          ><input
            v-model="marketing.urgency.countdown_to"
            class="input"
            placeholder="2026-04-01T00:00:00"
        /></label>
      </div>

      <!-- ═══════ Stats ═══════ -->
      <div v-if="activeSection === 'stats'" class="section-form">
        <p class="hint">3 chỉ số hiển thị ở hero section.</p>
        <div
          v-for="(stat, idx) in marketing.stats"
          :key="idx"
          class="nested-card"
          style="display: flex; gap: 8px; align-items: end"
        >
          <label class="label" style="flex: 1"
            ><span>Value {{ Number(idx) + 1 }}</span
            ><input v-model="stat.value" class="input" placeholder="5000+"
          /></label>
          <label class="label" style="flex: 1"
            ><span>Label {{ Number(idx) + 1 }}</span
            ><input v-model="stat.label" class="input" placeholder="Học viên"
          /></label>
        </div>
      </div>

      <!-- ═══════ Floating Bar ═══════ -->
      <div v-if="activeSection === 'floating_bar'" class="section-form">
        <label
          class="label"
          style="display: flex; align-items: center; gap: 10px"
        >
          <input type="checkbox" v-model="marketing.floating_bar.enabled" />
          <span>Bật floating bar (thanh CTA dưới cùng)</span>
        </label>
        <label class="label"
          ><span>Viewer count</span
          ><input
            type="number"
            v-model.number="marketing.floating_bar.viewer_count"
            class="input"
        /></label>
      </div>

      <!-- Save button -->
      <div style="margin-top: 20px; display: flex; gap: 8px">
        <button v-if="!saving" class="btn" @click="saveMarketing">
          Lưu Landing Page
        </button>
        <button v-else class="btn" disabled>Đang lưu…</button>
      </div>

      <div v-if="saveError" class="alert" style="margin-top: 12px">
        {{ saveError }}
      </div>
      <div
        v-if="saveSuccess"
        class="alert alert-success"
        style="margin-top: 12px"
      >
        {{ saveSuccess }}
      </div>
    </div>

    <!-- Media Picker (Single) -->
    <MediaPickerModal
      v-if="mediaPicker"
      :open="mediaPicker"
      @close="mediaPicker = false"
      @select="onMediaSelected"
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
              <option value="views">Lượt xem</option>
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

    <!-- Media Picker (Multiple - for hero cards) -->
    <MediaPickerModal
      v-if="heroCardsPicker"
      :open="heroCardsPicker"
      :multiple="true"
      @close="heroCardsPicker = false"
      @select="onHeroCardsSelected"
    />

    <!-- Media Picker (Multiple - for testimonials feedback images) -->
    <MediaPickerModal
      v-if="feedbackImagesPicker"
      :open="feedbackImagesPicker"
      :multiple="true"
      @close="feedbackImagesPicker = false"
      @select="onFeedbackImagesSelected"
    />

    <!-- Media Picker (Multiple - for testimonials gallery images) -->
    <MediaPickerModal
      v-if="galleryImagesPicker"
      :open="galleryImagesPicker"
      :multiple="true"
      @close="galleryImagesPicker = false"
      @select="onGalleryImagesSelected"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import { useToast } from "../lib/toast";
import MediaPickerModal from "../components/MediaPickerModal.vue";

const props = defineProps<{
  courseId: number;
}>();

const { success: toastSuccess, error: toastError } = useToast();

const loading = ref(true);
const saving = ref(false);
const saveError = ref<string | null>(null);
const saveSuccess = ref<string | null>(null);
const mediaPicker = ref(false);
const mediaPickerTarget = ref("");
const bunnyPickerOpen = ref(false);
const bunnyPickerTarget = ref("");
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
const heroCardsPicker = ref(false);
const feedbackImagesPicker = ref(false);
const galleryImagesPicker = ref(false);
const activeSection = ref("promo");

const sections = [
  { key: "promo", label: "Ưu đãi" },
  { key: "landing_nav", label: "Nav" },
  { key: "hero", label: "Hero" },
  { key: "pain_point", label: "Pain Point" },
  { key: "benefits", label: "Benefits" },
  { key: "target_audience", label: "Target Audience" },
  { key: "before_after", label: "Before/After" },
  { key: "workflow", label: "Workflow" },
  { key: "instructor_extra", label: "Instructor" },
  { key: "curriculum", label: "Curriculum" },
  { key: "testimonials", label: "Testimonials" },
  { key: "final_cta", label: "Final CTA" },
  { key: "urgency", label: "Urgency" },
  { key: "stats", label: "Stats" },
  { key: "floating_bar", label: "Floating Bar" },
];

// Default empty structures for each section
function defaultMarketing(): Record<string, any> {
  return {
    promo: { enabled: false, text: "" },
    landing_nav: { enabled: false, logo_url: "" },
    hero: {
      headline: "",
      subheadline: "",
      background_image: "",
      images: [],
      cards: [],
      bulletsText: "",
      cta_primary: "",
      cta_secondary: "",
      fake_students: "",
      fake_rating: "",
    },
    ticker_texts: [],
    workflow: {
      enabled: false,
      badge: "",
      title: "",
      subtitle: "",
      album: [] as string[],
      steps: [
        { title: "", desc: "", tag: "" },
        { title: "", desc: "", tag: "" },
        { title: "", desc: "", tag: "" },
      ],
    },
    stats: [
      { value: "", label: "" },
      { value: "", label: "" },
      { value: "", label: "" },
    ],
    pain_point: {
      enabled: false,
      title: "",
      highlight: "",
      description: "",
      video_url: "",
      callout_title: "",
      callout_text: "",
      cta_text: "",
      badges: [],
      community_note: "",
    },
    benefits: {
      enabled: false,
      badge: "",
      title: "",
      highlight: "",
      subtitle: "",
      items: [],
      cta_text: "",
    },
    target_audience: {
      enabled: false,
      title: "",
      highlight: "",
      subtitle: "",
      personas: [],
      closing_quote: "",
      cta_text: "",
    },
    before_after: {
      enabled: false,
      title: "",
      subtitle: "",
      before: { title: "", items: [] },
      after: { title: "", items: [], recommended_badge: "" },
      bottom_note: "",
    },
    instructor_extra: {
      enabled: false,
      badge: "",
      label: "",
      title: "",
      bio_extended: "",
      expertise: [],
      closing_quote: "",
      achievements: [],
      image: "",
      cta_text: "",
    },
    testimonials: {
      enabled: false,
      title: "",
      videos: [],
      feedback_title: "",
      feedback_images: [],
      gallery_title: "",
      gallery_images: [],
      cta_text: "",
    },
    curriculum: {
      subtitle: "",
    },
    final_cta: {
      enabled: false,
      title: "",
      subtitle: "",
      cta_text: "",
      social_proof: "",
    },
    urgency: {
      enabled: false,
      total_spots: 0,
      remaining_spots: 0,
      countdown_to: "",
    },
    floating_bar: { enabled: false, viewer_count: 0 },
  };
}

const marketing = ref<Record<string, any>>(defaultMarketing());

// Computed text helpers (textarea <-> array)
const heroBulletsText = computed({
  get: () => (marketing.value.hero?.bullets || []).join("\n"),
  set: (v: string) => {
    marketing.value.hero.bullets = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const tickerTextsText = computed({
  get: () => (marketing.value.ticker_texts || []).join("\n"),
  set: (v: string) => {
    marketing.value.ticker_texts = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const workflowAlbumText = computed({
  get: () => (marketing.value.workflow?.album || []).join("\n"),
  set: (v: string) => {
    marketing.value.workflow.album = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const painPointBadgesText = computed({
  get: () => {
    try {
      return JSON.stringify(marketing.value.pain_point?.badges || [], null, 2);
    } catch {
      return "[]";
    }
  },
  set: (v: string) => {
    try {
      marketing.value.pain_point.badges = JSON.parse(v);
    } catch {
      /* ignore parse errors while typing */
    }
  },
});

const beforeItemsText = computed({
  get: () => (marketing.value.before_after?.before?.items || []).join("\n"),
  set: (v: string) => {
    marketing.value.before_after.before.items = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const afterItemsText = computed({
  get: () => (marketing.value.before_after?.after?.items || []).join("\n"),
  set: (v: string) => {
    marketing.value.before_after.after.items = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const expertiseText = computed({
  get: () => (marketing.value.instructor_extra?.expertise || []).join("\n"),
  set: (v: string) => {
    marketing.value.instructor_extra.expertise = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const feedbackImagesText = computed({
  get: () => (marketing.value.testimonials?.feedback_images || []).join("\n"),
  set: (v: string) => {
    marketing.value.testimonials.feedback_images = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});

const galleryImagesText = computed({
  get: () => (marketing.value.testimonials?.gallery_images || []).join("\n"),
  set: (v: string) => {
    marketing.value.testimonials.gallery_images = v
      .split("\n")
      .map((s) => s.trim())
      .filter(Boolean);
  },
});




function onFeedbackImagesSelected(assets: any) {
  const urls: string[] = Array.isArray(assets)
    ? assets.map((a: any) => a?.url).filter(Boolean)
    : assets?.url
      ? [assets.url]
      : [];
  if (urls.length > 0) {
    marketing.value.testimonials.feedback_images = [
      ...(marketing.value.testimonials.feedback_images || []),
      ...urls,
    ];
  }
  feedbackImagesPicker.value = false;
}

function onGalleryImagesSelected(assets: any) {
  const urls: string[] = Array.isArray(assets)
    ? assets.map((a: any) => a?.url).filter(Boolean)
    : assets?.url
      ? [assets.url]
      : [];
  if (urls.length > 0) {
    marketing.value.testimonials.gallery_images = [
      ...(marketing.value.testimonials.gallery_images || []),
      ...urls,
    ];
  }
  galleryImagesPicker.value = false;
}

function pickImage(target: string) {
  mediaPickerTarget.value = target;
  mediaPicker.value = true;
}

function onMediaSelected(asset: any) {
  const url = Array.isArray(asset) ? asset[0]?.url : asset?.url;
  if (!url) return;

  if (mediaPickerTarget.value === "landing_nav_logo") {
    marketing.value.landing_nav.logo_url = url;
  } else if (mediaPickerTarget.value === "hero_bg") {
    marketing.value.hero.background_image = url;
  } else if (mediaPickerTarget.value === "instructor_image") {
    marketing.value.instructor_extra.image = url;
  } else if (mediaPickerTarget.value.startsWith("testimonial_thumb_")) {
    const idx = parseInt(
      mediaPickerTarget.value.replace("testimonial_thumb_", ""),
    );
    if (marketing.value.testimonials.videos[idx]) {
      marketing.value.testimonials.videos[idx].thumbnail = url;
    }
  }

  mediaPicker.value = false;
}

// ── Bunny Video Picker ──
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

const bunnyTotalPages = computed(() => {
  if (!bunnyTotalItems.value || !bunnyItemsPerPage.value) return 1;
  return Math.max(
    1,
    Math.ceil(bunnyTotalItems.value / bunnyItemsPerPage.value),
  );
});

async function pickVideo(target: string) {
  bunnyPickerTarget.value = target;
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
      bunnyError.value =
        "Không tìm thấy Video Library nào. Kiểm tra lại API Key tại Cài đặt.";
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
  } catch (e) {
    bunnyError.value = extractMessage(e);
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
  } catch (e) {
    bunnyError.value = extractMessage(e);
  } finally {
    bunnyLoading.value = false;
  }
}

function goBunnyPage(page: number) {
  bunnyPage.value = Math.min(Math.max(1, page), bunnyTotalPages.value);
  refreshBunnyVideos();
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

  // Build embed/iframe URL from Bunny Stream
  const host = bunnyStreamHostname.value || bunnyPullZoneUrl.value;
  const base = host?.startsWith("http") ? host : `https://${host}`;
  const embedUrl = `${base.replace(/\/+$/, "")}/${guid}/play`;
  // Also build an iframe URL for direct Bunny embed
  const iframeUrl = `https://iframe.mediadelivery.net/embed/${bunnyLibraryId.value}/${guid}`;

  if (bunnyPickerTarget.value.startsWith("testimonial_video_")) {
    const idx = parseInt(
      bunnyPickerTarget.value.replace("testimonial_video_", ""),
    );
    if (marketing.value.testimonials.videos[idx]) {
      marketing.value.testimonials.videos[idx].video_url = iframeUrl;
    }
  }

  toastSuccess("Đã chọn video từ Bunny.");
  bunnyPickerOpen.value = false;
}

function pickHeroCards() {
  heroCardsPicker.value = true;
}

function onHeroCardsSelected(assets: any) {
  const urls: string[] = Array.isArray(assets)
    ? assets.map((a: any) => a.url).filter(Boolean)
    : assets?.url
      ? [assets.url]
      : [];
  if (urls.length > 0) {
    marketing.value.hero.images = [
      ...(marketing.value.hero.images || []),
      ...urls,
    ];
  }
  heroCardsPicker.value = false;
}

function removeHeroCard(idx: number) {
  if (marketing.value.hero.images) {
    marketing.value.hero.images.splice(idx, 1);
  }
}

async function loadMarketing() {
  loading.value = true;
  try {
    const res = await apiFetch<{ marketing: Record<string, any> }>(
      `/api/admin/courses/${props.courseId}/marketing`,
    );
    const data = res.marketing || {};
    const defaults = defaultMarketing();

    // Deep merge: use server data but ensure all keys exist
    for (const key of Object.keys(defaults)) {
      if (data[key] !== undefined && data[key] !== null) {
        if (
          typeof defaults[key] === "object" &&
          !Array.isArray(defaults[key])
        ) {
          marketing.value[key] = { ...defaults[key], ...data[key] };
          // Ensure nested arrays exist
          if (key === "before_after") {
            marketing.value[key].before = {
              ...defaults[key].before,
              ...(data[key]?.before || {}),
            };
            marketing.value[key].after = {
              ...defaults[key].after,
              ...(data[key]?.after || {}),
            };
          }
          // Ensure workflow.steps always has 3 entries
          if (key === "workflow") {
            const steps = Array.isArray(data[key]?.steps)
              ? data[key].steps
              : [];
            marketing.value[key].steps = [
              { title: "", desc: "", tag: "", ...(steps[0] || {}) },
              { title: "", desc: "", tag: "", ...(steps[1] || {}) },
              { title: "", desc: "", tag: "", ...(steps[2] || {}) },
            ];
          }
        } else {
          marketing.value[key] = data[key];
        }
      }
    }

    // Ensure stats always has 3 entries
    const statsArr = Array.isArray(marketing.value.stats)
      ? marketing.value.stats
      : [];
    marketing.value.stats = [
      { value: "", label: "", ...(statsArr[0] || {}) },
      { value: "", label: "", ...(statsArr[1] || {}) },
      { value: "", label: "", ...(statsArr[2] || {}) },
    ];

    // Map hero bullets to bulletsText for the textarea
    if (Array.isArray(data.hero?.bullets)) {
      marketing.value.hero.bulletsText = data.hero.bullets.join("\n");
    }
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    toastError(extractMessage(e));
  } finally {
    loading.value = false;
  }
}

async function saveMarketing() {
  saving.value = true;
  saveError.value = null;
  saveSuccess.value = null;

  try {
    // Convert bulletsText to bullets array for hero section
    const payload = JSON.parse(JSON.stringify(marketing.value));
    if (payload.hero?.bulletsText !== undefined) {
      payload.hero.bullets = (payload.hero.bulletsText || "")
        .split("\n")
        .map((s: string) => s.trim())
        .filter(Boolean);
      delete payload.hero.bulletsText;
    }
    // Filter empty stats
    if (Array.isArray(payload.stats)) {
      payload.stats = payload.stats.filter((s: any) => s.value && s.label);
    }
    // Filter empty workflow steps
    if (Array.isArray(payload.workflow?.steps)) {
      payload.workflow.steps = payload.workflow.steps.filter(
        (s: any) => s.title || s.desc || s.tag,
      );
    }

    await apiFetch(`/api/admin/courses/${props.courseId}/marketing`, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ marketing: payload }),
    });
    saveSuccess.value = "Đã lưu landing page thành công!";
    toastSuccess("Đã lưu landing page!");
  } catch (e) {
    if (e instanceof HttpError && e.status === 401) {
      location.href = "/admin/login";
      return;
    }
    saveError.value = extractMessage(e);
    toastError(saveError.value || "Lưu thất bại");
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  loadMarketing();
});
</script>

<style scoped>
.section-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 8px;
}
.section-tab {
  padding: 6px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
  font-size: 13px;
  transition: all 0.15s;
}
.section-tab:hover {
  background: #f3f4f6;
}
.section-tab.active {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}
.section-form {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.nested-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
  background: #fafafa;
}
.hint {
  color: #6b7280;
  font-size: 14px;
}
.text-muted {
  color: #9ca3af;
}
.alert-success {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  padding: 8px 12px;
  border-radius: 6px;
}
.h3 {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 8px;
}
.btn-sm {
  padding: 4px 10px;
  font-size: 12px;
}
.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
}
.btn-danger:hover {
  background: #dc2626;
}

/* Hero cards grid */
.hero-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 8px;
  margin-top: 8px;
}
.hero-card-item {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 3/4;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
}
.hero-card-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.hero-card-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: bold;
  line-height: 1;
  opacity: 0;
  transition: opacity 0.15s;
}
.hero-card-item:hover .hero-card-remove {
  opacity: 1;
}
.hero-card-remove:hover {
  background: #dc2626;
}

/* Generic media thumbnail grid (used for testimonials feedback/gallery) */
.media-thumb-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
  margin-top: 8px;
}
.media-thumb {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  aspect-ratio: 1/1;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
}
.media-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.media-thumb-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: bold;
  line-height: 1;
  opacity: 0;
  transition: opacity 0.15s;
}
.media-thumb:hover .media-thumb-remove {
  opacity: 1;
}
.media-thumb-remove:hover {
  background: #dc2626;
}

/* Bunny Modal */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal {
  background: white;
  border-radius: 12px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
}
.modal-title {
  font-weight: 600;
  font-size: 16px;
}
.modal-body {
  padding: 16px 20px;
  overflow-y: auto;
  flex: 1;
}
.modal-footer {
  display: flex;
  padding: 12px 20px;
  border-top: 1px solid #e5e7eb;
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
  font-size: 12px;
  text-transform: uppercase;
  color: #64748b;
}
.table tbody tr:hover {
  background: #f8fafc;
}
.row {
  display: flex;
  align-items: center;
}
.error {
  color: #dc2626;
  font-size: 14px;
}
.muted {
  color: #9ca3af;
  font-size: 13px;
}
</style>
