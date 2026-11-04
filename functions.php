<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
function h(mixed $value): string {return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}
function url(string $page): string {return $page;}
function flash(string $message, string $type = 'success'): void {$_SESSION['flash'] = ['message'=>$message, 'type'=>$type];}
function redirect(string $path): never {header('Location: '.$path, true, 303); exit;}
function csrf_token(): string {return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));}
function csrf_field(): string {return '<input type="hidden" name="csrf" value="'.h(csrf_token()).'">';}
function verify_csrf(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {http_response_code(403); exit('Invalid form token. Reload the page and try again.');}
}
function signed_in(): bool {return isset($_SESSION['admin_id']);}
function require_login(): void {if (!signed_in()) redirect('login.php');}
function validate_date(string $value): bool {$d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $d!==false && $d->format('Y-m-d')===$value;}
function valid_email(string $email): bool {return filter_var($email, FILTER_VALIDATE_EMAIL)!==false;}
function page_header(string $title): void {
    $nav = signed_in();
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' | VolunteerHub</title><link rel="stylesheet" href="assets/style.css"></head><body>';
    echo '<header class="site-header"><div class="container nav"><a href="index.php" class="logo"><span class="logo-icon">V</span> VolunteerHub</a>';
    if ($nav) echo '<nav aria-label="Main navigation"><a href="index.php">Dashboard</a><a href="volunteers.php">Volunteers</a><a href="events.php">Events</a><a href="assignments.php">Assignments</a><form method="post" action="logout.php" class="inline">'.csrf_field().'<button class="nav-logout" type="submit">Log out</button></form></nav>';
    echo '</div></header><main class="container">';
    if ($message) echo '<div class="notice '.($message['type']==='error'?'error':'success').'">'.h($message['message']).'</div>';
}
function page_footer(): void {echo '</main><footer class="footer">CIS 344 &middot; Event Volunteer Management System &middot; Fall 2026</footer></body></html>';}
function error_list(array $errors): void {if($errors){echo '<div class="notice error"><strong>Please fix:</strong><ul>';foreach($errors as $e)echo '<li>'.h($e).'</li>';echo '</ul></div>';}}
function badge(string $value): string {return '<span class="badge badge-'.h(strtolower(str_replace(' ','-',$value))).'">'.h($value).'</span>';}
