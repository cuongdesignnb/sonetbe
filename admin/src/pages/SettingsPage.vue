<template>
  <div>
    <div
      class="row"
      style="justify-content: space-between; align-items: center"
    >
      <h1 class="h1">Cài đặt</h1>
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

    <!-- Tabs Navigation -->
    <div class="tabs" style="margin-top: 16px">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        class="tab-btn"
        :class="{ active: activeTab === tab.id }"
        @click="activeTab = tab.id"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" style="margin-top: 16px">
      <!-- Tab: Thương hiệu -->
      <div
        v-if="activeTab === 'brand'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Thông tin cơ bản</h2>
          <p class="muted" style="margin-top: -6px">
            Tên, mô tả và URL website.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Tên website</span>
              <input
                v-model="form.site.name"
                class="input"
                placeholder="Sonnet"
              />
            </label>
            <label class="label">
              <span>Mô tả</span>
              <RichTextEditor
                v-model="form.site.description"
                placeholder="Mô tả ngắn về website..."
                :minHeight="160"
              />
            </label>
            <label class="label">
              <span>Website URL</span>
              <input
                v-model="form.site.url"
                class="input"
                placeholder="https://sonnet.vn"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Logo & Favicon</h2>
          <p class="muted" style="margin-top: -6px">Hình ảnh thương hiệu.</p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Logo URL</span>
              <div class="row" style="gap: 10px">
                <input
                  :value="form.site.logo_url"
                  class="input"
                  placeholder="/logo.svg"
                  readonly
                  @focus="selectAll"
                />
                <button
                  class="btn btn-secondary"
                  type="button"
                  @click="openPicker('logo')"
                >
                  Chọn
                </button>
                <button
                  class="btn btn-secondary"
                  type="button"
                  :disabled="!form.site.logo_url"
                  @click="form.site.logo_url = ''"
                >
                  Xóa
                </button>
              </div>
              <div
                v-if="form.site.logo_url"
                class="muted"
                style="margin-top: 6px"
              >
                <img
                  :src="form.site.logo_url"
                  alt="Logo preview"
                  style="height: 32px; object-fit: contain"
                />
              </div>
            </label>
            <label class="label">
              <span>Favicon URL</span>
              <div class="row" style="gap: 10px">
                <input
                  :value="form.site.favicon_url"
                  class="input"
                  placeholder="/favicon.svg"
                  readonly
                  @focus="selectAll"
                />
                <button
                  class="btn btn-secondary"
                  type="button"
                  @click="openPicker('favicon')"
                >
                  Chọn
                </button>
                <button
                  class="btn btn-secondary"
                  type="button"
                  :disabled="!form.site.favicon_url"
                  @click="form.site.favicon_url = ''"
                >
                  Xóa
                </button>
              </div>
              <div
                v-if="form.site.favicon_url"
                class="muted"
                style="margin-top: 6px"
              >
                <img
                  :src="form.site.favicon_url"
                  alt="Favicon preview"
                  style="height: 24px; object-fit: contain"
                />
              </div>
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Liên hệ</h2>
          <p class="muted" style="margin-top: -6px">
            Thông tin liên hệ hiển thị ở footer.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Email</span>
              <input
                v-model="form.site.contact.email"
                class="input"
                placeholder="support@sonnet.vn"
              />
            </label>
            <label class="label">
              <span>Số điện thoại</span>
              <input
                v-model="form.site.contact.phone"
                class="input"
                placeholder="+84 123 456 789"
              />
            </label>
            <label class="label">
              <span>Địa chỉ</span>
              <input
                v-model="form.site.contact.address"
                class="input"
                placeholder="123 Nguyễn Huệ, Q.1, TP.HCM"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Mạng xã hội</h2>
          <p class="muted" style="margin-top: -6px">
            Link social media hiển thị ở footer.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Facebook</span>
              <input
                v-model="form.site.social.facebook"
                class="input"
                placeholder="https://facebook.com/..."
              />
            </label>
            <label class="label">
              <span>YouTube</span>
              <input
                v-model="form.site.social.youtube"
                class="input"
                placeholder="https://youtube.com/@..."
              />
            </label>
            <label class="label">
              <span>Instagram</span>
              <input
                v-model="form.site.social.instagram"
                class="input"
                placeholder="https://instagram.com/..."
              />
            </label>
            <label class="label">
              <span>TikTok</span>
              <input
                v-model="form.site.social.tiktok"
                class="input"
                placeholder="https://tiktok.com/@..."
              />
            </label>
            <label class="label">
              <span>LinkedIn</span>
              <input
                v-model="form.site.social.linkedin"
                class="input"
                placeholder="https://linkedin.com/..."
              />
            </label>
            <label class="label">
              <span>Twitter</span>
              <input
                v-model="form.site.social.twitter"
                class="input"
                placeholder="https://twitter.com/..."
              />
            </label>
          </div>
        </div>
      </div>

      <!-- Tab: Trang chủ -->
      <div
        v-if="activeTab === 'home'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Hero Section</h2>
          <p class="muted" style="margin-top: -6px">
            Phần đầu tiên của trang chủ.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Badge</span>
              <input v-model="form.home.hero.badge" class="input" />
            </label>
            <label class="label">
              <span>Title Prefix</span>
              <input v-model="form.home.hero.title_prefix" class="input" />
            </label>
            <label class="label">
              <span>Title Highlight (màu gradient)</span>
              <input v-model="form.home.hero.title_highlight" class="input" />
            </label>
            <label class="label">
              <span>Title Suffix</span>
              <input v-model="form.home.hero.title_suffix" class="input" />
            </label>
            <label class="label">
              <span>Subtitle</span>
              <RichTextEditor
                v-model="form.home.hero.subtitle"
                placeholder="Mô tả nổi bật cho hero..."
                :minHeight="160"
              />
            </label>
            <label class="label">
              <span>Nút chính (Primary CTA)</span>
              <input v-model="form.home.hero.primary_cta" class="input" />
            </label>
            <label class="label">
              <span>Nút phụ (Secondary CTA)</span>
              <input v-model="form.home.hero.secondary_cta" class="input" />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Thống kê</h2>
          <p class="muted" style="margin-top: -6px">
            Các con số hiển thị ở trang chủ.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Số khóa học</span>
              <input
                v-model.number="form.home.stats.courses"
                type="number"
                min="0"
                class="input"
              />
            </label>
            <label class="label">
              <span>Số học viên</span>
              <input
                v-model.number="form.home.stats.students"
                type="number"
                min="0"
                class="input"
              />
            </label>
            <label class="label">
              <span>Số chứng chỉ</span>
              <input
                v-model.number="form.home.stats.certificates"
                type="number"
                min="0"
                class="input"
              />
            </label>
            <label class="label">
              <span>Số quốc gia</span>
              <input
                v-model.number="form.home.stats.countries"
                type="number"
                min="0"
                class="input"
              />
            </label>
            <label class="label">
              <span>Rating</span>
              <input
                v-model.number="form.home.stats.rating"
                type="number"
                min="0"
                max="5"
                step="0.1"
                class="input"
              />
            </label>
            <h3
              style="
                margin-top: 16px;
                font-weight: 600;
                font-size: 14px;
                color: #666;
              "
            >
              Nhãn hiển thị
            </h3>
            <label class="label">
              <span>Nhãn Học viên</span>
              <input
                v-model="form.home.stats.label_students"
                class="input"
                placeholder="Học viên"
              />
            </label>
            <label class="label">
              <span>Nhãn Khóa học</span>
              <input
                v-model="form.home.stats.label_courses"
                class="input"
                placeholder="Khóa học"
              />
            </label>
            <label class="label">
              <span>Nhãn Ebooks</span>
              <input
                v-model="form.home.stats.label_certificates"
                class="input"
                placeholder="Ebooks"
              />
            </label>
            <label class="label">
              <span>Nhãn Sách xuất bản</span>
              <input
                v-model="form.home.stats.label_countries"
                class="input"
                placeholder="Sách xuất bản"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">CTA Section</h2>
          <p class="muted" style="margin-top: -6px">
            Phần kêu gọi hành động cuối trang.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Title Prefix</span>
              <input v-model="form.home.cta.title_prefix" class="input" />
            </label>
            <label class="label">
              <span>Title Highlight</span>
              <input v-model="form.home.cta.title_highlight" class="input" />
            </label>
            <label class="label">
              <span>Title Suffix</span>
              <input v-model="form.home.cta.title_suffix" class="input" />
            </label>
            <label class="label">
              <span>Subtitle</span>
              <RichTextEditor
                v-model="form.home.cta.subtitle"
                placeholder="Nội dung CTA..."
                :minHeight="160"
              />
            </label>
            <label class="label">
              <span>Primary CTA</span>
              <input v-model="form.home.cta.primary_cta" class="input" />
            </label>
            <label class="label">
              <span>Secondary CTA</span>
              <input v-model="form.home.cta.secondary_cta" class="input" />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Khóa học nổi bật</h2>
          <p class="muted" style="margin-top: -6px">
            Tiêu đề phần khóa học nổi bật trên trang chủ.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Tiêu đề</span>
              <input
                v-model="form.home.featured.title"
                class="input"
                placeholder="Khóa học, Sách & Ebooks nổi bật"
              />
            </label>
            <label class="label">
              <span>Mô tả</span>
              <input
                v-model="form.home.featured.subtitle"
                class="input"
                placeholder="Được thiết kế bởi các chuyên gia hàng đầu..."
              />
            </label>
            <label class="label">
              <span>Text nút CTA</span>
              <input
                v-model="form.home.featured.button_text"
                class="input"
                placeholder="TÌM HIỂU NGAY"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Webinar</h2>
          <p class="muted" style="margin-top: -6px">
            Nội dung phần Webinar trên trang chủ.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Badge</span>
              <input
                v-model="form.home.webinar.badge"
                class="input"
                placeholder="Zoom Webinar"
              />
            </label>
            <label class="label">
              <span>Tiêu đề</span>
              <input
                v-model="form.home.webinar.title"
                class="input"
                placeholder="Zoom Webinar miễn phí & trả phí"
              />
            </label>
            <label class="label">
              <span>Mô tả</span>
              <input
                v-model="form.home.webinar.subtitle"
                class="input"
                placeholder="Tham gia học trực tiếp với chuyên gia"
              />
            </label>
            <label class="label">
              <span>Tab "Sắp tới"</span>
              <input
                v-model="form.home.webinar.tab_upcoming"
                class="input"
                placeholder="Sắp tới"
              />
            </label>
            <label class="label">
              <span>Tab "Đã hoàn thành"</span>
              <input
                v-model="form.home.webinar.tab_completed"
                class="input"
                placeholder="Đã hoàn thành"
              />
            </label>
            <label class="label">
              <span>Nút xem chi tiết</span>
              <input
                v-model="form.home.webinar.button_detail"
                class="input"
                placeholder="Xem chi tiết"
              />
            </label>
            <label class="label">
              <span>Nút xem tất cả</span>
              <input
                v-model="form.home.webinar.button_view_all"
                class="input"
                placeholder="Xem tất cả webinar"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Affiliate Banner</h2>
          <p class="muted" style="margin-top: -6px">
            Nội dung banner Affiliate trên trang chủ.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Tiêu đề</span>
              <input
                v-model="form.home.affiliate.title"
                class="input"
                placeholder="Chương trình Affiliate - Xây dựng nguồn thu nhập thứ 2!"
              />
            </label>
            <label class="label">
              <span>Mô tả</span>
              <input
                v-model="form.home.affiliate.description"
                class="input"
                placeholder="Nhận hoa hồng lên đến 85% khi giới thiệu khách hàng mua khóa học"
              />
            </label>
            <label class="label">
              <span>Text nút</span>
              <input
                v-model="form.home.affiliate.button_text"
                class="input"
                placeholder="Tìm hiểu thêm"
              />
            </label>
          </div>
        </div>
      </div>

      <!-- Tab: Footer -->
      <div
        v-if="activeTab === 'footer'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Nội dung Footer</h2>
          <p class="muted" style="margin-top: -6px">
            Mô tả và thông tin hiển thị ở footer.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Mô tả footer</span>
              <textarea
                v-model="form.footer.description"
                class="textarea"
                rows="3"
                placeholder="Cung cấp các khóa học chất lượng cao..."
              />
            </label>
            <label class="label">
              <span>Copyright text</span>
              <input
                v-model="form.footer.copyright_text"
                class="input"
                placeholder="u{00A9} 2024 Sonnet. Tất cả quyền được bảo lưu."
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Hiển thị ở cuối footer
              </div>
            </label>
            <label class="label">
              <span>Tagline</span>
              <input
                v-model="form.footer.tagline"
                class="input"
                placeholder="Made with in Vietnam"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Hiển thị bên phải copyright
              </div>
            </label>
            <label class="label row" style="align-items: center; gap: 8px">
              <input v-model="form.footer.show_social_links" type="checkbox" />
              <span>Hiển thị icon mạng xã hội ở footer</span>
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Link khóa học</h2>
          <p class="muted" style="margin-top: -6px">
            Danh sách link khóa học trong footer.
          </p>
          <div class="form" style="margin-top: 12px">
            <div
              v-for="(link, idx) in footerCoursesLinks"
              :key="idx"
              class="array-item"
            >
              <div class="array-item-header">
                <span class="array-item-number">#{{ idx + 1 }}</span>
                <button
                  type="button"
                  class="btn btn-danger btn-sm"
                  @click="removeFooterCourseLink(idx)"
                >
                  Xóa
                </button>
              </div>
              <div class="array-item-body">
                <label class="label">
                  <span>Tên link</span>
                  <input
                    v-model="link.name"
                    class="input"
                    placeholder="Lập trình Web"
                  />
                </label>
                <label class="label">
                  <span>URL</span>
                  <input
                    v-model="link.href"
                    class="input"
                    placeholder="/categories/1"
                  />
                </label>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              style="width: 100%"
              @click="addFooterCourseLink"
            >
              + Thêm link
            </button>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Link hỗ trợ</h2>
          <p class="muted" style="margin-top: -6px">
            Danh sách link hỗ trợ trong footer.
          </p>
          <div class="form" style="margin-top: 12px">
            <div
              v-for="(link, idx) in footerSupportLinks"
              :key="idx"
              class="array-item"
            >
              <div class="array-item-header">
                <span class="array-item-number">#{{ idx + 1 }}</span>
                <button
                  type="button"
                  class="btn btn-danger btn-sm"
                  @click="removeFooterSupportLink(idx)"
                >
                  Xóa
                </button>
              </div>
              <div class="array-item-body">
                <label class="label">
                  <span>Tên link</span>
                  <input
                    v-model="link.name"
                    class="input"
                    placeholder="Trung tâm hỗ trợ"
                  />
                </label>
                <label class="label">
                  <span>URL</span>
                  <input
                    v-model="link.href"
                    class="input"
                    placeholder="/support"
                  />
                </label>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              style="width: 100%"
              @click="addFooterSupportLink"
            >
              + Thêm link
            </button>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Link pháp lý</h2>
          <p class="muted" style="margin-top: -6px">
            Danh sách link pháp lý trong footer.
          </p>
          <div class="form" style="margin-top: 12px">
            <div
              v-for="(link, idx) in footerLegalLinks"
              :key="idx"
              class="array-item"
            >
              <div class="array-item-header">
                <span class="array-item-number">#{{ idx + 1 }}</span>
                <button
                  type="button"
                  class="btn btn-danger btn-sm"
                  @click="removeFooterLegalLink(idx)"
                >
                  Xóa
                </button>
              </div>
              <div class="array-item-body">
                <label class="label">
                  <span>Tên link</span>
                  <input
                    v-model="link.name"
                    class="input"
                    placeholder="Điều khoản sử dụng"
                  />
                </label>
                <label class="label">
                  <span>URL</span>
                  <input
                    v-model="link.href"
                    class="input"
                    placeholder="/terms"
                  />
                </label>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              style="width: 100%"
              @click="addFooterLegalLink"
            >
              + Thêm link
            </button>
          </div>
        </div>
      </div>

      <!-- Tab: Giới thiệu -->
      <div
        v-if="activeTab === 'about'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Thông tin người sáng lập</h2>
          <p class="muted" style="margin-top: -6px">
            Thông tin hiển thị ở phần hero.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Họ tên</span>
              <input
                v-model="form.about.hero.name"
                class="input"
                placeholder="Phan Anh Chiến"
              />
            </label>
            <label class="label">
              <span>Chức danh</span>
              <input
                v-model="form.about.hero.title"
                class="input"
                placeholder="TikTok Marketing Expert..."
              />
            </label>
            <label class="label">
              <span>Mô tả ngắn</span>
              <input
                v-model="form.about.hero.subtitle"
                class="input"
                placeholder="Đào tạo hơn 10,000+ học viên..."
              />
            </label>
            <label class="label">
              <span>Avatar URL</span>
              <div class="row" style="gap: 10px">
                <input
                  :value="form.about.hero.avatar_url"
                  class="input"
                  placeholder="/images/avatar.jpg"
                  readonly
                  @focus="selectAll"
                />
                <button
                  class="btn btn-secondary"
                  type="button"
                  @click="openPicker('about_avatar')"
                >
                  Chọn
                </button>
                <button
                  class="btn btn-secondary"
                  type="button"
                  :disabled="!form.about.hero.avatar_url"
                  @click="form.about.hero.avatar_url = ''"
                >
                  Xóa
                </button>
              </div>
              <div
                v-if="form.about.hero.avatar_url"
                class="muted"
                style="margin-top: 6px"
              >
                <img
                  :src="form.about.hero.avatar_url"
                  alt="Avatar preview"
                  style="
                    height: 64px;
                    width: 64px;
                    object-fit: cover;
                    border-radius: 50%;
                  "
                />
              </div>
            </label>
            <label class="label">
              <span>Cover URL</span>
              <div class="row" style="gap: 10px">
                <input
                  :value="form.about.hero.cover_url"
                  class="input"
                  placeholder="/images/cover.jpg"
                  readonly
                  @focus="selectAll"
                />
                <button
                  class="btn btn-secondary"
                  type="button"
                  @click="openPicker('about_cover')"
                >
                  Chọn
                </button>
                <button
                  class="btn btn-secondary"
                  type="button"
                  :disabled="!form.about.hero.cover_url"
                  @click="form.about.hero.cover_url = ''"
                >
                  Xóa
                </button>
              </div>
              <div
                v-if="form.about.hero.cover_url"
                class="muted"
                style="margin-top: 6px"
              >
                <img
                  :src="form.about.hero.cover_url"
                  alt="Cover preview"
                  style="height: 64px; object-fit: cover; border-radius: 8px"
                />
              </div>
            </label>
            <label class="label row" style="align-items: center; gap: 8px">
              <input v-model="form.about.hero.verified" type="checkbox" />
              <span>Hiển thị huy hiệu Verified </span>
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Thống kê cá nhân</h2>
          <p class="muted" style="margin-top: -6px">
            Các con số hiển thị ở trang giới thiệu.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Số Followers</span>
              <input
                v-model="form.about.stats.followers"
                class="input"
                placeholder="500K+"
              />
            </label>
            <label class="label">
              <span>Số học viên</span>
              <input
                v-model="form.about.stats.students"
                class="input"
                placeholder="10,000+"
              />
            </label>
            <label class="label">
              <span>Số khóa học</span>
              <input
                v-model="form.about.stats.courses"
                class="input"
                placeholder="15+"
              />
            </label>
            <label class="label">
              <span>Kinh nghiệm</span>
              <input
                v-model="form.about.stats.experience"
                class="input"
                placeholder="5+ năm"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Mạng xã hội cá nhân</h2>
          <p class="muted" style="margin-top: -6px">
            Link đến các kênh mạng xã hội.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>TikTok</span>
              <input
                v-model="form.about.social.tiktok"
                class="input"
                placeholder="https://tiktok.com/@..."
              />
            </label>
            <label class="label">
              <span>YouTube</span>
              <input
                v-model="form.about.social.youtube"
                class="input"
                placeholder="https://youtube.com/@..."
              />
            </label>
            <label class="label">
              <span>Facebook</span>
              <input
                v-model="form.about.social.facebook"
                class="input"
                placeholder="https://facebook.com/..."
              />
            </label>
            <label class="label">
              <span>Instagram</span>
              <input
                v-model="form.about.social.instagram"
                class="input"
                placeholder="https://instagram.com/..."
              />
            </label>
          </div>
        </div>

        <div class="card" style="grid-column: 1 / -1">
          <h2 class="h2" style="margin-top: 0">Nội dung giới thiệu</h2>
          <p class="muted" style="margin-top: -6px">
            Thông tin chi tiết về bản thân.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Headline</span>
              <input
                v-model="form.about.about.headline"
                class="input"
                placeholder="Từ 0 follower đến Top Creator..."
              />
            </label>
            <label class="label">
              <span>Tiểu sử (Bio)</span>
              <RichTextEditor
                v-model="form.about.about.bio"
                placeholder="Giới thiệu về bản thân..."
                :minHeight="200"
              />
            </label>
            <label class="label">
              <span>Sứ mệnh</span>
              <RichTextEditor
                v-model="form.about.about.mission"
                placeholder="Sứ mệnh của bạn..."
                :minHeight="120"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Thành tựu</h2>
          <p class="muted" style="margin-top: -6px">
            Các thành tựu nổi bật của bạn.
          </p>
          <div class="form" style="margin-top: 12px">
            <div
              v-for="(achievement, idx) in aboutAchievements"
              :key="idx"
              class="array-item"
            >
              <div class="array-item-header">
                <span class="array-item-number">#{{ idx + 1 }}</span>
                <button
                  type="button"
                  class="btn btn-danger btn-sm"
                  @click="removeAchievement(idx)"
                >
                  Xóa
                </button>
              </div>
              <div class="array-item-body">
                <label class="label">
                  <span>Icon</span>
                  <select v-model="achievement.icon" class="input">
                    <option value="trophy">Trophy</option>
                    <option value="users">Users</option>
                    <option value="trending">Trending</option>
                    <option value="award">Award</option>
                    <option value="star">Star</option>
                  </select>
                </label>
                <label class="label">
                  <span>Tiêu đề</span>
                  <input
                    v-model="achievement.title"
                    class="input"
                    placeholder="Top 100 TikTok Creator"
                  />
                </label>
                <label class="label">
                  <span>Mô tả</span>
                  <input
                    v-model="achievement.description"
                    class="input"
                    placeholder="Được TikTok công nhận năm 2023"
                  />
                </label>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              style="width: 100%"
              @click="addAchievement"
            >
              + Thêm thành tựu
            </button>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Kỹ năng</h2>
          <p class="muted" style="margin-top: -6px">
            Các kỹ năng chuyên môn của bạn.
          </p>
          <div class="form" style="margin-top: 12px">
            <div
              v-for="(skill, idx) in aboutSkills"
              :key="idx"
              class="array-item"
            >
              <div class="array-item-header">
                <span class="array-item-number">#{{ idx + 1 }}</span>
                <button
                  type="button"
                  class="btn btn-danger btn-sm"
                  @click="removeSkill(idx)"
                >
                  Xóa
                </button>
              </div>
              <div class="array-item-body">
                <label class="label">
                  <span>Tên kỹ năng</span>
                  <input
                    v-model="skill.name"
                    class="input"
                    placeholder="TikTok Marketing"
                  />
                </label>
                <label class="label">
                  <span>Mức độ ({{ skill.level }}%)</span>
                  <input
                    v-model.number="skill.level"
                    type="range"
                    min="0"
                    max="100"
                    class="range-input"
                  />
                </label>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              style="width: 100%"
              @click="addSkill"
            >
              + Thêm kỹ năng
            </button>
          </div>
        </div>

        <div class="card" style="grid-column: 1 / -1">
          <h2 class="h2" style="margin-top: 0">Testimonials</h2>
          <p class="muted" style="margin-top: -6px">Đánh giá từ học viên.</p>
          <div class="form" style="margin-top: 12px">
            <div class="testimonials-grid">
              <div
                v-for="(testimonial, idx) in aboutTestimonials"
                :key="idx"
                class="array-item testimonial-item"
              >
                <div class="array-item-header">
                  <span class="array-item-number">Học viên #{{ idx + 1 }}</span>
                  <button
                    type="button"
                    class="btn btn-danger btn-sm"
                    @click="removeTestimonial(idx)"
                  >
                    Xóa
                  </button>
                </div>
                <div class="array-item-body">
                  <label class="label">
                    <span>Họ tên</span>
                    <input
                      v-model="testimonial.name"
                      class="input"
                      placeholder="Nguyễn Văn A"
                    />
                  </label>
                  <label class="label">
                    <span>Vai trò</span>
                    <input
                      v-model="testimonial.role"
                      class="input"
                      placeholder="TikToker 200K followers"
                    />
                  </label>
                  <label class="label">
                    <span>Avatar URL</span>
                    <div style="display: flex; gap: 8px">
                      <input
                        v-model="testimonial.avatar"
                        class="input"
                        placeholder="/images/avatar.jpg (để trống nếu không có)"
                      />
                      <button
                        type="button"
                        class="btn btn-secondary"
                        style="white-space: nowrap"
                        @click="openPicker('testimonial_' + idx)"
                      >
                        Chọn ảnh
                      </button>
                    </div>
                    <img
                      v-if="testimonial.avatar"
                      :src="testimonial.avatar"
                      style="
                        max-width: 48px;
                        max-height: 48px;
                        border-radius: 50%;
                        margin-top: 6px;
                      "
                    />
                  </label>
                  <label class="label">
                    <span>Nội dung đánh giá</span>
                    <textarea
                      v-model="testimonial.content"
                      class="textarea"
                      rows="3"
                      placeholder="Khóa học rất hay và bổ ích..."
                    />
                  </label>
                  <label class="label">
                    <span>Đánh giá sao ({{ testimonial.rating }}/5)</span>
                    <div class="star-rating">
                      <button
                        v-for="star in 5"
                        :key="star"
                        type="button"
                        class="star-btn"
                        :class="{ active: star <= testimonial.rating }"
                        @click="testimonial.rating = star"
                      >
                        
                      </button>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            <button
              type="button"
              class="btn btn-secondary"
              style="width: 100%; margin-top: 12px"
              @click="addTestimonial"
            >
              + Thêm đánh giá
            </button>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">CTA Section</h2>
          <p class="muted" style="margin-top: -6px">
            Phần kêu gọi hành động cuối trang.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Tiêu đề</span>
              <input
                v-model="form.about.cta.title"
                class="input"
                placeholder="Sẵn sàng bắt đầu hành trình?"
              />
            </label>
            <label class="label">
              <span>Mô tả</span>
              <input
                v-model="form.about.cta.subtitle"
                class="input"
                placeholder="Tham gia cùng 10,000+ học viên..."
              />
            </label>
            <label class="label">
              <span>Text nút</span>
              <input
                v-model="form.about.cta.button_text"
                class="input"
                placeholder="Xem các khóa học"
              />
            </label>
            <label class="label">
              <span>URL nút</span>
              <input
                v-model="form.about.cta.button_url"
                class="input"
                placeholder="/courses"
              />
            </label>
          </div>
        </div>
      </div>

      <!-- Tab: SEO -->
      <div
        v-if="activeTab === 'seo'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">SEO Mặc định</h2>
          <p class="muted" style="margin-top: -6px">
            Cấu hình SEO cho toàn website.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Title Template</span>
              <input
                v-model="form.seo.title_template"
                class="input"
                placeholder="%s | Sonnet"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                %s sẽ được thay bằng tiêu đề trang
              </div>
            </label>
            <label class="label">
              <span>Default Title</span>
              <input v-model="form.seo.default_title" class="input" />
            </label>
            <label class="label">
              <span>Default Description</span>
              <textarea
                v-model="form.seo.default_description"
                class="textarea"
                rows="3"
              />
            </label>
            <label class="label">
              <span>Keywords (phân tách bằng dấu phẩy)</span>
              <textarea v-model="form.seo.keywords" class="textarea" rows="2" />
            </label>
          </div>
        </div>
      </div>

      <!-- Tab: Thanh toán -->
      <div
        v-if="activeTab === 'payment'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">SePay Gateway</h2>
          <p class="muted" style="margin-top: -6px">
            Thông tin tài khoản nhận tiền. Xem danh sách ngân hàng tại
            <a
              href="https://qr.sepay.vn/banks.json"
              target="_blank"
              style="color: #3b82f6"
              >qr.sepay.vn/banks.json</a
            >
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Mã ngân hàng (short_name) — dùng cho QR</span>
              <input
                v-model="form.sepay_gateway.bank_code"
                class="input"
                placeholder="VPBank, MBBank, Techcombank, ACB, BIDV..."
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Nhập đúng <b>short_name</b> từ
                <a
                  href="https://qr.sepay.vn/banks.json"
                  target="_blank"
                  style="color: #3b82f6"
                  >banks.json</a
                >
                (VD: Vietcombank, MBBank, Techcombank, ACB, BIDV, VPBank,
                TPBank, Sacombank, HDBank, Agribank).
                <b style="color: #ef4444">Nếu sai, mã QR sẽ không hiển thị!</b>
              </div>
            </label>
            <label class="label">
              <span>Tên ngân hàng hiển thị</span>
              <input
                v-model="form.sepay_gateway.bank_name"
                class="input"
                placeholder="VP BANK CHI NHÁNH HÀ NỘI"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Tên đầy đủ hiển thị trên trang thanh toán (VD: VP BANK CHI NHÁNH
                HÀ NỘI)
              </div>
            </label>
            <label class="label">
              <span>Số tài khoản</span>
              <input
                v-model="form.sepay_gateway.account_number"
                class="input"
              />
            </label>
            <label class="label">
              <span>Tên chủ tài khoản</span>
              <input v-model="form.sepay_gateway.account_name" class="input" />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">SePay Webhook</h2>
          <p class="muted" style="margin-top: -6px">
            Cấu hình webhook nhận thông báo thanh toán từ
            <a
              href="https://my.sepay.vn/webhooks"
              target="_blank"
              style="color: #3b82f6"
              >my.sepay.vn</a
            >
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Webhook Token (API Key)</span>
              <input
                v-model="form.sepay.webhook_token"
                class="input"
                placeholder="Token từ SePay"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Chọn chứng thực <b>API Key</b> khi tạo webhook trên SePay. Token
                bạn đặt trên SePay phải trùng với giá trị ở đây.
              </div>
            </label>
            <label class="label">
              <span>Pattern (Mã thanh toán)</span>
              <input
                v-model="form.sepay.pattern"
                class="input"
                placeholder="SN"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Tiền tố cho mã đơn hàng (VD: SN mã thanh toán SN123). Phải
                trùng với <b>Cấu trúc mã thanh toán</b> trên SePay (Công ty 
                Cấu hình chung).
              </div>
            </label>
            <label class="label">
              <span>Webhook URL</span>
              <input
                :value="webhookUrl"
                class="input"
                readonly
                @focus="selectAll"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Copy URL này vào <b>Gọi đến URL</b> khi tạo webhook trên
                <a
                  href="https://my.sepay.vn/webhooks"
                  target="_blank"
                  style="color: #3b82f6"
                  >my.sepay.vn WebHooks</a
                >
              </div>
            </label>
          </div>

          <div
            style="
              margin-top: 16px;
              padding: 12px;
              background: #fefce8;
              border: 1px solid #fde047;
              border-radius: 8px;
              font-size: 13px;
              line-height: 1.6;
            "
          >
            <strong>Hướng dẫn cài đặt SePay:</strong>
            <ol style="margin: 8px 0 0 16px; padding: 0">
              <li>
                Đăng nhập
                <a
                  href="https://my.sepay.vn"
                  target="_blank"
                  style="color: #3b82f6"
                  >my.sepay.vn</a
                >
                liên kết tài khoản ngân hàng
              </li>
              <li>
                Vào <b>Công ty → Cấu hình chung → Cấu trúc mã thanh toán</b>:
                đặt tiền tố trùng với Pattern ở trên
              </li>
              <li>
                Vào <b>WebHooks → Thêm webhooks</b>:
                <ul style="margin: 4px 0 4px 16px">
                  <li>Sự kiện: <b>Tiền vào</b></li>
                  <li>Gọi đến URL: copy Webhook URL ở trên</li>
                  <li>Chứng thực: <b>API Key</b> → nhập token trùng ở trên</li>
                  <li>Content-Type: <b>application/json</b></li>
                </ul>
              </li>
              <li>
                Test: vào <b>Giao dịch → Giả lập giao dịch</b> để kiểm tra
              </li>
            </ol>
          </div>
        </div>
      </div>

      <!-- Tab: Hóa đơn điện tử (Minvoice) -->
      <div
        v-if="activeTab === 'invoice'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(360px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Kết nối Minvoice</h2>
          <p class="muted" style="margin-top: -6px">
            Tự động phát hành hóa đơn điện tử sau khi SePay xác nhận thanh toán.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label row" style="align-items: center; gap: 10px">
              <input
                type="checkbox"
                v-model="form.minvoice.enabled"
                style="width: 18px; height: 18px"
              />
              <span>Bật phát hành hóa đơn tự động</span>
            </label>
            <label class="label">
              <span>Base URL</span>
              <input
                v-model="form.minvoice.base_url"
                class="input"
                placeholder="https://0106026495-999.minvoice.site"
              />
              <small class="muted"
                >Không kết thúc bằng dấu "/". Ví dụ test:
                https://0106026495-999.minvoice.site</small
              >
            </label>
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 180px">
                <span>Username</span>
                <input
                  v-model="form.minvoice.username"
                  class="input"
                  autocomplete="off"
                  placeholder="Tài khoản Minvoice"
                />
              </label>
              <label class="label" style="flex: 1; min-width: 180px">
                <span>Password</span>
                <input
                  v-model="form.minvoice.password"
                  class="input"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Mật khẩu Minvoice"
                />
              </label>
            </div>
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 180px">
                <span>Mã số thuế (TaxCode)</span>
                <input
                  v-model="form.minvoice.tax_code"
                  class="input"
                  placeholder="0106026495"
                />
              </label>
              <label class="label" style="flex: 1; min-width: 120px">
                <span>Mã chi nhánh</span>
                <input
                  v-model="form.minvoice.branch_code"
                  class="input"
                  placeholder="VP"
                />
              </label>
            </div>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Mẫu & ký hiệu hóa đơn</h2>
          <p class="muted" style="margin-top: -6px">
            Đồng bộ với ký hiệu đã đăng ký trên Minvoice.
          </p>
          <div class="form" style="margin-top: 12px">
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 140px">
                <span>Loại hóa đơn</span>
                <input
                  v-model.number="form.minvoice.invoice_type"
                  class="input"
                  type="number"
                  min="1"
                  placeholder="1"
                />
                <small class="muted">1 = Hóa đơn GTGT</small>
              </label>
              <label class="label" style="flex: 1; min-width: 180px">
                <span>Ký hiệu mặc định</span>
                <input
                  v-model="form.minvoice.default_invoice_series"
                  class="input"
                  placeholder="C25MAA"
                />
              </label>
            </div>
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 120px">
                <span>Tiền tệ</span>
                <input
                  v-model="form.minvoice.currency_code"
                  class="input"
                  placeholder="VND"
                />
              </label>
              <label class="label" style="flex: 1; min-width: 120px">
                <span>Tỷ giá</span>
                <input
                  v-model.number="form.minvoice.exchange_rate"
                  class="input"
                  type="number"
                  step="0.0001"
                  placeholder="1"
                />
              </label>
            </div>
            <label class="label">
              <span>Hình thức thanh toán hiển thị</span>
              <input
                v-model="form.minvoice.payment_method_name"
                class="input"
                placeholder="Chuyển khoản"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Thuế & hàng hóa mặc định</h2>
          <p class="muted" style="margin-top: -6px">
            Áp dụng cho các mặt hàng không khai báo thuế riêng.
          </p>
          <div class="form" style="margin-top: 12px">
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 140px">
                <span>Mã thuế suất</span>
                <input
                  v-model="form.minvoice.vat_code"
                  class="input"
                  placeholder="0"
                />
                <small class="muted"
                  >Ví dụ: 0 (không chịu thuế), 1 (0%), 2 (5%), 3 (8%), 4
                  (10%)</small
                >
              </label>
              <label class="label" style="flex: 1; min-width: 120px">
                <span>VAT (%)</span>
                <input
                  v-model.number="form.minvoice.vat_rate"
                  class="input"
                  type="number"
                  step="0.01"
                  placeholder="0"
                />
              </label>
            </div>
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 120px">
                <span>Đơn vị tính</span>
                <input
                  v-model="form.minvoice.unit_code"
                  class="input"
                  placeholder="Goi"
                />
              </label>
              <label class="label" style="flex: 1; min-width: 120px">
                <span>Prefix mã hàng</span>
                <input
                  v-model="form.minvoice.item_code_prefix"
                  class="input"
                  placeholder="SONET"
                />
              </label>
            </div>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Tùy chọn nâng cao</h2>
          <p class="muted" style="margin-top: -6px">
            Thông số vận hành, chỉ chỉnh khi cần.
          </p>
          <div class="form" style="margin-top: 12px">
            <div class="row" style="gap: 10px; flex-wrap: wrap">
              <label class="label" style="flex: 1; min-width: 160px">
                <span>Timeout request (giây)</span>
                <input
                  v-model.number="form.minvoice.request_timeout"
                  class="input"
                  type="number"
                  min="5"
                  max="120"
                  placeholder="30"
                />
              </label>
              <label class="label" style="flex: 1; min-width: 160px">
                <span>Cache token (phút)</span>
                <input
                  v-model.number="form.minvoice.token_cache_minutes"
                  class="input"
                  type="number"
                  min="1"
                  max="720"
                  placeholder="50"
                />
              </label>
            </div>
            <div
              style="
                margin-top: 8px;
                padding: 12px;
                background: #f1f5f9;
                border: 1px solid #cbd5f5;
                border-radius: 8px;
                font-size: 13px;
                line-height: 1.6;
              "
            >
              <strong>Hướng dẫn nhanh:</strong>
              <ol style="margin: 8px 0 0 16px; padding: 0">
                <li>
                  Đăng nhập bảng điều khiển Minvoice, tạo tài khoản tích hợp và
                  lấy <b>Username / Password</b>.
                </li>
                <li>
                  Mở mục <b>Phát hành hóa đơn &rarr; Ký hiệu</b>, copy ký hiệu
                  đang sử dụng vào ô "Ký hiệu mặc định".
                </li>
                <li>
                  Nhấn <b>Lưu thay đổi</b>. Hệ thống sẽ dùng cấu hình này cho
                  mọi đơn hàng có yêu cầu xuất hóa đơn.
                </li>
                <li>
                  Có thể tạm tắt bằng ô <b>"Bật phát hành hóa đơn tự động"</b>
                  mà không cần xóa thông tin.
                </li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: CDN -->
      <div
        v-if="activeTab === 'cdn'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Bunny Storage</h2>
          <p class="muted" style="margin-top: -6px">
            Để trống sẽ dùng giá trị từ .env
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>API Key</span>
              <input
                v-model="form.bunnycdn.api_key"
                class="input"
                type="password"
                placeholder="********"
              />
            </label>
            <label class="label">
              <span>Storage Zone Name</span>
              <input v-model="form.bunnycdn.storage_zone_name" class="input" />
            </label>
            <label class="label">
              <span>Pull Zone URL</span>
              <input
                v-model="form.bunnycdn.pull_zone_url"
                class="input"
                placeholder="https://your-zone.b-cdn.net"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Bunny Stream</h2>
          <p class="muted" style="margin-top: -6px">
            Cấu hình video streaming.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Video Library ID</span>
              <input v-model="form.bunnycdn.video_library_id" class="input" />
            </label>
            <label class="label">
              <span>Video API Key</span>
              <input
                v-model="form.bunnycdn.video_api_key"
                class="input"
                type="password"
                placeholder="********"
              />
            </label>
            <label class="label">
              <span>Stream Hostname</span>
              <input
                v-model="form.bunnycdn.stream_hostname"
                class="input"
                placeholder="vz-xxxxx.b-cdn.net"
              />
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Token Auth</h2>
          <p class="muted" style="margin-top: -6px">Bảo vệ video với token.</p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Token Auth Key</span>
              <input
                v-model="form.bunnycdn.token_auth_key"
                class="input"
                type="password"
                placeholder="********"
              />
            </label>
            <label class="label row" style="align-items: center; gap: 8px">
              <input
                v-model="form.bunnycdn.enable_token_auth"
                type="checkbox"
              />
              <span>Bật Token Authentication</span>
            </label>
            <label class="label">
              <span>Token TTL (giây)</span>
              <input
                v-model.number="form.bunnycdn.token_ttl"
                type="number"
                min="60"
                max="86400"
                class="input"
              />
              <div class="muted" style="font-size: 12px; margin-top: 4px">
                Thời gian token hợp lệ (60 - 86400 giây)
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Tab: Email / SMTP -->
      <div
        v-if="activeTab === 'email'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Cấu hình SMTP</h2>
          <p class="muted" style="margin-top: -6px">
            Cài đặt máy chủ gửi email cho toàn hệ thống.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Mailer</span>
              <select v-model="form.mail.mailer" class="input">
                <option value="smtp">SMTP</option>
                <option value="sendmail">Sendmail</option>
                <option value="ses">Amazon SES</option>
                <option value="mailgun">Mailgun</option>
                <option value="postmark">Postmark</option>
                <option value="log">Log (chỉ test)</option>
              </select>
            </label>
            <label class="label">
              <span>SMTP Host</span>
              <input
                v-model="form.mail.host"
                class="input"
                placeholder="smtp.gmail.com"
              />
            </label>
            <label class="label">
              <span>SMTP Port</span>
              <input
                v-model.number="form.mail.port"
                class="input"
                type="number"
                placeholder="587"
              />
            </label>
            <label class="label">
              <span>Username</span>
              <input
                v-model="form.mail.username"
                class="input"
                placeholder="your-email@gmail.com"
              />
            </label>
            <label class="label">
              <span>Password</span>
              <input
                v-model="form.mail.password"
                class="input"
                type="password"
                placeholder="App Password"
              />
            </label>
            <label class="label">
              <span>Encryption</span>
              <select v-model="form.mail.encryption" class="input">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="null">Không mã hóa</option>
              </select>
            </label>
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Người gửi mặc định</h2>
          <p class="muted" style="margin-top: -6px">
            Thông tin hiển thị khi gửi email.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Email người gửi</span>
              <input
                v-model="form.mail.from_address"
                class="input"
                placeholder="no-reply@yourdomain.com"
              />
            </label>
            <label class="label">
              <span>Tên người gửi</span>
              <input
                v-model="form.mail.from_name"
                class="input"
                placeholder="Sonnet Academy"
              />
            </label>
          </div>

          <h2 class="h2">Gửi email thử nghiệm</h2>
          <p class="muted" style="margin-top: -6px">
            Kiểm tra cấu hình SMTP bằng cách gửi email test.
          </p>
          <div class="form" style="margin-top: 12px">
            <label class="label">
              <span>Email nhận thử</span>
              <input
                v-model="testEmailTo"
                class="input"
                type="email"
                placeholder="test@example.com"
              />
            </label>
            <button
              class="btn"
              :disabled="sendingTest || !testEmailTo"
              style="margin-top: 8px"
              @click="sendTestEmail"
            >
              {{ sendingTest ? "Đang gửi…" : "Gửi email test" }}
            </button>
            <div
              v-if="testEmailResult"
              :class="testEmailSuccess ? 'alert-success' : 'alert'"
              style="margin-top: 8px; font-size: 13px"
            >
              {{ testEmailResult }}
            </div>
          </div>
        </div>
      </div>

      <!-- Tab: Custom Code -->
      <div
        v-if="activeTab === 'code'"
        class="grid"
        style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr))"
      >
        <div class="card">
          <h2 class="h2" style="margin-top: 0">Head Scripts</h2>
          <p class="muted" style="margin-top: -6px">
            Scripts/meta thêm vào &lt;head&gt;
          </p>
          <div class="form" style="margin-top: 12px">
            <textarea
              v-model="form.custom_code.head_scripts"
              class="textarea code-textarea"
              rows="8"
              placeholder="<!-- Google Analytics -->
<script async src='...'></script>
<meta name='...' />"
            />
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Body Start Scripts</h2>
          <p class="muted" style="margin-top: -6px">
            Scripts thêm ngay sau &lt;body&gt;
          </p>
          <div class="form" style="margin-top: 12px">
            <textarea
              v-model="form.custom_code.body_start_scripts"
              class="textarea code-textarea"
              rows="8"
              placeholder="<!-- Google Tag Manager (noscript) -->
<noscript>...</noscript>"
            />
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Body End Scripts</h2>
          <p class="muted" style="margin-top: -6px">
            Scripts thêm trước &lt;/body&gt;
          </p>
          <div class="form" style="margin-top: 12px">
            <textarea
              v-model="form.custom_code.body_end_scripts"
              class="textarea code-textarea"
              rows="8"
              placeholder="<!-- Chat widget, FB Pixel, etc -->
<script>...</script>"
            />
          </div>
        </div>

        <div class="card">
          <h2 class="h2" style="margin-top: 0">Custom CSS</h2>
          <p class="muted" style="margin-top: -6px">
            CSS tùy chỉnh (không cần thẻ &lt;style&gt;)
          </p>
          <div class="form" style="margin-top: 12px">
            <textarea
              v-model="form.custom_code.custom_css"
              class="textarea code-textarea"
              rows="8"
              placeholder="/* CSS tùy chỉnh */
.my-class {
  color: red;
}"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Save Bar -->
    <div class="card save-bar" style="margin-top: 16px">
      <div
        class="row"
        style="justify-content: space-between; align-items: center"
      >
        <div>
          <div style="font-weight: 600">Lưu ý</div>
          <div class="muted" style="font-size: 12px">
            Thay đổi sẽ có hiệu lực ngay lập tức cho giao diện và API.
          </div>
        </div>
        <button class="btn" :disabled="saving" @click="save">
          {{ saving ? "Đang lưu…" : "Lưu thay đổi" }}
        </button>
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
import { computed, onMounted, ref } from "vue";
import { apiFetch, extractMessage, HttpError } from "../lib/api";
import RichTextEditor from "../components/RichTextEditor.vue";
import MediaPickerModal from "../components/MediaPickerModal.vue";
import type { MediaAsset } from "../lib/types";
import { useToast } from "../lib/toast";

// Tabs configuration
const tabs = [
  { id: "brand", label: "Thương hiệu" },
  { id: "home", label: "Trang chủ" },
  { id: "footer", label: "Footer" },
  { id: "about", label: "Giới thiệu" },
  { id: "seo", label: "SEO" },
  { id: "payment", label: "Thanh toán" },
  { id: "invoice", label: "Hóa đơn điện tử" },
  { id: "cdn", label: "CDN" },
  { id: "email", label: "Email / SMTP" },
  { id: "code", label: "Custom Code" },
];

const activeTab = ref("brand");

type SettingsPayload = {
  site: {
    name: string;
    description: string;
    url: string;
    logo_url: string;
    favicon_url: string;
    contact: {
      email: string;
      phone: string;
      address: string;
    };
    social: {
      facebook: string;
      youtube: string;
      instagram: string;
      tiktok: string;
      linkedin: string;
      twitter: string;
    };
  };
  seo: {
    title_template: string;
    default_title: string;
    default_description: string;
    keywords: string;
  };
  home: {
    hero: {
      badge: string;
      title_prefix: string;
      title_highlight: string;
      title_suffix: string;
      subtitle: string;
      primary_cta: string;
      secondary_cta: string;
    };
    stats: {
      courses: number;
      students: number;
      certificates: number;
      countries: number;
      rating: number;
      label_students: string;
      label_courses: string;
      label_certificates: string;
      label_countries: string;
    };
    cta: {
      title_prefix: string;
      title_highlight: string;
      title_suffix: string;
      subtitle: string;
      primary_cta: string;
      secondary_cta: string;
    };
    featured: {
      title: string;
      subtitle: string;
      button_text: string;
    };
    webinar: {
      badge: string;
      title: string;
      subtitle: string;
      tab_upcoming: string;
      tab_completed: string;
      button_detail: string;
      button_view_all: string;
    };
    affiliate: {
      title: string;
      description: string;
      button_text: string;
    };
  };
  bunnycdn: {
    api_key: string;
    storage_zone_name: string;
    pull_zone_url: string;
    video_library_id: string;
    video_api_key: string;
    stream_hostname: string;
    token_auth_key: string;
    enable_token_auth: boolean;
    token_ttl: number;
  };
  sepay: {
    webhook_token: string;
    pattern: string;
    webhook_url?: string;
  };
  sepay_gateway: {
    bank_code: string;
    bank_name: string;
    account_number: string;
    account_name: string;
  };
  minvoice: {
    enabled: boolean;
    base_url: string;
    username: string;
    password: string;
    branch_code: string;
    tax_code: string;
    invoice_type: number;
    default_invoice_series: string;
    currency_code: string;
    exchange_rate: number;
    payment_method_name: string;
    vat_rate: number;
    vat_code: string;
    unit_code: string;
    item_code_prefix: string;
    request_timeout: number;
    token_cache_minutes: number;
  };
  custom_code: {
    head_scripts: string;
    body_start_scripts: string;
    body_end_scripts: string;
    custom_css: string;
  };
  mail: {
    mailer: string;
    host: string;
    port: number;
    username: string;
    password: string;
    encryption: string;
    from_address: string;
    from_name: string;
  };
  footer: {
    description: string;
    copyright_text: string;
    tagline: string;
    show_social_links: boolean;
    links: string; // JSON string for links array
  };
  about: {
    hero: {
      name: string;
      title: string;
      subtitle: string;
      avatar_url: string;
      cover_url: string;
      verified: boolean;
    };
    stats: {
      followers: string;
      students: string;
      courses: string;
      experience: string;
    };
    social: {
      tiktok: string;
      youtube: string;
      facebook: string;
      instagram: string;
    };
    about: {
      headline: string;
      bio: string;
      mission: string;
    };
    achievements: string;
    skills: string;
    testimonials: string;
    cta: {
      title: string;
      subtitle: string;
      button_text: string;
      button_url: string;
    };
  };
};

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const openMediaPicker = ref(false);
const mediaPickerTarget = ref<
  "logo" | "favicon" | "about_avatar" | "about_cover" | string | null
>(null);
const testEmailTo = ref("");
const sendingTest = ref(false);
const testEmailResult = ref("");
const testEmailSuccess = ref(false);
const { success: toastSuccess, error: toastError } = useToast();

const form = ref<SettingsPayload>({
  site: {
    name: "Sonnet",
    description: "Nền tảng học online hàng đầu Việt Nam",
    url: "https://sonnet.vn",
    logo_url: "/logo.svg",
    favicon_url: "/favicon.svg",
    contact: {
      email: "support@sonnet.vn",
      phone: "+84 123 456 789",
      address: "123 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh",
    },
    social: {
      facebook: "",
      youtube: "",
      instagram: "",
      tiktok: "",
      linkedin: "",
      twitter: "",
    },
  },
  seo: {
    title_template: "%s | Sonnet",
    default_title: "Sonnet - Học Online Chất Lượng Cao",
    default_description:
      "Khám phá hàng ngàn khóa học chất lượng cao từ các chuyên gia hàng đầu.",
    keywords: "học online, khóa học, e-learning",
  },
  home: {
    hero: {
      badge: "Nền tảng học online #1 Việt Nam",
      title_prefix: "Mở khóa",
      title_highlight: "tiềm năng",
      title_suffix: "của bạn",
      subtitle:
        "Khám phá hàng ngàn khóa học chất lượng cao từ các chuyên gia hàng đầu.",
      primary_cta: "Bắt đầu học miễn phí",
      secondary_cta: "Xem giới thiệu",
    },
    stats: {
      courses: 1000,
      students: 50000,
      certificates: 25000,
      countries: 40,
      rating: 4.9,
      label_students: "Học viên",
      label_courses: "Khóa học",
      label_certificates: "Ebooks",
      label_countries: "Sách xuất bản",
    },
    cta: {
      title_prefix: "Sẵn sàng nâng cấp",
      title_highlight: "kỹ năng",
      title_suffix: "của bạn?",
      subtitle: "Tham gia cùng hơn 50,000+ học viên.",
      primary_cta: "Đăng ký miễn phí",
      secondary_cta: "Xem khóa học",
    },
    featured: {
      title: "Khóa học, Sách & Ebooks nổi bật",
      subtitle:
        "Được thiết kế bởi các chuyên gia hàng đầu, phù hợp cho mọi trình độ",
      button_text: "TÌM HIỂU NGAY",
    },
    webinar: {
      badge: "Zoom Webinar",
      title: "Zoom Webinar miễn phí & trả phí",
      subtitle: "Tham gia học trực tiếp với chuyên gia",
      tab_upcoming: "Sắp tới",
      tab_completed: "Đã hoàn thành",
      button_detail: "Xem chi tiết",
      button_view_all: "Xem tất cả webinar",
    },
    affiliate: {
      title: "Chương trình Affiliate - Xây dựng nguồn thu nhập thứ 2!",
      description:
        "Nhận hoa hồng lên đến 85% khi giới thiệu khách hàng mua khóa học",
      button_text: "Tìm hiểu thêm",
    },
  },
  bunnycdn: {
    api_key: "",
    storage_zone_name: "",
    pull_zone_url: "",
    video_library_id: "",
    video_api_key: "",
    stream_hostname: "",
    token_auth_key: "",
    enable_token_auth: false,
    token_ttl: 3600,
  },
  sepay: {
    webhook_token: "",
    pattern: "SN",
    webhook_url: "",
  },
  sepay_gateway: {
    bank_code: "",
    bank_name: "",
    account_number: "",
    account_name: "",
  },
  minvoice: {
    enabled: false,
    base_url: "",
    username: "",
    password: "",
    branch_code: "VP",
    tax_code: "",
    invoice_type: 1,
    default_invoice_series: "",
    currency_code: "VND",
    exchange_rate: 1,
    payment_method_name: "Chuyển khoản",
    vat_rate: 0,
    vat_code: "0",
    unit_code: "Goi",
    item_code_prefix: "SONET",
    request_timeout: 30,
    token_cache_minutes: 50,
  },
  custom_code: {
    head_scripts: "",
    body_start_scripts: "",
    body_end_scripts: "",
    custom_css: "",
  },
  mail: {
    mailer: "smtp",
    host: "",
    port: 587,
    username: "",
    password: "",
    encryption: "tls",
    from_address: "",
    from_name: "",
  },
  footer: {
    description:
      "Cung cấp các khóa học chất lượng cao với công nghệ streaming video tiên tiến.",
    copyright_text: "u{00A9} 2024 Sonnet. Tất cả quyền được bảo lưu.",
    tagline: "Made with in Vietnam",
    show_social_links: true,
    links: JSON.stringify({
      courses: [
        { name: "Lập trình Web", href: "/categories/1" },
        { name: "Mobile App", href: "/categories/2" },
        { name: "UI/UX Design", href: "/categories/3" },
        { name: "Data Science", href: "/categories/4" },
      ],
      support: [
        { name: "Trung tâm hỗ trợ", href: "/support" },
        { name: "FAQ", href: "/faq" },
        { name: "Liên hệ", href: "/contact" },
        { name: "Góp ý", href: "/feedback" },
      ],
      legal: [
        { name: "Điều khoản sử dụng", href: "/terms" },
        { name: "Chính sách bảo mật", href: "/privacy" },
        { name: "Chính sách hoàn tiền", href: "/refund" },
      ],
    }),
  },
  about: {
    hero: {
      name: "Phan Anh Chiến",
      title: "TikTok Marketing Expert & Founder of Sonet",
      subtitle: "Đào tạo hơn 10,000+ học viên kiếm tiền từ TikTok",
      avatar_url: "",
      cover_url: "",
      verified: true,
    },
    stats: {
      followers: "500K+",
      students: "10,000+",
      courses: "15+",
      experience: "5+ năm",
    },
    social: {
      tiktok: "https://tiktok.com/@phananhlien",
      youtube: "https://youtube.com/@phananhlien",
      facebook: "https://facebook.com/phananhlien",
      instagram: "https://instagram.com/phananhlien",
    },
    about: {
      headline: "Từ 0 follower đến Top Creator TikTok Việt Nam",
      bio: "",
      mission:
        "Sứ mệnh của Sonet là giúp mọi người tận dụng sức mạnh của mạng xã hội để phát triển sự nghiệp và thu nhập thụ động.",
    },
    achievements: "[]",
    skills: "[]",
    testimonials: "[]",
    cta: {
      title: "Sẵn sàng bắt đầu hành trình?",
      subtitle: "Tham gia cùng 10,000+ học viên đã thành công với TikTok",
      button_text: "Xem các khóa học",
      button_url: "/courses",
    },
  },
});

const webhookUrl = computed(() => {
  const override = String(form.value.sepay.webhook_url || "").trim();
  if (override) return override;
  const base = String(form.value.site.url || "").trim();
  if (!base) return "";
  return `${base.replace(/\/+$/, "")}/api/sepay/webhook`;
});

// About page array helpers
type Achievement = { icon: string; title: string; description: string };
type Skill = { name: string; level: number };
type Testimonial = {
  name: string;
  avatar: string;
  role: string;
  content: string;
  rating: number;
};

const aboutAchievements = computed<Achievement[]>({
  get() {
    try {
      const arr = JSON.parse(form.value.about.achievements || "[]");
      return Array.isArray(arr) ? arr : [];
    } catch {
      return [];
    }
  },
  set(val) {
    form.value.about.achievements = JSON.stringify(val);
  },
});

const aboutSkills = computed<Skill[]>({
  get() {
    try {
      const arr = JSON.parse(form.value.about.skills || "[]");
      return Array.isArray(arr) ? arr : [];
    } catch {
      return [];
    }
  },
  set(val) {
    form.value.about.skills = JSON.stringify(val);
  },
});

const aboutTestimonials = computed<Testimonial[]>({
  get() {
    try {
      const arr = JSON.parse(form.value.about.testimonials || "[]");
      return Array.isArray(arr) ? arr : [];
    } catch {
      return [];
    }
  },
  set(val) {
    form.value.about.testimonials = JSON.stringify(val);
  },
});

function addAchievement() {
  const arr = [...aboutAchievements.value];
  arr.push({ icon: "trophy", title: "", description: "" });
  aboutAchievements.value = arr;
}

function removeAchievement(idx: number) {
  const arr = [...aboutAchievements.value];
  arr.splice(idx, 1);
  aboutAchievements.value = arr;
}

function addSkill() {
  const arr = [...aboutSkills.value];
  arr.push({ name: "", level: 80 });
  aboutSkills.value = arr;
}

function removeSkill(idx: number) {
  const arr = [...aboutSkills.value];
  arr.splice(idx, 1);
  aboutSkills.value = arr;
}

function addTestimonial() {
  const arr = [...aboutTestimonials.value];
  arr.push({ name: "", avatar: "", role: "", content: "", rating: 5 });
  aboutTestimonials.value = arr;
}

function removeTestimonial(idx: number) {
  const arr = [...aboutTestimonials.value];
  arr.splice(idx, 1);
  aboutTestimonials.value = arr;
}

// Footer links helpers
type FooterLink = { name: string; href: string };
type FooterLinks = {
  courses: FooterLink[];
  support: FooterLink[];
  legal: FooterLink[];
};

function getFooterLinks(): FooterLinks {
  try {
    const parsed = JSON.parse(form.value.footer.links || "{}");
    return {
      courses: Array.isArray(parsed.courses) ? parsed.courses : [],
      support: Array.isArray(parsed.support) ? parsed.support : [],
      legal: Array.isArray(parsed.legal) ? parsed.legal : [],
    };
  } catch {
    return { courses: [], support: [], legal: [] };
  }
}

function setFooterLinks(links: FooterLinks) {
  form.value.footer.links = JSON.stringify(links);
}

const footerCoursesLinks = computed<FooterLink[]>({
  get() {
    return getFooterLinks().courses;
  },
  set(val) {
    const links = getFooterLinks();
    links.courses = val;
    setFooterLinks(links);
  },
});

const footerSupportLinks = computed<FooterLink[]>({
  get() {
    return getFooterLinks().support;
  },
  set(val) {
    const links = getFooterLinks();
    links.support = val;
    setFooterLinks(links);
  },
});

const footerLegalLinks = computed<FooterLink[]>({
  get() {
    return getFooterLinks().legal;
  },
  set(val) {
    const links = getFooterLinks();
    links.legal = val;
    setFooterLinks(links);
  },
});

function addFooterCourseLink() {
  const arr = [...footerCoursesLinks.value];
  arr.push({ name: "", href: "" });
  footerCoursesLinks.value = arr;
}

function removeFooterCourseLink(idx: number) {
  const arr = [...footerCoursesLinks.value];
  arr.splice(idx, 1);
  footerCoursesLinks.value = arr;
}

function addFooterSupportLink() {
  const arr = [...footerSupportLinks.value];
  arr.push({ name: "", href: "" });
  footerSupportLinks.value = arr;
}

function removeFooterSupportLink(idx: number) {
  const arr = [...footerSupportLinks.value];
  arr.splice(idx, 1);
  footerSupportLinks.value = arr;
}

function addFooterLegalLink() {
  const arr = [...footerLegalLinks.value];
  arr.push({ name: "", href: "" });
  footerLegalLinks.value = arr;
}

function removeFooterLegalLink(idx: number) {
  const arr = [...footerLegalLinks.value];
  arr.splice(idx, 1);
  footerLegalLinks.value = arr;
}

async function load() {
  loading.value = true;
  error.value = null;
  try {
    const res = await apiFetch<{ settings: SettingsPayload }>(
      "/api/admin/settings",
    );
    // Deep-merge response with defaults to ensure no keys are lost
    const defaults = form.value;
    const incoming = res.settings;
    for (const section of Object.keys(defaults) as (keyof SettingsPayload)[]) {
      if (incoming[section] === undefined || incoming[section] === null)
        continue;
      if (
        typeof defaults[section] === "object" &&
        !Array.isArray(defaults[section]) &&
        typeof incoming[section] === "object" &&
        !Array.isArray(incoming[section])
      ) {
        for (const key of Object.keys(
          defaults[section] as Record<string, unknown>,
        )) {
          const sub = (incoming[section] as Record<string, unknown>)[key];
          const def = (defaults[section] as Record<string, unknown>)[key];
          if (sub === undefined || sub === null) {
            (incoming[section] as Record<string, unknown>)[key] = def;
          } else if (
            typeof def === "object" &&
            def !== null &&
            !Array.isArray(def) &&
            typeof sub === "object" &&
            sub !== null &&
            !Array.isArray(sub)
          ) {
            (incoming[section] as Record<string, unknown>)[key] = {
              ...(def as Record<string, unknown>),
              ...(sub as Record<string, unknown>),
            };
          }
        }
      }
    }
    form.value = incoming;
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
  // Sync computed array values back to form before serialization
  // (v-model on array items mutates cached arrays in-place without triggering setters)
  form.value.about.achievements = JSON.stringify(aboutAchievements.value);
  form.value.about.skills = JSON.stringify(aboutSkills.value);
  form.value.about.testimonials = JSON.stringify(aboutTestimonials.value);
  setFooterLinks({
    courses: footerCoursesLinks.value,
    support: footerSupportLinks.value,
    legal: footerLegalLinks.value,
  });

  saving.value = true;
  error.value = null;
  try {
    await apiFetch("/api/admin/settings", {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form.value),
    });
    toastSuccess("Đã lưu cài đặt thành công!");
  } catch (e) {
    const msg = extractMessage(e);
    error.value = msg;
    toastError(msg || "Lưu cài đặt thất bại!");
  } finally {
    saving.value = false;
  }
}

async function sendTestEmail() {
  sendingTest.value = true;
  testEmailResult.value = "";
  try {
    const res = await apiFetch("/api/admin/settings/test-email", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ to: testEmailTo.value }),
    });
    testEmailSuccess.value = true;
    testEmailResult.value = (res as any).message || "Gửi thành công!";
  } catch (e) {
    testEmailSuccess.value = false;
    testEmailResult.value = extractMessage(e) || "Gửi email thất bại!";
  } finally {
    sendingTest.value = false;
  }
}

function openPicker(target: string) {
  mediaPickerTarget.value = target;
  openMediaPicker.value = true;
}

function onMediaSelected(asset: MediaAsset | MediaAsset[]) {
  if (Array.isArray(asset)) return;
  if (!asset.url) return;
  if (mediaPickerTarget.value === "logo") {
    form.value.site.logo_url = asset.url;
  }
  if (mediaPickerTarget.value === "favicon") {
    form.value.site.favicon_url = asset.url;
  }
  if (mediaPickerTarget.value === "about_avatar") {
    form.value.about.hero.avatar_url = asset.url;
  }
  if (mediaPickerTarget.value === "about_cover") {
    form.value.about.hero.cover_url = asset.url;
  }
  if (mediaPickerTarget.value?.startsWith("testimonial_")) {
    const idx = parseInt(
      mediaPickerTarget.value.replace("testimonial_", ""),
      10,
    );
    const arr = aboutTestimonials.value;
    if (arr[idx]) arr[idx].avatar = asset.url;
  }
  openMediaPicker.value = false;
}

function selectAll(e: Event) {
  (e.target as HTMLInputElement).select();
}

onMounted(load);
</script>

<style scoped>
.tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 4px;
  background: var(--color-bg-secondary);
  border-radius: 12px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  background: transparent;
  color: var(--color-text-secondary);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.2s;
}

.tab-btn:hover {
  background: var(--color-bg-tertiary);
  color: var(--color-text-primary);
}

.tab-btn.active {
  background: var(--color-primary);
  color: white;
}

.code-textarea {
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", monospace;
  font-size: 13px;
  line-height: 1.5;
  background: #1e1e1e;
  color: #d4d4d4;
  border-radius: 8px;
  padding: 12px;
}

.code-textarea::placeholder {
  color: #6a6a6a;
}

/* Array item styles for About page */
.array-item {
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  margin-bottom: 12px;
  overflow: hidden;
}

.array-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  background: var(--color-bg-tertiary);
  border-bottom: 1px solid var(--color-border);
}

.array-item-number {
  font-weight: 600;
  font-size: 13px;
  color: var(--color-text-secondary);
}

.array-item-body {
  padding: 14px;
}

.array-item-body .label {
  margin-bottom: 12px;
}

.array-item-body .label:last-child {
  margin-bottom: 0;
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

.range-input {
  width: 100%;
  height: 8px;
  background: var(--color-bg-tertiary);
  border-radius: 4px;
  outline: none;
  -webkit-appearance: none;
  cursor: pointer;
}

.range-input::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  background: var(--color-primary);
  border-radius: 50%;
  cursor: pointer;
}

.range-input::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: var(--color-primary);
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 16px;
}

.testimonial-item {
  margin-bottom: 0;
}

.star-rating {
  display: flex;
  gap: 4px;
}

.star-btn {
  background: none;
  border: none;
  font-size: 24px;
  color: #d1d5db;
  cursor: pointer;
  padding: 2px;
  transition:
    color 0.15s,
    transform 0.15s;
}

.star-btn:hover {
  transform: scale(1.2);
}

.star-btn.active {
  color: #f59e0b;
}

.save-bar {
  position: sticky;
  bottom: 16px;
  background: var(--color-bg-primary);
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}
</style>
