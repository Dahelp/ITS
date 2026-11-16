'use strict';
const fs=require('node:fs');
async function main(){
 const report=JSON.parse(fs.readFileSync(process.argv[2],'utf8')),results=[],queue=[...report.products];
 async function worker(){while(queue.length){const p=queue.shift();let item;
  for(let attempt=0;attempt<3;attempt++)try{
   const r=await fetch('https://its-center.ru/product/'+p.alias,{signal:AbortSignal.timeout(40000),headers:{'Cache-Control':'no-cache'}});const html=await r.text();
   const visible=html.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi,'').replace(/<[^>]+>/g,' ').replace(/&nbsp;|&#160;/g,' ');
   const blocks=[...html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)].map(m=>JSON.parse(m[1]));const product=blocks.flatMap(b=>b['@graph']||[b]).find(b=>b['@type']==='Product');const offer=Array.isArray(product?.offers)?product.offers[0]:product?.offers;
   const prices=[...html.matchAll(/class=["'][^"']*product-price-current[^"']*["'][^>]*>([\s\S]*?)<\/div>/gi)].map(m=>m[1].replace(/<[^>]+>/g,' ').replace(/&nbsp;|&#160;/g,' ').trim());
   const priceOk=p.price===null?prices.some(x=>x.includes('Цена по запросу'))&&!offer:prices.some(x=>Number(x.replace(/[^\d.,]/g,'').replace(',','.'))===p.price)&&Number(offer?.price)===p.price;
   item={article:p.article,status:r.status,price_ok:priceOk,pair_notice:html.includes('data-chain-sale-unit="2"')&&html.includes('data-chain-price-unit="2"'),single_sale_absent:!visible.includes('1 цепь на одно колесо'),package:visible.includes(p.package)};
   item.ok=r.status===200&&priceOk&&item.pair_notice&&item.single_sale_absent&&item.package;if(item.ok)break;
  }catch(e){item={article:p.article,ok:false,error:e.message};}results.push(item);
 }}
 await Promise.all(Array.from({length:3},worker));
 const excluded=await fetch('https://its-center.ru/product/cepi-spectehnika-sota-10-10-pod-zakaz',{signal:AbortSignal.timeout(40000)});const excludedHtml=await excluded.text();
 const excludedOk=excluded.status===200&&!excludedHtml.includes('data-chain-price-unit="2"')&&!excludedHtml.includes('data-chain-sale-unit="2"');
 const failed=results.filter(x=>!x.ok);fs.writeFileSync('reports/http-verification.json',JSON.stringify({checked:results.length,failed,excluded_unchanged:excludedOk,results},null,2));
 console.log(`Pair products checked: ${results.length}; failures: ${failed.length}; excluded: ${excludedOk}`);if(failed.length||!excludedOk){console.error(JSON.stringify(failed));process.exitCode=1;}
}
main().catch(e=>{console.error(e.message);process.exitCode=1;});
