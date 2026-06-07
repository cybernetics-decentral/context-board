<?php

/**
 * Test bootstrap — sets up autoloading and test environment.
 */

define('ROOT_DIR', dirname(__DIR__));

// Override config paths for testing before loading config
// We'll use a temporary data directory managed by TestCase

require_once ROOT_DIR . '/src/Helpers.php';
require_once ROOT_DIR . '/src/FlatfileStore.php';
require_once ROOT_DIR . '/src/Validator.php';
require_once ROOT_DIR . '/src/Security.php';
require_once ROOT_DIR . '/src/IpLogger.php';
require_once ROOT_DIR . '/src/Template.php';
require_once ROOT_DIR . '/src/Router.php';
require_once ROOT_DIR . '/src/BoardController.php';
require_once ROOT_DIR . '/src/PostController.php';
require_once ROOT_DIR . '/src/AdminController.php';
require_once ROOT_DIR . '/src/AuthController.php';
