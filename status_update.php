<?php
require_once __DIR__.'/functions.php';require_login();
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
verify_csrf();$id=filter_var($_POST['assignment_id']??null,FILTER_VALIDATE_INT);$status=(string)($_POST['assignment_status']??'');
if(!$id||!in_array($status,['Registered','Confirmed','Completed','Cancelled'],true)){flash('Invalid status update.','error');redirect('assignments.php');}
$pdo=db();
try{$pdo->beginTransaction();
 $stmt=$pdo->prepare('SELECT a.event_id,a.assignment_status FROM assignments a WHERE a.assignment_id=?');$stmt->execute([$id]);$assignment=$stmt->fetch();
 if(!$assignment)throw new RuntimeException('Assignment not found.');
 $stmt=$pdo->prepare('SELECT capacity FROM events WHERE event_id=? FOR UPDATE');$stmt->execute([$assignment['event_id']]);$capacity=(int)$stmt->fetchColumn();
 $stmt=$pdo->prepare("SELECT COUNT(*) FROM assignments WHERE event_id=? AND assignment_status <> 'Cancelled'");$stmt->execute([$assignment['event_id']]);$active=(int)$stmt->fetchColumn();
 if($assignment['assignment_status']==='Cancelled' && $status!=='Cancelled' && $active>=$capacity)throw new RuntimeException('Cannot reactivate: event is full.');
 $stmt=$pdo->prepare('UPDATE assignments SET assignment_status=? WHERE assignment_id=?');$stmt->execute([$status,$id]);$pdo->commit();flash('Assignment status updated.');
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash($e instanceof RuntimeException?$e->getMessage():'Could not update status.','error');}
redirect('assignments.php');
