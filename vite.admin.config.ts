import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "node:path";

export default defineConfig(({ mode }) => {
  const nodeEnv = mode === "production" ? "production" : "development";

  return {
    root: path.resolve(__dirname, "admin"),
    plugins: [vue()],
    base: "/admin/",
    define: {
      "process.env.NODE_ENV": JSON.stringify(nodeEnv),
      "process.env": JSON.stringify({ NODE_ENV: nodeEnv }),
      process: JSON.stringify({ env: { NODE_ENV: nodeEnv } }),
    },
    build: {
      outDir: path.resolve(__dirname, "public/admin"),
      emptyOutDir: true,
      lib: {
        entry: path.resolve(__dirname, "admin/src/main.ts"),
        name: "CourseAdmin",
        formats: ["iife"],
        fileName: () => "admin.js",
      },
      rollupOptions: {
        output: {
          assetFileNames: (assetInfo) => {
            if (assetInfo.name && assetInfo.name.endsWith(".css")) {
              return "admin.css";
            }
            return "assets/[name]-[hash][extname]";
          },
        },
      },
    },
  };
});
