<?php
require_once __DIR__.'/functions.php';require_login();$pdo=db();$errors=[];
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT)?:0;$editing=null;
if($editId){$stmt=$pdo->prepare('SELECT * FROM volunteers WHERE volunteer_id=?');$stmt->execute([$editId]);$editing=$stmt->fetch();if(!$editing){flash('Volunteer not found.','error');redirect('volunteers.php');}}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'save');$id=filter_var($_POST['volunteer_id']??0,FILTER_VALIDATE_INT)?:0;
 if($action==='delete'){$stmt=$pdo->prepare('DELETE FROM volunteers WHERE volunteer_id=?');$stmt->execute([$id]);flash('Volunteer deleted. Their assignments were also removed.');redirect('volunteers.php');}
 $data=['first_name'=>trim((string)($_POST['first_name']??'')),'last_name'=>trim((string)($_POST['last_name']??'')),'email'=>strtolower(trim((string)($_POST['email']??''))),'phone'=>trim((string)($_POST['phone']??'')),'skills'=>trim((string)($_POST['skills']??''))];
 if($data['first_name']===''||mb_strlen($data['first_name'])>60)$errors[]='Enter a first name (max 60 characters).';
 if($data['last_name']===''||mb_strlen($data['last_name'])>60)$errors[]='Enter a last name (max 60 characters).';
 if(!valid_email($data['email'])||strlen($data['email'])>120)$errors[]='Enter a valid email.';
 if(strlen($data['phone'])>25||strlen($data['skills'])>255)$errors[]='Phone or skills text is too long.';
 if(!$errors){try{
  if($id){$stmt=$pdo->prepare('UPDATE volunteers SET first_name=?,last_name=?,email=?,phone=?,skills=? WHERE volunteer_id=?');$stmt->execute([...array_values($data),$id]);}
  else{$stmt=$pdo->prepare('INSERT INTO volunteers(first_name,last_name,email,phone,skills) VALUES(?,?,?,?,?)');$stmt->execute(array_values($data));}
  flash($id?'Volunteer updated.':'Volunteer added.');redirect('volunteers.php');
 }catch(PDOException $e){$errors[]='That email may already be in use. Please use a unique email.';}}
 $editing=['volunteer_id'=>$id]+$data;
}
$rows=$pdo->query('SELECT v.*,COUNT(a.assignment_id) AS assignments FROM volunteers v LEFT JOIN assignments a ON a.volunteer_id=v.volunteer_id GROUP BY v.volunteer_id ORDER BY v.last_name,v.first_name')->fetchAll();
page_header('Volunteers');
?>
<div class="page-title"><div><div class="eyebrow">PEOPLE</div><h1>Volunteers</h1><p class="muted">Add and update volunteer contact details and skills.</p></div></div>
<section class="panel"><h2><?=$editing?'Edit volunteer':'Add volunteer'?></h2><?php error_list($errors);?><form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="volunteer_id" value="<?=h($editing['volunteer_id']??'')?>"><label>First name<input name="first_name" required maxlength="60" value="<?=h($editing['first_name']??'')?>"></label><label>Last name<input name="last_name" required maxlength="60" value="<?=h($editing['last_name']??'')?>"></label><label>Email<input type="email" name="email" required maxlength="120" value="<?=h($editing['email']??'')?>"></label><label>Phone<input name="phone" maxlength="25" value="<?=h($editing['phone']??'')?>"></label><label class="full">Skills<input name="skills" maxlength="255" placeholder="e.g., Setup, communication" value="<?=h($editing['skills']??'')?>"></label><div class="form-actions"><button class="btn primary" type="submit"><?=$editing?'Save changes':'Add volunteer'?></button><?php if($editing):?><a class="btn outline" href="volunteers.php">Cancel edit</a><?php endif;?></div></form></section>
<section class="panel"><div class="section-heading"><h2>Volunteer directory</h2><span class="muted"><?=count($rows)?> total</span></div><div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Skills</th><th>Assignments</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $v):?><tr><td><strong><?=h($v['first_name'].' '.$v['last_name'])?></strong></td><td><?=h($v['email'])?></td><td><?=h($v['phone']?:'—')?></td><td><?=h($v['skills']?:'—')?></td><td><?=h($v['assignments'])?></td><td class="actions"><a href="volunteers.php?edit=<?=(int)$v['volunteer_id']?>">Edit</a><form method="post" class="inline" onsubmit="return confirm('Delete this volunteer and their assignments?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="volunteer_id" value="<?=(int)$v['volunteer_id']?>"><button type="submit" class="text-danger">Delete</button></form></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="6" class="muted">No volunteers yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php page_footer(); ?>
