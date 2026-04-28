<?php
$c = new mysqli('localhost', 'root', '');
$r = $c->query('SHOW DATABASES');
while($f = $r->fetch_assoc()) echo $f['Database'] . "\n";
