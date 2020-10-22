<?php
# /public/api/v1/logout/index.php

const ROOT = '/var/www';
require_once ROOT . '/src/common.php';

// validate user
if (!Common::getLoginStatus())
	Common::return();

// clear session
if (session_status() !== PHP_SESSION_ACTIVE)
	session_start();

session_destroy();

// clear cookies
foreach ($_COOKIE as $name => $value)
	Common::setCookie($name, '');
