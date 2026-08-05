<?php
$p = file_get_contents(__DIR__ . '/../app/Mail/WeeklyCommercialRecap.php');
$p = str_replace('use Queueable, SerializesModels;', 'use Queueable, SerializesModels, HasEmailTemplate;', $p);
$p = str_replace("use Illuminate\\Queue\\SerializesModels;", "use Illuminate\\Queue\\SerializesModels;\nuse App\\Mail\\Traits\\HasEmailTemplate;", $p);
file_put_contents(__DIR__ . '/../app/Mail/WeeklyCommercialRecap.php', $p);
echo 'patched1';
