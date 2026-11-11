<?php
declare(strict_types=1);
/* CADCraft Didático API v1 — autenticação por sessão e armazenamento JSON local. */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Referrer-Policy: same-origin');
ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (PHP_VERSION_ID < 80100) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'PHP 8.1 ou superior necessário']); exit; }
session_name('CADCRAFTSESS');
session_set_cookie_params(['lifetime'=>0,'path'=>rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/')),'/') . '/', 'secure'=>$secure, 'httponly'=>true,'samesite'=>'Strict']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(24));
// Prefer private storage outside public_html, even when the web server ignores .htaccess.
$private = dirname(__DIR__).DIRECTORY_SEPARATOR.'.cadcraft-private-'.substr(hash('sha256',__DIR__),0,12);
$base=(is_dir($private)||@mkdir($private,0750,true))?$private:__DIR__.DIRECTORY_SEPARATOR.'data';
$account=$base.DIRECTORY_SEPARATOR.'account.json';
$projectsDir=$base.DIRECTORY_SEPARATOR.'projects';
$action=(string)($_GET['action']??'status');
$method=$_SERVER['REQUEST_METHOD']??'GET';
function respond(array $payload, int $status=200): never { http_response_code($status);echo json_encode(['ok'=>$status<400]+$payload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit; }
function error_out(string $message,int $status=400): never {respond(['error'=>$message],$status);}
function body(): array { $raw=file_get_contents('php://input',false,null,0,2000001); if ($raw===false||strlen($raw)>2000000) error_out('Tamanho máximo: 2 MB',413);$decoded=json_decode($raw,true,32);if(!is_array($decoded))error_out('JSON inválido');return $decoded; }
function login_required(): void {if (empty($_SESSION['auth']))error_out('Autenticação necessária',401);}
function csrf_required(): void { $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';if(!is_string($token)||!hash_equals((string)$_SESSION['csrf'],$token))error_out('Token CSRF inválido; recarregue a página',403);}
function get_account(string $filename): ?array {if(!is_file($filename))return null;$v=json_decode((string)file_get_contents($filename),true);return is_array($v)?$v:null;}
function valid_id($id): bool {return is_string($id)&&preg_match('/^id[a-z0-9]{8,50}$/D',$id)===1;}
function project_valid($p): bool {if(!is_array($p)||($p['version']??null)!==1||!valid_id($p['id']??null)||!is_string($p['name']??null)||mbsafe_length($p['name'])>100)return false;
 if(!is_array($p['terrain']??null)||!is_array($p['building']??null)||!is_array($p['requirements']??null)||!is_array($p['levels']??null))return false;
 foreach(['w','d']as $v){if(!is_numeric($p['terrain'][$v]??null)||!is_numeric($p['building'][$v]??null))return false;if((float)$p['building'][$v]<1||(float)$p['building'][$v]>200||(float)$p['terrain'][$v]<3||(float)$p['terrain'][$v]>300)return false;}
 if(count($p['levels'])<1||count($p['levels'])>8)return false;
 foreach($p['levels'] as $lv){if(!is_array($lv)||!is_array($lv['rooms']??null)||!is_array($lv['openings']??null)||!is_array($lv['customWalls']??null)||count($lv['rooms'])>300||count($lv['openings'])>800||count($lv['customWalls'])>800)return false;}
 return true;
}
function mbsafe_length(string $s): int {return function_exists('mb_strlen')?mb_strlen($s):strlen($s);}
if ($action==='status'&&$method==='GET')respond(['installed'=>is_file($account),'authenticated'=>(bool)($_SESSION['auth']??false),'username'=>(string)($_SESSION['username']??''),'csrf'=>$_SESSION['csrf']]);
if ($method!=='POST'&&$method!=='GET')error_out('Método não permitido',405);
if (in_array($action,['setup','login','logout','save','delete'],true)) {if($method!=='POST')error_out('POST necessário',405);csrf_required();}
if ($action==='setup'){
 if(is_file($account))error_out('A instalação já foi concluída',409);
 if(!is_dir($base)&&!mkdir($base,0750,true)&&!is_dir($base))error_out('Pasta data não gravável',500);
 $input=body();$user=(string)($input['username']??'');$pass=(string)($input['password']??'');
 if(!preg_match('/^[a-zA-Z0-9_.-]{3,35}$/D',$user))error_out('Usuário deve conter 3 a 35 caracteres alfanuméricos');
 if(strlen($pass)<10||strlen($pass)>128)error_out('A senha deve ter 10 a 128 caracteres');
 $hash=password_hash($pass,PASSWORD_DEFAULT);
 $fp=@fopen($account,'x');if(!$fp)error_out('Administrador já configurado ou pasta data sem permissão',409);
 $data=json_encode(['username'=>$user,'password_hash'=>$hash,'created'=>gmdate('c')],JSON_UNESCAPED_UNICODE);
 if(fwrite($fp,$data)===false){fclose($fp);@unlink($account);error_out('Não foi possível gravar a configuração',500);}fflush($fp);fclose($fp);@chmod($account,0600);
 session_regenerate_id(true);$_SESSION['auth']=true;$_SESSION['username']=$user;respond(['message'=>'Administrador criado']);
}
if ($action==='login'){
 $input=body();$acct=get_account($account);if(!$acct)error_out('Servidor não configurado',409);
 $attempts=(int)($_SESSION['failures']??0);if($attempts>=8&&(time()-(int)($_SESSION['last_fail']??0)<300))error_out('Muitas tentativas. Tente novamente em alguns minutos',429);
 $user=(string)($input['username']??'');$pass=(string)($input['password']??'');
 if(!hash_equals((string)$acct['username'],$user)||!password_verify($pass,(string)$acct['password_hash'])){$_SESSION['failures']=$attempts+1;$_SESSION['last_fail']=time();error_out('Usuário ou senha inválidos',401);}
 session_regenerate_id(true);$_SESSION['auth']=true;$_SESSION['username']=$user;$_SESSION['failures']=0;respond(['message'=>'Autenticado']);
}
if ($action==='logout'){unset($_SESSION['auth'],$_SESSION['username']);session_regenerate_id(true);respond(['message'=>'Sessão encerrada']);}
login_required();
if (!is_dir($projectsDir)&&!mkdir($projectsDir,0750,true)&&!is_dir($projectsDir))error_out('Diretório de projetos indisponível',500);
if ($action==='list'&&$method==='GET'){
 $list=[];foreach(glob($projectsDir.'/id*.json')?:[] as $file){if(!is_file($file)||filesize($file)>2000000)continue;$p=json_decode((string)file_get_contents($file),true);if(!is_array($p)||!valid_id($p['id']??null))continue;$list[]=['id'=>$p['id'],'name'=>substr((string)($p['name']??'Projeto'),0,120),'updated'=>substr((string)($p['updated']??''),0,35)];}usort($list,fn($a,$b)=>strcmp($b['updated'],$a['updated']));respond(['projects'=>array_slice($list,0,250)]);
}
if ($action==='load'&&$method==='GET'){
 $id=$_GET['id']??null;if(!valid_id($id))error_out('ID inválido');$path=$projectsDir.'/'.$id.'.json';if(!is_file($path))error_out('Projeto não encontrado',404);if(filesize($path)>2000000)error_out('Arquivo excede o limite',413);$p=json_decode((string)file_get_contents($path),true);if(!project_valid($p))error_out('Projeto armazenado inválido',500);respond(['project'=>$p]);
}
if ($action==='save'){
 $input=body();$p=$input['project']??null;if(!project_valid($p))error_out('Dados do projeto inválidos');
 $encoded=json_encode($p,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);if($encoded===false||strlen($encoded)>1900000)error_out('Projeto excede o limite de 1,9 MB',413);
 $path=$projectsDir.'/'.$p['id'].'.json';$temp=tempnam($projectsDir,'writing_');if($temp===false)error_out('Diretório sem permissão de gravação',500);
 if(file_put_contents($temp,$encoded,LOCK_EX)===false){@unlink($temp);error_out('Falha ao salvar projeto',500);} @chmod($temp,0600);
 if(!@rename($temp,$path)){@unlink($temp);error_out('Falha ao finalizar gravação',500);}respond(['id'=>$p['id'],'message'=>'Projeto salvo']);
}
if ($action==='delete'){
 $input=body();$id=$input['id']??null;if(!valid_id($id))error_out('ID inválido');$path=$projectsDir.'/'.$id.'.json';if(!is_file($path))error_out('Projeto não encontrado',404);if(!unlink($path))error_out('Falha ao excluir',500);respond(['message'=>'Projeto removido']);
}
error_out('Ação inválida',404);
