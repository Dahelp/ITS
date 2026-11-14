'use strict';
const fs = require('node:fs');
const forbidden = /собственн[а-яё]*\s+производств|(?:^|[^\p{L}])(?:производим|изготавливаем|изготовим|выпускаем)(?=[^\p{L}]|$)|наш[^\n]{0,35}металлообработ|от\s+производителя|поставщик/iu;
async function main() {
  const publication = JSON.parse(fs.readFileSync(process.argv[2], 'utf8'));
  const results = [];
  const queue = [...publication.urls];
  async function worker() {
    while (queue.length) {
      const path = queue.shift();
      if (!/^\/(?:catalog|category|product)\/[a-z0-9/._-]+$/.test(path)) throw new Error('Unexpected verification URL');
      let result;
      for (let attempt = 0; attempt < 2; attempt++) {
        try {
          const response = await fetch('https://its-center.ru' + path, {
            signal: AbortSignal.timeout(40000), headers: {'Cache-Control': 'no-cache'},
          });
          const html = await response.text();
          const publicHtml = html.replace(/<(script|style)\b[^>]*>[\s\S]*?<\/\1>/gi, '').replace(/&nbsp;|&#160;/g, ' ');
          const text = publicHtml.replace(/<[^>]+>/g, ' ');
          const structuredData = [...html.matchAll(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)]
            .map(match => JSON.stringify(JSON.parse(match[1]))).join(' ');
          const requireBenefits = path.startsWith('/product/') || path.split('/').length === 3;
          result = {path, status: response.status, claimFree: !forbidden.test(text + ' ' + publicHtml + ' ' + structuredData),
            benefits: !requireBenefits || html.includes('data-chain-benefits="2026-11"')};
          result.ok = response.status === 200 && result.claimFree && result.benefits;
          break;
        } catch (error) { result = {path, ok: false, error: error.message}; }
      }
      results.push(result);
    }
  }
  await Promise.all(Array.from({length: 4}, worker));
  const failed = results.filter(r => !r.ok);
  fs.writeFileSync('reports/http-verification.json', JSON.stringify({checked: results.length, failed, results}, null, 2));
  console.log(`Public chain pages: ${results.length}; failures: ${failed.length}`);
  if (failed.length) process.exitCode = 1;
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
