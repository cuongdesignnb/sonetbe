const fs = require('fs');
const path = 'h:/Sonet/backend/admin/src/pages';
const files = fs.readdirSync(path).filter(f => f.endsWith('.vue'));

let count = 0;
for (const file of files) {
  const fp = `${path}/${file}`;
  let content = fs.readFileSync(fp, 'utf8');
  
  if (content.includes('method: "DELETE"') || content.includes("method: 'DELETE'")) {
    let lines = content.split('\n');
    let changed = false;

    // Check if useToast is imported
    if (!content.includes('import { useToast } from "../lib/toast"')) {
      for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('import { apiFetch')) {
          lines.splice(i + 1, 0, 'import { useToast } from "../lib/toast";');
          changed = true;
          break;
        }
      }
    }

    // Check if toastSuccess is destructured
    if (!content.includes('toastSuccess')) {
      for (let i = 0; i < lines.length; i++) {
        if (lines[i].trim().startsWith("const error =") || lines[i].trim().startsWith("const { success")) {
          lines.splice(i + 1, 0, 'const { success: toastSuccess, error: toastError } = useToast();');
          changed = true;
          break;
        }
      }
    }

    // Add toastSuccess("Đã xóa"); right after apiFetch DELETE
    for (let i = 0; i < lines.length; i++) {
      if (lines[i].includes('method: "DELETE"') || lines[i].includes("method: 'DELETE'")) {
        // Find if the previous lines have confirm
        let hasConfirm = false;
        for(let j = i; j >= Math.max(0, i-5); j--) {
           if (lines[j].includes('confirm(')) {
              hasConfirm = true; break;
           }
        }
        
        if (!hasConfirm) {
           // Insert confirm above
           const indentMatch = lines[i].match(/^\s*/);
           const indent = indentMatch ? indentMatch[0] : '  ';
           lines.splice(i, 0, `${indent}if (!confirm("Bạn có chắc chắn muốn xóa?")) return;`);
           i++; // shift because we added a line
           changed = true;
        }

        // Now search below for toastSuccess
        let hasToast = false;
        for(let j = i; j <= Math.min(lines.length-1, i+5); j++) {
           if (lines[j].includes('toastSuccess')) {
              hasToast = true; break;
           }
        }
        
        if (!hasToast) {
           const indentMatch = lines[i].match(/^\s*/);
           const indent = indentMatch ? indentMatch[0] : '  ';
           lines.splice(i + 1, 0, `${indent}toastSuccess("Xóa thành công.");`);
           changed = true;
        }
      }
    }

    if (changed) {
      fs.writeFileSync(fp, lines.join('\n'));
      console.log(`Updated ${file}`);
      count++;
    }
  }
}

console.log(`Updated ${count} files.`);
