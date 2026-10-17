<?php
require dirname(__DIR__).'/vendor/autoload.php';
use app\services\AvitoChatWorkspace as Workspace;
class_alias(\RedBeanPHP\R::class,'R'); R::setup('sqlite::memory:');
R::exec('CREATE TABLE user (id INTEGER PRIMARY KEY, name TEXT, role TEXT)');
R::exec('CREATE TABLE avito_chat_manager (user_id INTEGER PRIMARY KEY)');
R::exec('CREATE TABLE avito_chat_assignment (chat_key TEXT PRIMARY KEY,user_id INTEGER,updated_by INTEGER)');
R::exec('CREATE TABLE avito_chat_note (id INTEGER PRIMARY KEY AUTOINCREMENT,chat_key TEXT,author_id INTEGER,body TEXT,created_at INTEGER)');
R::exec('CREATE TABLE avito_chat_template (id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT,body TEXT,author_id INTEGER,active INTEGER DEFAULT 1)');
foreach ([[1,'Admin','admin'],[2,'Manager','manager'],[3,'Other','manager'],[4,'Client','user']] as $user) R::exec('INSERT INTO user (id,name,role) VALUES (?,?,?)',$user);
foreach ([1,2,3] as $id) R::exec('INSERT INTO avito_chat_manager VALUES (?)',[$id]);
function workspaceCheck($ok,$label) { if (!$ok) throw new RuntimeException($label); }
Workspace::assign('chat-a',2,1); Workspace::assign('chat-b',3,1);
workspaceCheck(Workspace::workspace('chat-a',2)['assignee'] === 2,'Assignment persists per chat');
workspaceCheck(Workspace::decorateChats(['chats'=>[['id'=>'chat-a']]])['chats'][0]['assigned_to']==='Manager','Responsible staff visible in chat list');
Workspace::assign('chat-a',0,2);
workspaceCheck(!Workspace::workspace('chat-a',2)['assignee'] && Workspace::workspace('chat-b',2)['assignee']===3,'Unassign preserves other chats');
try { Workspace::assign('chat-a',4,1); throw new RuntimeException('Client accepted'); } catch (InvalidArgumentException $expected) {}
R::exec('DELETE FROM avito_chat_manager WHERE user_id=3');
try { Workspace::assign('chat-a',3,1); throw new RuntimeException('Disabled staff accepted'); } catch (InvalidArgumentException $expected) {}
Workspace::addNote('chat-a','Internal only',2); Workspace::addNote('chat-b','Other chat',1);
workspaceCheck(count(Workspace::workspace('chat-a',2)['notes'])===1,'Notes are isolated between chats');
workspaceCheck(Workspace::workspace('chat-a',2)['notes'][0]['author']==='Manager','Note author shown');
Workspace::saveTemplate(0,'Delivery','We deliver',2);
$template=Workspace::workspace('chat-a',2)['templates'][0]; $id=(int)$template['id'];
workspaceCheck($template['can_edit'],'Template author can edit');
workspaceCheck(!Workspace::workspace('chat-a',3)['templates'][0]['can_edit'],'Other manager cannot edit');
try { Workspace::saveTemplate($id,'Hijack','Changed',3); throw new RuntimeException('Other manager edit allowed'); } catch (InvalidArgumentException $expected) {}
try { Workspace::deleteTemplate($id,3); throw new RuntimeException('Other manager delete allowed'); } catch (InvalidArgumentException $expected) {}
Workspace::saveTemplate($id,'Delivery updated','Updated response',1);
workspaceCheck(Workspace::workspace('chat-a',2)['templates'][0]['body']==='Updated response','Admin can update shared template');
Workspace::deleteTemplate($id,2);
workspaceCheck(!Workspace::workspace('chat-a',1)['templates'],'Removed template is hidden');
workspaceCheck((int)R::getCell('SELECT active FROM avito_chat_template WHERE id=?',[$id])===0,'Template removal is reversible');
foreach ([['','x'],['x',str_repeat('a',1001)]] as [$title,$body]) {
    try { Workspace::saveTemplate(0,$title,$body,1); throw new RuntimeException('Invalid template accepted'); } catch (InvalidArgumentException $expected) {}
}
try { Workspace::addNote('chat-a',' ',1); throw new RuntimeException('Blank note accepted'); } catch (InvalidArgumentException $expected) {}
echo "AvitoChatWorkspace: OK\n";
