<?php
$hash = password_hash('test', PASSWORD_DEFAULT);
var_dump(password_get_info($hash));
var_dump(password_get_info('plaintext'));
?>
