<?php

/**
 * AuthController — handles admin authentication.
 *
 * SDD Reference: Section 3.7
 */
class AuthController
{
    private FlatfileStore $store;
    private Security $security;
    private array $config;

    public function __construct(FlatfileStore $store, Security $security, array $config)
    {
        $this->store    = $store;
        $this->security = $security;
        $this->config   = $config;
    }

    /**
     * GET /admin/login
     */
    public function loginForm(): void
    {
        $template = new Template(ROOT_DIR . '/templates');
        $error = $_GET['error'] ?? '';
        $expired = $_GET['expired'] ?? '';

        echo $template->render('admin/login', [
            'pageTitle' => 'Admin Login',
            'error'     => $expired ? 'Session expired. Please log in again.' : ($error ? 'Invalid username or password.' : ''),
            'layout'    => 'layout',
        ]);
    }

    /**
     * POST /admin/login
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $adminPath = 'admin.json';
        if (!$this->store->exists($adminPath)) {
            header('Location: /setup', true, 302);
            exit;
        }

        $admin = $this->store->readJson($adminPath);
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $validUsername = hash_equals($admin['username'] ?? '', $username);
        $validPassword = Security::verifyPassword($password, $admin['password_hash'] ?? '');

        if ($validUsername && $validPassword) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_login_time'] = time();
            $_SESSION['csrf_token'] = Security::generateCsrfToken();

            // Audit log success
            $this->auditLog($username, 'success');

            header('Location: /admin', true, 303);
            exit;
        }

        // Audit log failure
        $this->auditLog($username, 'failure');

        header('Location: /admin/login?error=1', true, 302);
        exit;
    }

    /**
     * GET /admin/logout
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();

        header('Location: /', true, 302);
        exit;
    }

    /**
     * GET /setup
     */
    public function setupForm(): void
    {
        if ($this->store->exists('admin.json')) {
            http_response_code(404);
            echo 'Setup already completed.';
            return;
        }

        $template = new Template(ROOT_DIR . '/templates');
        echo $template->render('admin/setup', [
            'pageTitle' => 'Initial Setup',
            'layout'    => 'layout',
        ]);
    }

    /**
     * POST /setup
     */
    public function setup(): void
    {
        if ($this->store->exists('admin.json')) {
            http_response_code(400);
            echo 'Setup already completed.';
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($username)) {
            http_response_code(400);
            echo 'Username is required.';
            return;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo 'Password must be at least 8 characters.';
            return;
        }

        if ($password !== $passwordConfirm) {
            http_response_code(400);
            echo 'Passwords do not match.';
            return;
        }

        $passwordHash = Security::hashPassword($password);

        $this->store->writeJson('admin.json', [
            'username'            => $username,
            'password_hash'       => $passwordHash,
            'created_at'          => time(),
            'last_password_change' => time(),
        ]);

        header('Location: /admin/login', true, 303);
        exit;
    }

    /**
     * GET /admin/password
     */
    public function passwordChangeForm(): void
    {
        Security::requireAdminSession();

        $template = new Template(ROOT_DIR . '/templates');
        echo $template->render('admin/password_change', [
            'pageTitle' => 'Change Password',
            'layout'    => 'layout',
        ]);
    }

    /**
     * POST /admin/password
     */
    public function passwordChange(): void
    {
        Security::requireAdminSession();
        $this->validateCsrf();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo 'Password must be at least 8 characters.';
            return;
        }

        if ($newPassword !== $newPasswordConfirm) {
            http_response_code(400);
            echo 'Passwords do not match.';
            return;
        }

        $admin = $this->store->readJson('admin.json');

        if (!Security::verifyPassword($currentPassword, $admin['password_hash'] ?? '')) {
            http_response_code(400);
            echo 'Current password is incorrect.';
            return;
        }

        $admin['password_hash'] = Security::hashPassword($newPassword);
        $admin['last_password_change'] = time();

        $this->store->writeJson('admin.json', $admin);

        header('Location: /admin', true, 303);
        exit;
    }

    private function auditLog(string $username, string $result): void
    {
        $auditPath = $this->config['app_log_dir'] . '/../admin_audit.log';
        $entry = '[' . date('c') . '] ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ' ' . $username . ' login ' . $result . "\n";
        @file_put_contents($auditPath, $entry, FILE_APPEND | LOCK_EX);
    }

    private function validateCsrf(): void
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Invalid security token.';
            exit;
        }
    }
}
