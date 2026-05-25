export function formatPriceInput(val: number | null | undefined): string {
  if (val === null || val === undefined || isNaN(Number(val))) return "";
  return new Intl.NumberFormat("vi-VN").format(val) + " đ";
}

export function parsePriceInput(val: string): number | null {
  const clean = val.replace(/\D/g, "");
  if (!clean) return null;
  return parseInt(clean, 10);
}

export function formatPriceDisplay(val: number | null | undefined): string {
  if (val === null || val === undefined || isNaN(Number(val))) return "0 đ";
  if (Number(val) === 0) return "Miễn phí";
  return new Intl.NumberFormat("vi-VN").format(val) + " đ";
}
