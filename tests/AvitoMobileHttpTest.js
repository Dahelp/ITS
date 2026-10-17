const assert = require('node:assert/strict');
const {spawn,spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const net = require('node:net');
const php = process.env.PHP_BINARY || 'C:/xampp82/php/php.exe';
const root = path.resolve(__dirname,'..');
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'avito-mobile-'));
const db = path.join(dir,'test.sqlite');
const bootstrap = `require 'vendor/autoload.php'; class_alias('RedBeanPHP\\R','R'); R::setup('sqlite:'.getenv('AVITO_MOBILE_TEST_DB'));
R::exec('CREATE TABLE user (id INTEGER PRIMARY KEY,email TEXT,name TEXT,role TEXT,password TEXT)');
R::exec('CREATE TABLE avito_chat_manager (user_id INTEGER PRIMARY KEY)');
R::exec('CREATE TABLE avito_mobile_session (token_hash TEXT PRIMARY KEY,user_id INTEGER,password_stamp TEXT,device_name TEXT,created_at INTEGER,expires_at INTEGER)');
R::exec('CREATE TABLE avito_mobile_throttle (bucket TEXT PRIMARY KEY,attempts INTEGER,expires_at INTEGER)');
R::exec('CREATE TABLE avito_push_device (token_hash TEXT PRIMARY KEY,session_hash TEXT,user_id INTEGER,push_token TEXT,platform TEXT,updated_at INTEGER)');
R::exec('INSERT INTO user VALUES (1,?,?,?,?)',['test@example.test','Test manager','manager',password_hash('secret',PASSWORD_DEFAULT)]);
R::exec('INSERT INTO avito_chat_manager VALUES (1)');`;
const env = {...process.env,AVITO_MOBILE_TEST_DB:db};
const setup = spawnSync(php,['-r',bootstrap],{cwd:root,env,encoding:'utf8'});
assert.equal(setup.status,0,setup.stderr+setup.stdout);
let server;
(async()=>{
    const listener = net.createServer(); await new Promise(r=>listener.listen(0,'127.0.0.1',r));
    const port=listener.address().port; await new Promise(r=>listener.close(r));
    server=spawn(php,['-S',`127.0.0.1:${port}`,'tests/fixtures/avito-mobile-router.php'],{cwd:root,env,stdio:'ignore'});
    const base=`http://127.0.0.1:${port}/api/mobile/v1/avito/`;
    for(let i=0;i<50;i++) { try { await fetch(base+'me'); break; } catch { await new Promise(r=>setTimeout(r,100)); } }
    async function call(op,method='GET',body,token,extra={}) {
        const res=await fetch(base+op,{method,headers:{...(body===undefined?{}:{'Content-Type':'application/json'}),...(token?{Authorization:`Bearer ${token}`} : {}),...extra},body:body===undefined?undefined:JSON.stringify(body)});
        assert.match(res.headers.get('cache-control'),/no-store/);
        return {status:res.status,body:await res.json(),headers:res.headers};
    }
    assert.equal((await call('me')).status,401);
    assert.equal((await call('me','GET',undefined,undefined,{Cookie:'PHPSESSID=admin'})).status,401);
    assert.equal((await call('login')).status,405);
    assert.equal((await call('missing')).status,404);
    assert.equal((await call('me?insecure=1')).status,400);
    assert.equal((await call('login','POST',{email:['bad']})).status,400);
    assert.equal((await call('login','POST',{email:'test@example.test',password:'wrong'})).status,401);
    const login=await call('login','POST',{email:'test@example.test',password:'secret',device_name:'HTTP test'});
    assert.equal(login.status,200); const token=login.body.data.access_token;
    assert.equal((await call('me','GET',undefined,token)).body.data.role,'manager');
    assert.equal((await call('capabilities','GET',undefined,token)).body.data.version,1);
    assert.equal((await call('push-status','GET',undefined,token)).body.data.registered,false);
    assert.equal((await call('push-unregister','POST',{},token)).body.data.registered,false);
    assert.equal((await call('sessions','GET',undefined,token)).body.data.sessions[0].current,true);
    assert.equal((await call('send','GET',undefined,token)).status,405);
    assert.equal((await call('send','POST',{chat:'chat',text:''},token)).status,400);
    assert.equal((await call('logout','POST',{},token)).status,200);
    assert.equal((await call('me','GET',undefined,token)).status,401);
    console.log('AvitoMobileHttp: OK (routing, JSON, auth, methods, logout)');
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(()=>{
    if(server) { server.once('exit',()=>fs.rmSync(dir,{recursive:true,force:true})); server.kill(); }
    else fs.rmSync(dir,{recursive:true,force:true});
});
