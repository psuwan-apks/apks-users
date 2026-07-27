<?php
require_once __DIR__ . '/app/model/user.php';
$created = User::createUser('testuser2', 'testpass');
var_dump("Created:", $created);
$user = User::findByUsername('testuser2');
var_dump("Found user:", $user);
$auth = User::authenticate('testuser2', 'testpass');
var_dump("Auth:", $auth);
