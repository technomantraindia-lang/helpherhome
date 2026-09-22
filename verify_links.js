const fs = require('fs');
const path = require('path');

const rootDir = __dirname;
const htmlFiles = [];

function findHtmlFiles(dir) {
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory() && entry.name !== 'node_modules' && entry.name !== '.git') {
      findHtmlFiles(fullPath);
    } else if (entry.isFile() && entry.name.endsWith('.html')) {
      htmlFiles.push(fullPath);
    }
  }
}

findHtmlFiles(rootDir);

let errors = 0;
let checkedLinks = 0;
let checkedImages = 0;

for (const filePath of htmlFiles) {
  const content = fs.readFileSync(filePath, 'utf-8');
  const dir = path.dirname(filePath);
  const relFilePath = path.relative(rootDir, filePath);

  // Check for dummy '#' links
  const dummyLinks = content.match(/href=["']#["']/g);
  if (dummyLinks) {
    console.error(`[ERROR] ${relFilePath} has dummy '#' href!`);
    errors++;
  }

  // Check internal href links
  const hrefMatches = [...content.matchAll(/href=["']([^"']+)["']/g)];
  for (const match of hrefMatches) {
    const link = match[1];
    if (link.startsWith('http') || link.startsWith('mailto:') || link.startsWith('tel:') || link.startsWith('javascript:') || link.startsWith('#')) {
      continue;
    }
    checkedLinks++;
    const cleanLink = link.split('?')[0].split('#')[0];
    if (!cleanLink) continue;
    const targetPath = path.resolve(dir, cleanLink);
    if (!fs.existsSync(targetPath)) {
      console.error(`[ERROR] Broken link in ${relFilePath}: ${link} -> Target does not exist: ${targetPath}`);
      errors++;
    }
  }

  // Check images
  const srcMatches = [...content.matchAll(/src=["']([^"']+)["']/g)];
  for (const match of srcMatches) {
    const src = match[1];
    if (src.startsWith('http') || src.startsWith('data:')) {
      continue;
    }
    checkedImages++;
    const cleanSrc = src.split('?')[0];
    const targetPath = path.resolve(dir, cleanSrc);
    if (!fs.existsSync(targetPath)) {
      console.error(`[ERROR] Missing image in ${relFilePath}: ${src} -> Target does not exist: ${targetPath}`);
      errors++;
    }
  }
}

console.log(`\nVerification Complete! Checked ${htmlFiles.length} HTML files, ${checkedLinks} internal links, ${checkedImages} local image sources.`);
if (errors === 0) {
  console.log('SUCCESS: All internal links and images exist with 0 broken references and 0 dummy "#" links!');
} else {
  console.error(`FAILED: Found ${errors} issues.`);
  process.exit(1);
}

