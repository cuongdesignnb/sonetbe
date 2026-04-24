export type Category = {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  image?: string | null;
  parent_id?: number | null;
  is_active?: boolean;
  courses_count?: number;
  parent?: { id: number; name: string } | null;
};

export type Course = {
  id: number;
  slug: string;
  title: string;
  description: string;
  price: string | number;
  original_price: string | number | null;
  thumbnail: string | null;
  preview_video: string | null;
  category_id: number;
  instructor_id: number;
  level: "beginner" | "intermediate" | "advanced";
  status: "draft" | "published" | "archived" | "coming_soon";
  sort_order: number;
  meta_description: string | null;
  marketing?: any;
  badge_text: string | null;
  badge_color: string | null;
};

export type CourseSection = {
  id: number;
  course_id: number;
  title: string;
  description: string | null;
  order: number;
};

export type Lesson = {
  id: number;
  course_id: number;
  section_id: number | null;
  title: string;
  description: string | null;
  order: number;
  duration: number | null;
  is_preview: boolean;
  thumbnail?: string | null;
  video_bunny_id: string | null;
  video_bunny_library_id: string | null;
  video_local_path: string | null;
  video_url: string | null;
  embed_url: string | null;
};

export type MediaAsset = {
  id: number;
  type: "image" | "video" | "file";
  folder_id: number | null;
  url: string | null;
  path: string | null;
  original_name: string | null;
  mime_type: string | null;
  size: number | null;
  created_at: string;
};

export type MediaFolder = {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  order: number;
  created_at: string;
};

export type CourseFaq = {
  id: number;
  course_id: number | null;
  question: string;
  answer: string;
  order: number;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
};

export type Review = {
  id: number;
  course_id: number;
  user_id: number | null;
  reviewer_name?: string | null;
  rating: number;
  comment: string;
  is_approved: boolean;
  approved_at?: string | null;
  created_at?: string;
  updated_at?: string;
  user?: { id: number; name: string } | null;
  course?: { id: number; title: string } | null;
};

export type BlogCategory = {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  is_active: boolean;
  posts_count?: number;
};

export type BlogPost = {
  id: number;
  category_id: number | null;
  author_id: number | null;
  title: string;
  slug: string;
  excerpt?: string | null;
  content: string;
  featured_image?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
  meta_keywords?: string | null;
  status: "draft" | "published" | "archived";
  published_at?: string | null;
  created_at?: string;
  updated_at?: string;
  category?: { id: number; name: string } | null;
};

export type ServiceCategory = {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  is_active: boolean;
  posts_count?: number;
};

export type ServicePost = {
  id: number;
  category_id: number | null;
  author_id: number | null;
  title: string;
  slug: string;
  excerpt?: string | null;
  content: string;
  featured_image?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
  meta_keywords?: string | null;
  status: "draft" | "published" | "archived";
  published_at?: string | null;
  created_at?: string;
  updated_at?: string;
  category?: { id: number; name: string } | null;
};

export type AdminUser = {
  id: number;
  name: string;
  email: string;
  role: "admin" | "instructor" | "student";
  avatar?: string | null;
  bio?: string | null;
  phone?: string | null;
  date_of_birth?: string | null;
  is_active?: boolean;
  created_at?: string;
};

export type AdminEnrollment = {
  id: number;
  status: "pending" | "active" | "completed" | string;
  enrolled_at?: string | null;
  completed_at?: string | null;
  amount_paid?: string | number | null;
  created_at?: string;
  user?: { id: number; name: string; email: string } | null;
  course?: { id: number; title: string; price: string | number } | null;
  payment_status?: "paid" | "pending" | "free" | string | null;
  payment?: {
    id: number;
    status: "paid" | "pending" | string;
    amount: string | number;
    order_code?: string | null;
    paid_at?: string | null;
    sepay_txn_id?: string | null;
    transfer_amount?: string | number | null;
  } | null;
};

export type Voucher = {
  id: number;
  code: string;
  name: string;
  description?: string | null;
  discount_type: "fixed" | "percent";
  discount_value: string | number;
  min_order_amount: string | number;
  max_discount?: string | number | null;
  usage_limit?: number | null;
  used_count: number;
  usage_per_user?: number | null;
  valid_from?: string | null;
  valid_until?: string | null;
  status: "active" | "inactive" | "expired";
  applicable_type?: "all" | "specific";
  courses?: { id: number; title: string }[];
  created_at?: string;
  updated_at?: string;
  usages_count?: number;
};
