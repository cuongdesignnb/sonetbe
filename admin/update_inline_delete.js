const fs = require('fs');
const path = require('path');

function walkDir(dir, callback) {
  fs.readdirSync(dir).forEach(f => {
    let dirPath = path.join(dir, f);
    let isDirectory = fs.statSync(dirPath).isDirectory();
    if (isDirectory) {
      walkDir(dirPath, callback);
    } else if (dirPath.endsWith('.vue')) {
      callback(dirPath);
    }
  });
}

const vueDirs = [
  'h:/Sonet/backend/admin/src/pages',
  'h:/Sonet/backend/admin/src/components'
];

let count = 0;
vueDirs.forEach(dir => {
  walkDir(dir, fp => {
    let content = fs.readFileSync(fp, 'utf8');
    let changed = false;

    // We look for @click="something.splice(idx, 1)" and wrap it in confirm
    const inlineRegex = /@click="([a-zA-Z0-9_.[\]]+\.splice\([^)]+\))"/g;
    
    const newContent = content.replace(inlineRegex, (match, p1) => {
      if (match.includes('confirm')) return match; // already has confirm
      changed = true;
      return `@click="confirm('Bạn có chắc chắn muốn xóa?') && ${p1}"`;
    });

    if (changed) {
      fs.writeFileSync(fp, newContent);
      console.log(`Updated inline splices in ${fp}`);
      count++;
    }
  });
});

console.log(`Updated inline splices in ${count} files.`);
