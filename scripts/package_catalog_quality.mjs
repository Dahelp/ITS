import fs from 'node:fs/promises';
import path from 'node:path';
import crypto from 'node:crypto';
import {fileURLToPath} from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const baseline=JSON.parse(await fs.readFile(path.join(root,'outputs/releases/catalog-foundation-2a-2026-11-11T11-21-54-360Z/manifest.json'),'utf8'));
const files=['app/data/catalog-properties-v1.json','app/services/CatalogQuality.php','app/services/CatalogProperties.php','app/services/CatalogLegacyPreview.php','app/controllers/admin/CatalogPropertiesController.php','app/views/itscenter/admin/CatalogProperties/index.php','app/views/itscenter/admin/CatalogProperties/quality.php','app/views/itscenter/admin/CatalogProperties/dictionaries.php','bin/catalog_quality.php'];
const out=path.join(root,'outputs/releases/catalog-quality-2b-'+new Date().toISOString().replace(/[:.]/g,'-'));
const manifest={release:'catalog-quality-2b',created_at:new Date().toISOString(),production_applied:false,files:[]};
for(const p of files){const b=await fs.readFile(path.join(root,p));const destination=path.join(out,'files',p);await fs.mkdir(path.dirname(destination),{recursive:true});await fs.writeFile(destination,b);manifest.files.push({path:p,bytes:b.length,sha256:crypto.createHash('sha256').update(b).digest('hex'),expected_production_sha256:baseline.files.find(f=>f.path===p)?.sha256??null});}
await fs.writeFile(path.join(out,'manifest.json'),JSON.stringify(manifest,null,2)+'\n');console.log(out);
