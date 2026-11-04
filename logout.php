<?php
require_once __DIR__.'/functions.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
verify_csrf();$_SESSION=[];session_regenerate_id(true);redirect('login.php');
