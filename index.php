<?php
require_once __DIR__.'/functions.php';require_login();
$pdo=db();
$counts=[
 'volunteers'=>(int)$pdo->query('SELECT COUNT(*) FROM volunteers')->fetchColumn(),
 'events'=>(int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
 'assignments'=>(int)$pdo->query("SELECT COUNT(*) FROM assignments WHERE assignment_status <> 'Cancelled'")->fetchColumn(),
 'upcoming'=>(int)$pdo->query('SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()')->fetchColumn()
];
// THREE-TABLE JOIN: assignments + volunteers + events for a readable activity feed.
$recent=$pdo->query('SELECT a.assignment_id, a.role_name, a.assignment_status, v.first_name,v.last_name,e.title,e.event_date FROM assignments a INNER JOIN volunteers v ON v.volunteer_id=a.volunteer_id INNER JOIN events e ON e.event_id=a.event_id ORDER BY a.assigned_at DESC,a.assignment_id DESC LIMIT 8')->fetchAll();
// LEFT JOIN also shows events with zero volunteers.
$events=$pdo->query("SELECT e.event_id,e.title,e.event_date,e.location,e.capacity,COUNT(a.assignment_id) AS registered FROM events e LEFT JOIN assignments a ON a.event_id=e.event_id AND a.assignment_status <> 'Cancelled' GROUP BY e.event_id,e.title,e.event_date,e.location,e.capacity ORDER BY e.event_date ASC LIMIT 6")->fetchAll();
page_header('Dashboard');
?>
<div class="page-title"><div><div class="eyebrow">OVERVIEW</div><h1>Volunteer dashboard</h1><p class="muted">Organize community events and the people who make them happen.</p></div><a class="btn primary" href="assignments.php">+ New assignment</a></div>
<div class="metrics"><div class="metric"><span>Volunteers</span><strong><?=$counts['volunteers']?></strong></div><div class="metric"><span>Events</span><strong><?=$counts['events']?></strong></div><div class="metric"><span>Active assignments</span><strong><?=$counts['assignments']?></strong></div><div class="metric"><span>Upcoming events</span><strong><?=$counts['upcoming']?></strong></div></div>
<section class="panel"><div class="section-heading"><h2>Event capacity</h2><a href="events.php">Manage events →</a></div><div class="table-scroll"><table><thead><tr><th>Event</th><th>Date</th><th>Location</th><th>Filled spots</th></tr></thead><tbody><?php foreach($events as $e):?><tr><td><strong><?=h($e['title'])?></strong></td><td><?=h($e['event_date'])?></td><td><?=h($e['location'])?></td><td><?=h($e['registered'])?> / <?=h($e['capacity'])?></td></tr><?php endforeach;?><?php if(!$events):?><tr><td colspan="4" class="muted">No events yet. Add your first event.</td></tr><?php endif;?></tbody></table></div></section>
<section class="panel"><div class="section-heading"><h2>Recent assignments</h2><a href="assignments.php">View all →</a></div><div class="table-scroll"><table><thead><tr><th>Volunteer</th><th>Event</th><th>Role</th><th>Status</th></tr></thead><tbody><?php foreach($recent as $a):?><tr><td><?=h($a['first_name'].' '.$a['last_name'])?></td><td><?=h($a['title'])?></td><td><?=h($a['role_name'])?></td><td><?=badge($a['assignment_status'])?></td></tr><?php endforeach;?><?php if(!$recent):?><tr><td colspan="4" class="muted">No assignments yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php page_footer(); ?>
