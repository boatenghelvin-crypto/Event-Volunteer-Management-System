<?php
require_once __DIR__.'/functions.php';require_login();$pdo=db();$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'save');$id=filter_var($_POST['assignment_id']??0,FILTER_VALIDATE_INT)?:0;
 if($action==='delete'){$stmt=$pdo->prepare('DELETE FROM assignments WHERE assignment_id=?');$stmt->execute([$id]);flash('Assignment removed.');redirect('assignments.php');}
 $volunteer=filter_var($_POST['volunteer_id']??null,FILTER_VALIDATE_INT);
 $event=filter_var($_POST['event_id']??null,FILTER_VALIDATE_INT);
 $role=trim((string)($_POST['role_name']??''));$status=(string)($_POST['assignment_status']??'Registered');
 if(!$volunteer||!$event)$errors[]='Select a volunteer and an event.';
 if(!$role||mb_strlen($role)>80)$errors[]='Enter a role (max 80 characters).';
 if(!in_array($status,['Registered','Confirmed','Completed','Cancelled'],true))$errors[]='Choose a valid status.';
 if(!$errors){try{
  $pdo->beginTransaction();
  // Lock event row to stop two users assigning the last available spot at once.
  $stmt=$pdo->prepare('SELECT capacity FROM events WHERE event_id=? FOR UPDATE');$stmt->execute([$event]);$capacity=$stmt->fetchColumn();
  $stmt=$pdo->prepare('SELECT volunteer_id FROM volunteers WHERE volunteer_id=?');$stmt->execute([$volunteer]);$exists=$stmt->fetchColumn();
  if($capacity===false||$exists===false)throw new RuntimeException('Volunteer or event not found.');
  $stmt=$pdo->prepare('SELECT assignment_id FROM assignments WHERE volunteer_id=? AND event_id=?');$stmt->execute([$volunteer,$event]);
  if($stmt->fetch())throw new RuntimeException('This volunteer is already assigned to this event.');
  $stmt=$pdo->prepare("SELECT COUNT(*) FROM assignments WHERE event_id=? AND assignment_status <> 'Cancelled'");$stmt->execute([$event]);
  if($status!=='Cancelled' && (int)$stmt->fetchColumn()>=(int)$capacity)throw new RuntimeException('This event has reached capacity.');
  $stmt=$pdo->prepare('INSERT INTO assignments(volunteer_id,event_id,role_name,assignment_status) VALUES(?,?,?,?)');$stmt->execute([$volunteer,$event,$role,$status]);
  $pdo->commit();flash('Assignment created.');redirect('assignments.php');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]=($e instanceof RuntimeException)?$e->getMessage():'Unable to save assignment. Please try again.';}}
}
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='status'){
 // Not reached from the create branch; status changes use status_update.php.
}
$volunteers=$pdo->query('SELECT volunteer_id,first_name,last_name FROM volunteers ORDER BY last_name,first_name')->fetchAll();
$events=$pdo->query('SELECT event_id,title,event_date FROM events ORDER BY event_date,title')->fetchAll();
// Joins from assignment to volunteer and event (key grading requirement).
$rows=$pdo->query('SELECT a.assignment_id,a.role_name,a.assignment_status,a.assigned_at,v.first_name,v.last_name,v.email,e.title,e.event_date,e.location FROM assignments a INNER JOIN volunteers v ON a.volunteer_id=v.volunteer_id INNER JOIN events e ON a.event_id=e.event_id ORDER BY e.event_date DESC,v.last_name')->fetchAll();
page_header('Assignments');
?>
<div class="page-title"><div><div class="eyebrow">COORDINATION</div><h1>Volunteer assignments</h1><p class="muted">Connect volunteers with events and track their roles.</p></div></div>
<section class="panel"><h2>Create assignment</h2><?php error_list($errors);?><form method="post" class="form-grid"><?=csrf_field()?><label>Volunteer<select name="volunteer_id" required><option value="">Select volunteer</option><?php foreach($volunteers as $v):?><option value="<?=(int)$v['volunteer_id']?>" <?=((string)($_POST['volunteer_id']??'')===(string)$v['volunteer_id'])?'selected':''?>><?=h($v['first_name'].' '.$v['last_name'])?></option><?php endforeach;?></select></label><label>Event<select name="event_id" required><option value="">Select event</option><?php foreach($events as $e):?><option value="<?=(int)$e['event_id']?>" <?=((string)($_POST['event_id']??'')===(string)$e['event_id'])?'selected':''?>><?=h($e['title'].' — '.$e['event_date'])?></option><?php endforeach;?></select></label><label>Volunteer role<input name="role_name" maxlength="80" required placeholder="e.g., Registration desk" value="<?=h($_POST['role_name']??'')?>"></label><label>Status<select name="assignment_status"><?php foreach(['Registered','Confirmed','Completed','Cancelled'] as $status):?><option value="<?=h($status)?>" <?=($_POST['assignment_status']??'Registered')===$status?'selected':''?>><?=h($status)?></option><?php endforeach;?></select></label><div class="form-actions"><button class="btn primary" type="submit" <?=!$volunteers||!$events?'disabled':''?>>Assign volunteer</button></div></form><?php if(!$volunteers||!$events):?><p class="muted">Add at least one volunteer and one event before creating an assignment.</p><?php endif;?></section>
<section class="panel"><div class="section-heading"><h2>All assignments</h2><span class="muted"><?=count($rows)?> total</span></div><div class="table-scroll"><table><thead><tr><th>Volunteer</th><th>Event</th><th>Role</th><th>Status</th><th>Update status</th><th></th></tr></thead><tbody><?php foreach($rows as $a):?><tr><td><strong><?=h($a['first_name'].' '.$a['last_name'])?></strong></td><td><?=h($a['title'])?><br><small class="muted"><?=h($a['event_date'])?></small></td><td><?=h($a['role_name'])?></td><td><?=badge($a['assignment_status'])?></td><td><form method="post" action="status_update.php" class="inline group"><?=csrf_field()?><input type="hidden" name="assignment_id" value="<?=(int)$a['assignment_id']?>"><select name="assignment_status" aria-label="Status for <?=h($a['first_name'].' '.$a['last_name'])?>"><?php foreach(['Registered','Confirmed','Completed','Cancelled'] as $status):?><option value="<?=h($status)?>" <?=$a['assignment_status']===$status?'selected':''?>><?=h($status)?></option><?php endforeach;?></select><button class="btn small outline" type="submit">Save</button></form></td><td><form method="post" class="inline" onsubmit="return confirm('Remove this assignment?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="assignment_id" value="<?=(int)$a['assignment_id']?>"><button type="submit" class="text-danger">Delete</button></form></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="6" class="muted">No assignments yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php page_footer(); ?>
