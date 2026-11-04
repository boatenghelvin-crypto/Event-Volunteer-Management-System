<?php
require_once __DIR__.'/functions.php';require_login();$pdo=db();$errors=[];
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT)?:0;$editing=null;
if($editId){$stmt=$pdo->prepare('SELECT * FROM events WHERE event_id=?');$stmt->execute([$editId]);$editing=$stmt->fetch();if(!$editing){flash('Event not found.','error');redirect('events.php');}}
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=(string)($_POST['action']??'save');$id=filter_var($_POST['event_id']??0,FILTER_VALIDATE_INT)?:0;
 if($action==='delete'){$stmt=$pdo->prepare('DELETE FROM events WHERE event_id=?');$stmt->execute([$id]);flash('Event deleted. Related assignments were also removed.');redirect('events.php');}
 $data=['title'=>trim((string)($_POST['title']??'')),'event_date'=>trim((string)($_POST['event_date']??'')),'location'=>trim((string)($_POST['location']??'')),'capacity'=>filter_var($_POST['capacity']??0,FILTER_VALIDATE_INT),'description'=>trim((string)($_POST['description']??''))];
 if($data['title']===''||mb_strlen($data['title'])>120)$errors[]='Enter an event title (max 120 characters).';
 if(!validate_date($data['event_date']))$errors[]='Choose a valid event date.';
 if($data['location']===''||mb_strlen($data['location'])>160)$errors[]='Enter a location (max 160 characters).';
 if($data['capacity']===false||$data['capacity']<1||$data['capacity']>100000)$errors[]='Capacity must be between 1 and 100,000.';
 if($id && !$errors){$stmt=$pdo->prepare("SELECT COUNT(*) FROM assignments WHERE event_id=? AND assignment_status <> 'Cancelled'");$stmt->execute([$id]);if((int)$stmt->fetchColumn()>$data['capacity'])$errors[]='Capacity cannot be below the number of active assignments.';}
 if(!$errors){$vals=array_values($data);
  if($id){$stmt=$pdo->prepare('UPDATE events SET title=?,event_date=?,location=?,capacity=?,description=? WHERE event_id=?');$stmt->execute([...$vals,$id]);}
  else{$stmt=$pdo->prepare('INSERT INTO events(title,event_date,location,capacity,description) VALUES(?,?,?,?,?)');$stmt->execute($vals);}
  flash($id?'Event updated.':'Event added.');redirect('events.php');}
 $editing=['event_id'=>$id]+$data;
}
$rows=$pdo->query("SELECT e.*,COUNT(a.assignment_id) AS registered FROM events e LEFT JOIN assignments a ON a.event_id=e.event_id AND a.assignment_status <> 'Cancelled' GROUP BY e.event_id ORDER BY e.event_date,e.title")->fetchAll();
page_header('Events');
?>
<div class="page-title"><div><div class="eyebrow">CALENDAR</div><h1>Events</h1><p class="muted">Schedule events and set volunteer capacity.</p></div></div>
<section class="panel"><h2><?=$editing?'Edit event':'Add event'?></h2><?php error_list($errors);?><form method="post" class="form-grid"><?=csrf_field()?><input type="hidden" name="event_id" value="<?=h($editing['event_id']??'')?>"><label>Event title<input name="title" required maxlength="120" value="<?=h($editing['title']??'')?>"></label><label>Event date<input type="date" name="event_date" required value="<?=h($editing['event_date']??'')?>"></label><label>Location<input name="location" required maxlength="160" value="<?=h($editing['location']??'')?>"></label><label>Maximum volunteers<input type="number" name="capacity" min="1" max="100000" required value="<?=h($editing['capacity']??'20')?>"></label><label class="full">Description<textarea name="description" rows="3"><?=h($editing['description']??'')?></textarea></label><div class="form-actions"><button class="btn primary" type="submit"><?=$editing?'Save changes':'Add event'?></button><?php if($editing):?><a href="events.php" class="btn outline">Cancel edit</a><?php endif;?></div></form></section>
<section class="panel"><div class="section-heading"><h2>Event list</h2><span class="muted"><?=count($rows)?> total</span></div><div class="table-scroll"><table><thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Volunteer spots</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $e):?><tr><td><strong><?=h($e['title'])?></strong></td><td><?=h($e['event_date'])?></td><td><?=h($e['location'])?></td><td><?=h($e['registered'])?> / <?=h($e['capacity'])?></td><td class="actions"><a href="events.php?edit=<?=(int)$e['event_id']?>">Edit</a><form method="post" class="inline" onsubmit="return confirm('Delete this event and all its assignments?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="event_id" value="<?=(int)$e['event_id']?>"><button type="submit" class="text-danger">Delete</button></form></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="5" class="muted">No events yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php page_footer(); ?>
