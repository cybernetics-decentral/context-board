<?php

/**
 * Front Controller — single entry point for all HTTP requests.
 */

define('ROOT_DIR', dirname(__DIR__));

// Load configuration
$config = require ROOT_DIR . '/src/config.php';

// Set error reporting
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set($config['timezone']);

// Start session for admin routes
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($requestUri, '/admin') || str_starts_with($requestUri, '/setup')) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Autoload all source files
require_once ROOT_DIR . '/src/config.php';
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

// Instantiate core services
$store     = new FlatfileStore($config['data_dir']);
$validator = new Validator();
$security  = new Security();
$ipLogger  = new IpLogger($config['ip_logs_dir']);
$template  = new Template($config['template_dir']);

// Instantiate controllers
$boardCtrl = new BoardController($store, $template, $config);
$postCtrl  = new PostController($store, $validator, $security, $ipLogger, $config);
$adminCtrl = new AdminController($store, $validator, $security, $template, $config);
$authCtrl  = new AuthController($store, $security, $config);

// Instantiate router and register routes
$router = new Router();

// Public routes
$router->addRoute('GET', '/', $boardCtrl, 'index');
$router->addRoute('GET', '/boards/{board_id}', $boardCtrl, 'showBoard');
$router->addRoute('GET', '/boards/{board_id}/new', $boardCtrl, 'newThreadForm');
$router->addRoute('POST', '/boards/{board_id}/new', $postCtrl, 'createThread');
$router->addRoute('GET', '/boards/{board_id}/thread/{thread_id}', $boardCtrl, 'showThread');
$router->addRoute('GET', '/boards/{board_id}/thread/{thread_id}/reply', $postCtrl, 'replyForm');
$router->addRoute('POST', '/boards/{board_id}/thread/{thread_id}/reply', $postCtrl, 'createReply');

// Auth routes
$router->addRoute('GET', '/admin/login', $authCtrl, 'loginForm');
$router->addRoute('POST', '/admin/login', $authCtrl, 'login');
$router->addRoute('GET', '/admin/logout', $authCtrl, 'logout');
$router->addRoute('GET', '/setup', $authCtrl, 'setupForm');
$router->addRoute('POST', '/setup', $authCtrl, 'setup');

// Admin routes (require auth)
$router->addRoute('GET', '/admin', $adminCtrl, 'dashboard');
$router->addRoute('GET', '/admin/boards', $adminCtrl, 'manageBoards');
$router->addRoute('POST', '/admin/boards/create', $adminCtrl, 'createBoard');
$router->addRoute('POST', '/admin/boards/{board_id}/rename', $adminCtrl, 'renameBoard');
$router->addRoute('POST', '/admin/boards/{board_id}/delete', $adminCtrl, 'deleteBoard');
$router->addRoute('GET', '/admin/boards/{board_id}', $adminCtrl, 'moderateBoard');
$router->addRoute('GET', '/admin/boards/{board_id}/thread/{thread_id}', $adminCtrl, 'moderateThread');
$router->addRoute('POST', '/admin/boards/{board_id}/thread/{thread_id}/delete', $adminCtrl, 'deleteThread');
$router->addRoute('POST', '/admin/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete', $adminCtrl, 'deleteReply');
$router->addRoute('GET', '/admin/password', $authCtrl, 'passwordChangeForm');
$router->addRoute('POST', '/admin/password', $authCtrl, 'passwordChange');

// Dispatch
try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $requestUri);
} catch (\Throwable $e) {
    if ($config['debug']) {
        http_response_code(500);
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . "\n\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        if (file_exists(ROOT_DIR . '/templates/errors/500.php')) {
            include ROOT_DIR . '/templates/errors/500.php';
        } else {
            echo '<h1>Internal Server Error</h1><p>An internal error occurred. Please try again later.</p>';
        }
    }
    error_log('[' . date('c') . '] [ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
