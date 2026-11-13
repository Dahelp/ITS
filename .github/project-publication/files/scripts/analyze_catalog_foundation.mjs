/** Offline stage-1 analysis. Reads exported catalogue JSON, writes local review artifacts. */
import fs from 'node:fs/promises';
import path from 'node:path';
const root = process.cwd();
const input = process.argv[2] || 'outputs/catalog-foundation-production-20261111.json';
const data = JSON.parse(await fs.readFile(path.join(root,input),'utf8'));
const clean = s => String(s??'').trim();
const norm = s => clean(s).toLocaleLowerCase('ru').replaceAll('ё','е').replace(/[х×*]/g,'x').replace(/,/g,'.').replace(/\s+/g,' ');
const byId = a => new Map(a.map(v=>[Number(v.id),v]));
const products=byId(data.products), cats=byId(data.category), attrs=byId(data.attribute), vals=byId(data.attribute_value), brands=byId(data.brand);
const group=(a,key)=>{const m=new Map();for(const v of a){const k=key(v);if(!m.has(k))m.set(k,[]);m.get(k).push(v);}return m;};
const pa=group(data.product_attribute,r=>Number(r.product_id));
const ap=group(data.attribute_product,r=>Number(r.product_id));
const duplicates=(a,key)=>[...group(a,key)].filter(([,v])=>v.length>1).map(([key,rows])=>({key,rows}));
const hasAncestor=(category,id)=>{const seen=new Set();while(category&&!seen.has(category)){if(category===id)return true;seen.add(category);category=Number(cats.get(category)?.parent_id);}return false;};
const value=(id,aid)=>(pa.get(id)||[]).filter(r=>Number(r.attribute_id)===aid).map(r=>clean(r.attribute_text)).filter(Boolean);
const normProp=(s,g)=>{let n=norm(s);if(g===1)n=n.replace(/\s*дюйм.*$/,'');if(g===11)n=n.replace(/пневматические|пневматическая/,'pneumatic').replace(/цельнолитые|цельнолитая/,'solid').replace(/бандажные|бандажная/,'bandage');return n;};
const mappings=[{aid:4,gid:2,code:'tire_size'},{aid:7,gid:1,code:'rim_diameter_in'},{aid:8,gid:4,code:'brand'},{aid:9,gid:3,code:'tread_model'},{aid:15,gid:5,code:'equipment_application'},{aid:54,gid:7,code:'hub_mm'},{aid:55,gid:8,code:'pcd_mm'},{aid:57,gid:9,code:'bolt_count'},{aid:58,gid:6,code:'offset_mm'},{aid:59,gid:10,code:'rim_designation'},{aid:61,gid:11,code:'tire_type'},{aid:62,gid:12,code:'surface'},{aid:75,gid:13,code:'rim_ring_type'},{aid:68,gid:14,code:'seal_section_diameter_mm'}];
const comparison=[];
for(const m of mappings){const summary={...m,attribute_only:0,filter_only:0,equal:0,needs_review:0};for(const p of data.products){const av=value(Number(p.id),m.aid);const fv=(ap.get(Number(p.id))||[]).map(r=>vals.get(Number(r.attr_id))).filter(v=>v&&Number(v.attr_group_id)===m.gid).map(v=>clean(v.value));if(!av.length&&!fv.length)continue;let state=!fv.length?'attribute_only':!av.length?'filter_only':av.every(a=>fv.some(f=>normProp(f,m.gid)===normProp(a,m.gid)))&&fv.every(f=>av.some(a=>normProp(a,m.gid)===normProp(f,m.gid)))?'equal':'needs_review';summary[state]++;if(state!=='equal')comparison.push({product_id:p.id,sku:p.sku,category_id:p.category_id,code:m.code,state,attribute_values:av,filter_values:fv});}m.summary=summary;}
function resolve(template, id){let text=String(template??'');const unresolved=[];for(const line of text.split(/\r?\n/)){for(const match of line.matchAll(/\{([^}]+)\}/g)){const candidates=(pa.get(id)||[]).filter(r=>attrs.get(Number(r.attribute_id))?.url_params===match[1]);const v=clean(candidates[0]?.attribute_text);if(!v){unresolved.push(match[1]);text=text.replaceAll(line,'');break;}text=text.replaceAll(match[0],v);}}return {text:text.replace(/[\r\n]/g,' ').replace(/ {2,}/g,' ').trim(),missing:[...new Set(unresolved)]};}
const templates=group(data.plagins_inseo.filter(t=>t.tip==='product'&&t.hide==='show'),t=>Number(t.category_id));
const issues=[];const inventory=[];
for(const p of data.products){const id=Number(p.id);const at=pa.get(id)||[];const ft=ap.get(id)||[];const ts=templates.get(Number(p.category_id))||[];const template=ts[0];const h1raw=clean(p.seo_h1)||clean(template?.name)||p.name;const h1=resolve(h1raw,id);const title=clean(p.title)?{text:p.title,missing:[]}:resolve(template?.title,id);const description=clean(p.description)?{text:p.description,missing:[]}:resolve(template?.description,id);const tyre=hasAncestor(Number(p.category_id),34);const industrial=hasAncestor(Number(p.category_id),1);const typeFacts=[...value(id,61),...ft.map(r=>vals.get(Number(r.attr_id))).filter(v=>v&&Number(v.attr_group_id)===11).map(v=>v.value)];const type=typeFacts.some(v=>/цельнолит/iu.test(v))?'solid':typeFacts.some(v=>/бандаж/iu.test(v))?'bandage':typeFacts.some(v=>/пневмат/iu.test(v))?'pneumatic':'unknown';const problems=[];
 const add=(code,detail)=>problems.push({code,detail});
 if(!clean(p.name))add('name_empty','Нет названия');if(!clean(p.sku))add('sku_empty','Нет offer ID');if(!clean(p.alias))add('alias_empty','Нет URL');
 if(!at.length)add('attributes_absent','Нет характеристик');if(!ft.length)add('filters_absent','Нет связей с фильтрами');
 if(!h1.text)add('h1_effective_empty','Пустой H1 после InSEO');if(h1.missing.length)add('h1_placeholders_missing',h1.missing.join(', '));
 if(!title.text)add('title_effective_empty','Нет итогового Title');if(title.missing.length)add('title_placeholders_missing',title.missing.join(', '));
 if(!description.text)add('description_effective_empty','Нет итогового meta Description');
 if(!clean(p.img)||/^(no[_-]?image|noimage)/i.test(p.img))add('image_field_empty','Нет основного фото в поле; проверка файлов отдельно');
 const base=data.image_files?.['baseimg/'+clean(p.img)];
 const unload=data.image_files?.['unload/'+clean(p.unload_img)];
 if(clean(p.img)&&base&&!base.exists)add('base_image_file_missing','Основной файл отсутствует на сервере');
 if(clean(p.unload_img)&&unload&&!unload.exists)add('export_image_file_missing','Файл выгрузки отсутствует на сервере');
 if(clean(p.img)&&base?.exists&&!base.width)add('base_image_invalid','Не удалось распознать растровое изображение');
 if(base?.width&&base?.height&&(base.width<450||base.height<450))add('base_image_small_review','Меньше 450px по одной стороне; проверить целевой канал');
 if(!p.content_plain_length)add('own_content_empty','Нет собственного описания; не означает отсутствия текста InSEO');
 if(tyre){for(const [aid,code]of [[4,'tire_size'],[7,'rim_diameter'],[61,'tire_type'],[15,'application']])if(!value(id,aid).length)add(code+'_attribute_missing','Нет отдельной характеристики');if(type==='pneumatic'){for(const [aid,code]of [[63,'construction'],[16,'sealing_and_package'],[70,'load_index'],[71,'speed_index']])if(!value(id,aid).length)add(code+'_review','Проверить применимость и подтверждение; не заполнять догадкой');}}
 const w=Number(value(id,19)[0]?.replace(',','.'));if(w>2000)add('weight_suspicious','Вес больше 2000 кг — проверить единицы');
 const psi=Number(value(id,12)[0]?.replace(',','.'));if(psi>300)add('pressure_psi_suspicious','Давление больше 300 PSI — проверить единицы');
 if(ts.length>1)add('template_ambiguous','Несколько активных шаблонов категории; LIMIT без ORDER BY');
 const stock=Math.max(0,Number(p.quantity||0)+data.modification.filter(m=>Number(m.product_id)===id).reduce((s,m)=>s+Number(m.quantity||0),0)-Number(p.reserve||0));
 const item={id,sku:p.sku,name:p.name,category_id:Number(p.category_id),category:cats.get(Number(p.category_id))?.name,visibility:p.hide,tyre,industrial,type,stock,has_stock:stock>0,h1_source:clean(p.seo_h1)?'manual':clean(template?.name)?'inseo':'name',effective_h1:h1.text,effective_title:title.text,effective_description:description.text,template_id:template?.id,attribute_rows:at.length,filter_rows:ft.length,problems};inventory.push(item);for(const problem of problems)issues.push({product_id:id,sku:p.sku,category_id:p.category_id,visibility:p.hide,...problem});
}
const integrity={
 duplicate_product_attributes:duplicates(data.product_attribute,r=>r.product_id+':'+r.attribute_id),
 duplicate_filter_links:duplicates(data.attribute_product,r=>r.product_id+':'+r.attr_id),
 duplicate_category_groups:duplicates(data.attribute_category,r=>r.category_id+':'+r.group_id),
 duplicate_filter_values:duplicates(data.attribute_value,v=>v.attr_group_id+':'+norm(v.value)),
 duplicate_skus:duplicates(data.products.filter(p=>clean(p.sku)),p=>clean(p.sku)),
 duplicate_aliases:duplicates(data.products.filter(p=>clean(p.alias)),p=>clean(p.alias)),
 orphan_product_attributes:data.product_attribute.filter(r=>!products.has(Number(r.product_id))||!attrs.has(Number(r.attribute_id))),
 orphan_filter_links:data.attribute_product.filter(r=>!products.has(Number(r.product_id))||!vals.has(Number(r.attr_id))),
 wrong_attribute_section:data.product_attribute.filter(r=>attrs.has(Number(r.attribute_id))&&Number(attrs.get(Number(r.attribute_id)).attribute_group_id)!==Number(r.attribute_group_id)),
 orphan_filter_values:data.attribute_value.filter(v=>!data.attribute_group.some(g=>Number(g.id)===Number(v.attr_group_id))),
 duplicate_effective_h1:duplicates(inventory.filter(p=>p.visibility==='show'&&clean(p.effective_h1)),p=>norm(p.effective_h1)).map(g=>({key:g.key,products:g.rows.map(p=>({id:p.id,sku:p.sku}))})),
 duplicate_effective_title:duplicates(inventory.filter(p=>p.visibility==='show'&&clean(p.effective_title)),p=>norm(p.effective_title)).map(g=>({key:g.key,products:g.rows.map(p=>({id:p.id,sku:p.sku}))}))
};
const distribution=aid=>[...group(data.product_attribute.filter(r=>Number(r.attribute_id)===aid),r=>clean(r.attribute_text))].map(([value,rows])=>({value,count:rows.length})).sort((a,b)=>b.count-a.count);
const categories=data.category.map(c=>{const p=inventory.filter(p=>p.category_id===Number(c.id));return {id:c.id,name:c.name,total:p.length,visible:p.filter(p=>p.visibility==='show').length,stock:p.filter(p=>p.has_stock).length,no_attributes:p.filter(p=>!p.attribute_rows).length,no_filters:p.filter(p=>!p.filter_rows).length};}).filter(c=>c.total);
const issueCounts=[...group(issues,i=>i.code)].map(([code,rows])=>({code,total:rows.length,visible:rows.filter(i=>i.visibility==='show').length}));
const result={generated_at:data.generated_at,scope:data.scope,counts:{products:inventory.length,visible:inventory.filter(p=>p.visibility==='show').length,tyres:inventory.filter(p=>p.tyre).length,industrial:inventory.filter(p=>p.industrial).length,without_stock:inventory.filter(p=>!p.has_stock).length,h1_manual:inventory.filter(p=>p.h1_source==='manual').length,h1_equals_name:data.products.filter(p=>p.seo_h1===p.name).length},categories,integrity,issueCounts,mappings:mappings.map(m=>m.summary),comparison,distributions:{type:distribution(61),construction:distribution(63),sealing:distribution(16),application:distribution(15),speed:distribution(71),load:distribution(70)},inventory};
await fs.writeFile(path.join(root,'outputs/catalog-foundation-analysis-20261111.json'),JSON.stringify(result,null,2));
console.log(JSON.stringify({counts:result.counts,integrity:Object.fromEntries(Object.entries(integrity).map(([k,v])=>[k,v.length])),issueCounts,mappings:result.mappings,categories},null,2));
