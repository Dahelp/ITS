'use strict';
const fs=require('node:fs');
async function main(){
  const report=JSON.parse(fs.readFileSync(process.argv[2],'utf8'));
  const queue=[...report.products],results=[];
  async function worker(){while(queue.length){
    const p=queue.shift(); let result;
    for(let attempt=0;attempt<2;attempt++)try{
      if(!/^[a-z0-9-]+$/.test(p.alias))throw Error('Invalid alias');
      const r=await fetch('https://its-center.ru/product/'+p.alias,{signal:AbortSignal.timeout(40000),headers:{'Cache-Control':'no-cache'}});
      const html=await r.text();
      const visible=html.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi,'').replace(/<[^>]+>/g,' ').replace(/&nbsp;|&#160;/g,' ');
      const blocks=[...html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)].map(m=>JSON.parse(m[1]));
      const nodes=blocks.flatMap(b=>b['@graph']||[b]);
      const product=nodes.find(b=>b['@type']==='Product'||(Array.isArray(b['@type'])&&b['@type'].includes('Product')));
      const offers=product?.offers;
      const offer=Array.isArray(offers)?offers[0]:offers;
      const current=[...html.matchAll(/class=["'][^"']*product-price-current[^"']*["'][^>]*>([\s\S]*?)<\/div>/gi)].map(m=>m[1].replace(/<[^>]+>/g,' ').replace(/&nbsp;|&#160;/g,' ').trim());
      const numeric=current.map(s=>Number(s.replace(/[^\d.,]/g,'').replace(',','.')));
      const priceOk=p.price===null?current.some(s=>s.includes('Цена по запросу'))&&!offers:numeric.includes(p.price)&&Number(offer?.price)===p.price&&offer?.priceCurrency==='RUB';
      result={article:p.article,path:'/product/'+p.alias,status:r.status,price:p.price,price_ok:priceOk,one_chain:/одн[ауой]+\s+цеп|1\s*(?:цеп|колес)/iu.test(visible),benefits:html.includes('data-chain-benefits="2026-11"')};
      result.ok=r.status===200&&result.price_ok&&result.one_chain&&result.benefits;
      break;
    }catch(e){result={article:p.article,ok:false,error:e.message};}
    results.push(result);
  }}
  await Promise.all(Array.from({length:4},worker));
  const failed=results.filter(r=>!r.ok);
  fs.writeFileSync('reports/http-verification.json',JSON.stringify({checked:results.length,failed,results},null,2));
  console.log(`Checked ${results.length} chain pages; failures ${failed.length}`);
  if(failed.length){console.error(JSON.stringify(failed));process.exitCode=1;}
}
main().catch(e=>{console.error(e.message);process.exitCode=1;});
