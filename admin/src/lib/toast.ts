import { ref } from "vue";

type ToastVariant = "success" | "error" | "info";

export type ToastItem = {
  id: number;
  message: string;
  variant: ToastVariant;
};

const toasts = ref<ToastItem[]>([]);
let toastId = 0;

function remove(id: number) {
  toasts.value = toasts.value.filter((t) => t.id !== id);
}

function push(
  message: string,
  variant: ToastVariant = "success",
  timeout = 2600,
) {
  const id = ++toastId;
  toasts.value = [...toasts.value, { id, message, variant }];
  if (timeout > 0) {
    window.setTimeout(() => remove(id), timeout);
  }
}

function success(message: string, timeout?: number) {
  push(message, "success", timeout);
}

function error(message: string, timeout?: number) {
  push(message, "error", timeout);
}

function info(message: string, timeout?: number) {
  push(message, "info", timeout);
}

export function useToast() {
  return { toasts, remove, push, success, error, info };
}
