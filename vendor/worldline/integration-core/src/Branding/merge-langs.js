const fs = require('fs');
const path = require('path');

const baseLangDir = path.resolve(__dirname, '..', 'BusinessLogic', 'AdminConfig', 'Resources', 'src', 'lang');
const brandLangDir = path.resolve(__dirname, 'WOP', 'lang');
const outputLangDir = path.resolve(
    __dirname, '..', 'BusinessLogic', 'AdminConfig', 'Resources', 'test', 'resources', 'lang'
);

if (!fs.existsSync(baseLangDir)) return;
if (!fs.existsSync(brandLangDir)) return;

fs.mkdirSync(outputLangDir, { recursive: true });

const files = fs.readdirSync(baseLangDir).filter((f) => f.endsWith('.json'));

files.forEach((file) => {
    const baseFile = path.join(baseLangDir, file);
    const brandFile = path.join(brandLangDir, file);
    const outputFile = path.join(outputLangDir, file);

    const baseContent = JSON.parse(fs.readFileSync(baseFile, 'utf-8'));
    const brandContent = fs.existsSync(brandFile)
        ? JSON.parse(fs.readFileSync(brandFile, 'utf-8'))
        : {};

    const merged = { ...baseContent, ...brandContent };

    fs.writeFileSync(outputFile, JSON.stringify(merged, null, 2), 'utf-8');

});
