/* npm --prefix tools/css install, then node scripts/build_frontend_css.cjs [source root] */
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto'),zlib=require('node:zlib');
const root=path.resolve(process.argv[2]||'.');
const tool=process.env.CSS_TOOLCHAIN_DIR||path.resolve(fs.existsSync('tools/css/node_modules/lightningcss')?'tools/css/node_modules':'tmp/css-toolchain/node_modules');
const {transform}=require(path.join(tool,'lightningcss'));
const sources=['css/bootstrap.css','css/flexslider.css','css/aiz-core.css','css/style.css','adminlte/plugins/fontawesome-free/css/all.min.css','adminlte/plugins/select2/css/select2.min.css','adminlte/plugins/select2-bootstrap5-theme/select2-bootstrap-5-theme.min.css','css/swiper-bundle.min.css','css/fonts-override.css'];
const hashes={},chunks=[];let original=0;const warnings=[];
for(const source of sources){
 const raw=fs.readFileSync(path.join(root,'public',source));original+=raw.length;hashes[source]=crypto.createHash('sha256').update(raw).digest('hex');
 let css=raw.toString('utf8').replace(/([;{]\s*)\*[a-z-]+\s*:[^;}]*;/gi,'$1').replace(/@charset\s+["'][^"']+["'];/gi,'').replace(/\/\*[#@]\s*sourceMappingURL=[\s\S]*?\*\//g,'');
 css=css.replace(/url\(\s*(["']?)([^)"']+)\1\s*\)/gi,(all,q,url)=>{
  url=url.trim();if(/^(?:data:|https?:|\/\/|#)/i.test(url))return all;
  const parsed=new URL(url,'https://its-center.ru/'+source);const resolved=parsed.pathname.replace(/^\/public\/adminlte\//,'/adminlte/');
  return 'url("'+resolved+parsed.search+parsed.hash+'")';
 });
 let result;try{result=transform({filename:source,code:Buffer.from(css),minify:true,errorRecovery:true,targets:{chrome:90<<16,firefox:90<<16,safari:14<<16}});}catch(e){throw new Error(source+': '+e.message+' at '+JSON.stringify(e.loc));}
 for(const warning of result.warnings)if(!(source==='css/style.css' && warning.type==='SelectorError' && warning.value?.type==='EmptySelector'))throw Error('Unexpected CSS warning: '+JSON.stringify(warning));
 warnings.push(...result.warnings);const licenses=(raw.toString('utf8').match(/\/\*[\s\S]*?\*\//g)||[]).filter(x=>/copyright|license/i.test(x));chunks.push(licenses.join('\n').replace(/\r\n/g,'\n')+'\n'+'/* '+source+' */\n'+result.code.toString());
}
const output=Buffer.from('@charset "UTF-8";\n'+chunks.join('\n')+'\n');
fs.writeFileSync(path.join(root,'public/css/site.bundle.min.css'),output);
const report={compiler:'lightningcss@1.33.0',sources:hashes,original_bytes:original,bundle_bytes:output.length,gzip_bytes:zlib.gzipSync(output).length,warnings};
fs.writeFileSync(path.join(root,'public/css/site.bundle.json'),JSON.stringify(report,null,2)+'\n');
console.log(JSON.stringify({...report,sources:sources.length}));
