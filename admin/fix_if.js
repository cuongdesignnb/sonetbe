const fs = require('fs');
const files = [
  'src/pages/WebinarEditorPage.vue',
  'src/pages/EbookEditorPage.vue',
  'src/pages/CourseEditorPage.vue',
  'src/components/CourseLandingEditor.vue'
];

for (const f of files) {
  let content = fs.readFileSync(f, 'utf8');
  content = content.replace(/if\(confirmDelete\(\)\)\s+/g, 'confirmDelete() && ');
  fs.writeFileSync(f, content, 'utf8');
  console.log(`Fixed ${f}`);
}
