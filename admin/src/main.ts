import { createApp } from "vue";
import { createRouter, createWebHistory } from "vue-router";
import Quill from "quill";
import App from "./App.vue";
import DashboardPage from "./pages/DashboardPage.vue";
import LoginPage from "./pages/LoginPage.vue";
import CoursesPage from "./pages/CoursesPage.vue";
import CourseEditorPage from "./pages/CourseEditorPage.vue";
import MediaLibraryPage from "./pages/MediaLibraryPage.vue";
import CategoriesPage from "./pages/CategoriesPage.vue";
import FaqsPage from "./pages/FaqsPage.vue";
import ReviewsPage from "./pages/ReviewsPage.vue";
import BlogCategoriesPage from "./pages/BlogCategoriesPage.vue";
import BlogPostsPage from "./pages/BlogPostsPage.vue";
import BlogPostEditorPage from "./pages/BlogPostEditorPage.vue";
import UsersPage from "./pages/UsersPage.vue";
import SettingsPage from "./pages/SettingsPage.vue";
import EnrollmentsPage from "./pages/EnrollmentsPage.vue";
import PagesContentPage from "./pages/PagesContentPage.vue";
import ContactPageSettings from "./pages/ContactPageSettings.vue";
import VouchersPage from "./pages/VouchersPage.vue";
import WebinarsPage from "./pages/WebinarsPage.vue";
import WebinarEditorPage from "./pages/WebinarEditorPage.vue";
import WebinarRegistrationsPage from "./pages/WebinarRegistrationsPage.vue";
import EbooksPage from "./pages/EbooksPage.vue";
import EbookEditorPage from "./pages/EbookEditorPage.vue";
import OrdersPage from "./pages/OrdersPage.vue";
import InvoiceRequestsPage from "./pages/InvoiceRequestsPage.vue";
import MenuPage from "./pages/MenuPage.vue";
import ServiceCategoriesPage from "./pages/ServiceCategoriesPage.vue";
import ServicePostsPage from "./pages/ServicePostsPage.vue";
import ServicePostEditorPage from "./pages/ServicePostEditorPage.vue";
import UserGroupsPage from "./pages/UserGroupsPage.vue";

import "./styles.css";
import "quill/dist/quill.snow.css";

const router = createRouter({
  history: createWebHistory("/admin"),
  routes: [
    { path: "/", redirect: "/dashboard" },
    { path: "/login", component: LoginPage },
    { path: "/dashboard", component: DashboardPage },
    { path: "/categories", component: CategoriesPage },
    { path: "/courses", component: CoursesPage },
    { path: "/courses/:id", component: CourseEditorPage },
    { path: "/faqs", component: FaqsPage },
    { path: "/reviews", component: ReviewsPage },
    { path: "/blog-categories", component: BlogCategoriesPage },
    { path: "/blog-posts", component: BlogPostsPage },
    { path: "/blog-posts/:id", component: BlogPostEditorPage },
    { path: "/users", component: UsersPage },
    { path: "/enrollments", component: EnrollmentsPage },
    { path: "/vouchers", component: VouchersPage },
    { path: "/media", component: MediaLibraryPage },
    { path: "/settings", component: SettingsPage },
    { path: "/pages", component: PagesContentPage },
    { path: "/contact-page", component: ContactPageSettings },
    { path: "/webinars", component: WebinarsPage },
    { path: "/webinars/:id", component: WebinarEditorPage },
    { path: "/webinar-registrations", component: WebinarRegistrationsPage },
    { path: "/ebooks", component: EbooksPage },
    { path: "/ebooks/:id", component: EbookEditorPage },
    { path: "/orders", component: OrdersPage },
    { path: "/invoice-requests", component: InvoiceRequestsPage },
    { path: "/menus", component: MenuPage },
    { path: "/service-categories", component: ServiceCategoriesPage },
    { path: "/service-posts", component: ServicePostsPage },
    { path: "/service-posts/:id", component: ServicePostEditorPage },
    { path: "/user-groups", component: UserGroupsPage },
  ],
});

router.beforeEach((to) => {
  if (to.path === "/login") return true;
  const token = localStorage.getItem("admin_token");
  if (!token) return "/login";
  return true;
});

// Ensure Quill is available globally before any async editor mounts
(window as any).Quill = Quill;

createApp(App).use(router).mount("#admin-app");
