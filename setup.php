<?php
require_once __DIR__.'/functions.php';
$count=(int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if($count>0) redirect('login.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();
 $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
 if(!valid_email($email)) $errors[]='Enter a valid email address.';
 if(strlen($password)<12) $errors[]='Password must be at least 12 characters.';
 if(!hash_equals($password,(string)($_POST['confirm']??''))) $errors[]='Passwords do not match.';
 if(!$errors){
  $stmt=db()->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)');
  $stmt->execute([$email,password_hash($password,PASSWORD_DEFAULT)]);
  flash('Admin account created. Sign in to continue.');redirect('login.php');
 }
}
page_header('First-time setup');
?>
<section class="auth-panel"><div class="eyebrow">FIRST-TIME SETUP</div><h1>Create your administrator account</h1><p class="muted">This screen is available only until the first admin account is created.</p>
<?php error_list($errors); ?>
<form method="post" class="form-grid"><?=csrf_field()?><label class="full">Email<input type="email" name="email" required maxlength="120" value="<?=h($_POST['email']??'')?>"></label><label class="full">Password (12+ characters)<input type="password" name="password" required minlength="12"></label><label class="full">Confirm password<input type="password" name="confirm" required minlength="12"></label><button class="btn primary" type="submit">Create admin</button></form></section>
<?php page_footer(); ?>
