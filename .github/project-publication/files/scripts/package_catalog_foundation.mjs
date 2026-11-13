import fs from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
import {fileURLToPath} from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const files=[
'app/data/catalog-properties-v1.json',
'app/services/CatalogProperties.php',
'app/services/CatalogPropertyAccess.php',
'app/services/CatalogPropertySchema.php',
'app/services/CatalogSize.php',
'app/services/CatalogLegacyPreview.php',
'app/controllers/admin/CatalogPropertiesController.php',
'app/views/itscenter/admin/CatalogProperties/index.php',
'app/views/itscenter/admin/CatalogProperties/edit.php',
'bin/catalog_foundation.php',
'bin/catalog_foundation_preflight.php'
];
const stamp=new Date().toISOString().replace(/[:.]/g,'-');
const out=path.join(root,'outputs','releases','catalog-foundation-2a-'+stamp);
await fs.mkdir(out,{recursive:true});
const manifest={release:'catalog-foundation-2a',created_at:new Date().toISOString(),production_applied:false,files:[]};
for(const rel of files){
 const data=await fs.readFile(path.join(root,rel));
 const dst=path.join(out,'files',rel);await fs.mkdir(path.dirname(dst),{recursive:true});await fs.writeFile(dst,data);
 manifest.files.push({path:rel,bytes:data.length,sha256:crypto.createHash('sha256').update(data).digest('hex')});
}
await fs.writeFile(path.join(out,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');
await fs.writeFile(path.join(out,'SHA256SUMS'),manifest.files.map(f=>f.sha256+'  files/'+f.path).join('\n')+'\n');
console.log(out);
