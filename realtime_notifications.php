
<?php
require_once 'functions.php';
require_once 'auth.php';
requireLogin();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

function