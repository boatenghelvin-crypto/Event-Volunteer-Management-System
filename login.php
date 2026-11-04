<?php
require_once __DIR__.'/functions.php';
if(signed_in())redirect('index.php');
if((int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn()===0)redirect('setup.php');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$email=strtolower(trim((string)($_POST['email']??'')));
 $stmt=db()->prepare('SELECT admin_id,password_hash FROM admins WHERE email=?');$stmt->execute([$email]);$admin=$stmt->fetch();
 if(!$admin || !password_verify((string)($_POST['password']??''),$admin['password_hash'])){$errors[]='Email or password is incorrect.';}
 else{session_regenerate_id(true);$_SESSION['admin_id']=(int)$admin['admin_id'];redirect('index.php');}
}
page_header('Sign in');
?>
<section class="auth-panel"><div class="eyebrow">WELCOME BACK</div><h1>Sign in to VolunteerHub</h1><p class="muted">Manage volunteers, events and assignments in one place.</p><?php error_list($errors);?><form method="post" class="form-grid"><?=csrf_field()?><label class="full">Email<input type="email" name="email" required></label><label class="full">Password<input type="password" name="password" required></label><button class="btn primary" type="submit">Sign in</button></form></section>
<?php page_footer(); ?>
