const fs=require('node:fs'),path=require('node:path'),cp=require('node:child_process'),assert=require('node:assert/strict');
const root=process.cwd(),dir=path.join(root,'tmp','cache-concurrency-'+Date.now());fs.mkdirSync(dir,{recursive:true});
const php=process.env.PHP_BINARY || 'C:/xampp82/php/php.exe',worker=path.join(root,'tests/fixtures/html-cache-worker.php');
function run(mode){return new Promise((resolve,reject)=>{const p=cp.spawn(php,[worker,dir,...(mode?[mode]:[])],{windowsHide:true});let out='',err='';p.stdout.on('data',x=>out+=x);p.stderr.on('data',x=>err+=x);p.on('error',reject);p.on('exit',code=>code?reject(Error(err)):resolve(out));});}
(async()=>{
 const bodies=await Promise.all(Array.from({length:6},()=>run()));
 assert(bodies.every(body=>body===bodies[0]&&body.endsWith('</html>')),'all workers receive complete identical HTML');
 assert.equal(fs.readFileSync(path.join(dir,'renders.txt'),'utf8').trim().split('\n').length,1,'six misses produce one render');
 await run('purge');await run();
 assert.equal(fs.readFileSync(path.join(dir,'renders.txt'),'utf8').trim().split('\n').length,2,'purge forces exactly one fresh render');
 await run('purge');const discarded=await run('clean');assert(discarded.includes('replacement response'));
 const next=await run();assert.equal(next,bodies[0],'discarded output must never become a cached response');
 assert.equal(fs.readFileSync(path.join(dir,'renders.txt'),'utf8').trim().split('\n').length,4,'discarded buffer forces another real render');
 console.log('HTML cache concurrency: six simultaneous requests share one render; purge regenerates complete HTML');
})().catch(e=>{console.error(e);process.exitCode=1;});
