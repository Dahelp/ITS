// Rasterize the repo-native SVG mark for Android and iOS launchers.
const fs = require('node:fs');
const path = require('node:path');
const sharp = require(process.env.SHARP_MODULE || 'sharp');
const root = path.resolve(__dirname, '..');
const source = path.join(root, 'assets/app-icon.svg');
async function render(target, size) {
  fs.mkdirSync(path.dirname(target), {recursive:true});
  await sharp(source).resize(size,size).flatten({background:'#003b4d'}).png().toFile(target);
}
(async () => {
  for (const [density,size] of Object.entries({mdpi:48,hdpi:72,xhdpi:96,xxhdpi:144,xxxhdpi:192}))
    await render(path.join(root,`android/app/src/main/res/mipmap-${density}/ic_launcher.png`),size);
  const dir = path.join(root,'ios/Runner/Assets.xcassets/AppIcon.appiconset');
  const catalog = JSON.parse(fs.readFileSync(path.join(dir,'Contents.json'),'utf8'));
  for (const icon of catalog.images) if(icon.filename)
    await render(path.join(dir,icon.filename),Math.round(parseFloat(icon.size)*parseFloat(icon.scale)));
  await render(path.join(root,'assets/app-icon.png'),1024);
})().catch(e=>{console.error(e.message);process.exit(1);});
