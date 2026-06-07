<?php

namespace Tests\Integration;

use Tests\TestCase;

class AppIntegrationTest extends TestCase
{
    private \FlatfileStore $store;
    private \Template $template;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = require ROOT_DIR . '/src/config.php';
        // Override paths for testing
        $this->config['data_dir']     = $this->tempDir;
        $this->config['boards_dir']   = $this->tempDir . '/boards';
        $this->config['ip_logs_dir']  = $this->tempDir . '/ip_logs';
        $this->config['tmp_dir']      = $this->tempDir . '/tmp';
        $this->config['template_dir'] = ROOT_DIR . '/templates';
        $this->config['debug']        = true;

        $this->store    = new \FlatfileStore($this->config['data_dir']);
        $this->template = new \Template($this->config['template_dir']);

        // Create test template dir with layout for rendering
        if (!is_dir($this->tempDir . '/templates')) {
            mkdir($this->tempDir . '/templates', 0755, true);
            copy(ROOT_DIR . '/templates/layout.php', $this->tempDir . '/templates/layout.php');
        }

        // Setup test board
        $this->store->writeJson('boards.json', [
            [
                'board_id'    => 'general',
                'name'        => 'General',
                'description' => 'Test board',
                'sort_order'  => 1,
                'max_threads' => 100,
                'created_at'  => time(),
            ],
        ]);
        $this->store->createDirectory('boards/general');
        $this->store->createDirectory('boards/general/threads');
        $this->store->writeJson('boards/general/threads.json', []);
    }

    // ========== Board Display Tests ==========

    public function testBoardIndexReturnsOk(): void
    {
        $controller = new \BoardController($this->store, $this->template, $this->config);
        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assertStringContainsString('General', $output);
        $this->assertStringContainsString('/boards/general', $output);
        $this->assertStringContainsString('Test board', $output);
    }

    public function testShowBoardReturnsThreadList(): void
    {
        $controller = new \BoardController($this->store, $this->template, $this->config);
        ob_start();
        $controller->showBoard('general');
        $output = ob_get_clean();

        $this->assertStringContainsString('General', $output);
        $this->assertStringContainsString('No threads yet', $output);
        $this->assertStringContainsString('<meta http-equiv="refresh" content="30">', $output);
    }

    public function testNonExistentBoardReturns404(): void
    {
        $controller = new \BoardController($this->store, $this->template, $this->config);
        ob_start();
        $controller->showBoard('nonexistent');
        $output = ob_get_clean();

        $this->assertStringContainsString('Board not found', $output);
    }

    // ========== Thread Creation Tests ==========

    public function testCreateThreadAndView(): void
    {
        // Simulate POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'subject'   => 'Test Thread',
            'message'   => 'Hello World!',
            'csrf_token' => 'test',
        ];
        $_SESSION['csrf_token'] = 'test';

        $postCtrl = new \PostController(
            $this->store,
            new \Validator(),
            new \Security(),
            new \IpLogger($this->config['ip_logs_dir']),
            $this->config
        );

        // Capture redirect
        ob_start();
        try {
            $postCtrl->createThread('general');
        } catch (\Throwable $e) {
            // headers already sent in test context is expected
        }
        ob_end_clean();

        // Verify thread was created
        $index = $this->store->readJson('boards/general/threads.json');
        $this->assertCount(1, $index);
        $this->assertSame('Test Thread', $index[0]['subject']);

        $threadId = $index[0]['thread_id'];
        $this->assertTrue($this->store->exists("boards/general/threads/{$threadId}.json"));

        $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
        $this->assertSame('Hello World!', $thread['op']['message']);
        $this->assertSame(0, $thread['reply_count']);
    }

    // ========== Reply Tests ==========

    public function testCreateTopLevelReply(): void
    {
        // First create a thread
        $this->createTestThread('general', 'T1', 'Thread for replies');

        $index = $this->store->readJson('boards/general/threads.json');
        $threadId = $index[0]['thread_id'];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'message'    => 'This is a reply',
            'parent_id'  => '',
            'csrf_token' => 'test',
        ];
        $_SESSION['csrf_token'] = 'test';

        $postCtrl = new \PostController(
            $this->store,
            new \Validator(),
            new \Security(),
            new \IpLogger($this->config['ip_logs_dir']),
            $this->config
        );

        ob_start();
        try { $postCtrl->createReply('general', $threadId); } catch (\Throwable $e) {}
        ob_end_clean();

        $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
        $this->assertCount(1, $thread['replies']);
        $this->assertNull($thread['replies'][0]['parent_id']);
        $this->assertSame('This is a reply', $thread['replies'][0]['message']);
    }

    public function testCreateNestedReply(): void
    {
        $this->createTestThread('general', 'T2', 'Nested test');
        $index = $this->store->readJson('boards/general/threads.json');
        $threadId = $index[0]['thread_id'];

        // Add a top-level reply
        $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
        $parentId = \Helpers::generateId();
        $thread['replies'][] = [
            'post_id'   => $parentId,
            'parent_id' => null,
            'message'   => 'Parent reply',
            'ip'        => '127.0.0.1',
            'timestamp' => time(),
        ];
        $this->store->writeJson("boards/general/threads/{$threadId}.json", $thread);

        // Now reply to that reply
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'message'    => 'Nested reply',
            'parent_id'  => $parentId,
            'csrf_token' => 'test',
        ];
        $_SESSION['csrf_token'] = 'test';

        $postCtrl = new \PostController(
            $this->store,
            new \Validator(),
            new \Security(),
            new \IpLogger($this->config['ip_logs_dir']),
            $this->config
        );

        ob_start();
        try { $postCtrl->createReply('general', $threadId); } catch (\Throwable $e) {}
        ob_end_clean();

        $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
        $this->assertCount(2, $thread['replies']);
        $this->assertSame($parentId, $thread['replies'][1]['parent_id']);
    }

    public function testInvalidParentIdRejected(): void
    {
        $this->createTestThread('general', 'T3', 'Parent test');
        $index = $this->store->readJson('boards/general/threads.json');
        $threadId = $index[0]['thread_id'];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'message'    => 'Bad parent',
            'parent_id'  => '999.999.nonexistent',
            'csrf_token' => 'test',
        ];
        $_SESSION['csrf_token'] = 'test';

        $postCtrl = new \PostController(
            $this->store,
            new \Validator(),
            new \Security(),
            new \IpLogger($this->config['ip_logs_dir']),
            $this->config
        );

        ob_start();
        try { $postCtrl->createReply('general', $threadId); } catch (\Throwable $e) {}
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid parent post', $output);
    }

    // ========== Bump Score Tests ==========

    public function testBumpScoreComputation(): void
    {
        $replies = [
            ['post_id' => 'r1', 'parent_id' => null, 'timestamp' => 100],
            ['post_id' => 'r2', 'parent_id' => 'r1', 'timestamp' => 200],
            ['post_id' => 'r3', 'parent_id' => 'r2', 'timestamp' => 300],
            ['post_id' => 'r4', 'parent_id' => null, 'timestamp' => 150],
            ['post_id' => 'r5', 'parent_id' => 'r4', 'timestamp' => 250],
        ];

        [$score, $recency] = \PostController::computeBumpScore($replies);

        $this->assertSame(3, $score);    // Branch R1 has 3
        $this->assertSame(300, $recency); // R3 timestamp
    }

    public function testBumpScoreEmpty(): void
    {
        [$score, $recency] = \PostController::computeBumpScore([]);
        $this->assertSame(0, $score);
        $this->assertSame(0, $recency);
    }

    // ========== Reply Tree Tests ==========

    public function testBuildReplyTree(): void
    {
        $replies = [
            ['post_id' => 'r1', 'parent_id' => null, 'message' => 'first', 'timestamp' => 100],
            ['post_id' => 'r2', 'parent_id' => 'r1', 'message' => 'child', 'timestamp' => 200],
            ['post_id' => 'r3', 'parent_id' => null, 'message' => 'second', 'timestamp' => 150],
        ];

        $tree = \BoardController::buildReplyTree($replies);

        $this->assertCount(2, $tree);
        $this->assertSame('first', $tree[0]['message']);
        $this->assertSame(0, $tree[0]['depth']);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertSame('child', $tree[0]['children'][0]['message']);
        $this->assertSame(1, $tree[0]['children'][0]['depth']);
    }

    // ========== Admin Auth Tests ==========

    public function testAdminSetup(): void
    {
        // Ensure no admin.json exists
        $this->assertFalse($this->store->exists('admin.json'));

        $authCtrl = new \AuthController($this->store, new \Security(), $this->config);

        // Setup form renders
        ob_start();
        $authCtrl->setupForm();
        $output = ob_get_clean();
        $this->assertStringContainsString('Initial Setup', $output);

        // Do setup
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'username'         => 'admin',
            'password'         => 'admin123',
            'password_confirm' => 'admin123',
        ];

        ob_start();
        try { $authCtrl->setup(); } catch (\Throwable $e) {}
        ob_end_clean();

        $this->assertTrue($this->store->exists('admin.json'));
        $admin = $this->store->readJson('admin.json');
        $this->assertSame('admin', $admin['username']);
        $this->assertTrue(password_verify('admin123', $admin['password_hash']));

        // Setup should now be disabled
        ob_start();
        $authCtrl->setupForm();
        $output = ob_get_clean();
        $this->assertStringContainsString('already completed', $output);
    }

    public function testAdminLoginAndLogout(): void
    {
        // Setup admin
        $authCtrl = new \AuthController($this->store, new \Security(), $this->config);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['username' => 'admin', 'password' => 'test1234', 'password_confirm' => 'test1234'];
        ob_start(); try { $authCtrl->setup(); } catch (\Throwable $e) {} ob_end_clean();

        // Login
        $_POST = ['username' => 'admin', 'password' => 'test1234'];
        ob_start();
        try { $authCtrl->login(); } catch (\Throwable $e) {}
        ob_end_clean();

        $this->assertTrue($_SESSION['admin_authenticated'] ?? false);

        // Logout
        ob_start();
        try { $authCtrl->logout(); } catch (\Throwable $e) {}
        ob_end_clean();
        $this->assertEmpty($_SESSION);
    }

    // ========== Admin Board CRUD Tests ==========

    public function testAdminCreateBoard(): void
    {
        $this->setupAdminSession();

        $adminCtrl = new \AdminController(
            $this->store, new \Validator(), new \Security(),
            $this->template, $this->config
        );

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'board_id'    => 'tech',
            'name'        => 'Technology',
            'description' => 'Tech talk',
            'csrf_token'  => $_SESSION['csrf_token'],
        ];

        ob_start();
        try { $adminCtrl->createBoard(); } catch (\Throwable $e) {}
        ob_end_clean();

        $boards = $this->store->readJson('boards.json');
        $this->assertCount(2, $boards);
        $this->assertTrue($this->store->exists('boards/tech/threads.json'));
    }

    // ========== Cascading Delete Test ==========

    public function testCascadingDeleteRemovesDescendants(): void
    {
        $this->setupAdminSession();

        // Create thread with nested replies
        $this->createTestThread('general', 'CD', 'Cascade test');
        $index = $this->store->readJson('boards/general/threads.json');
        $threadId = $index[0]['thread_id'];

        $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
        $thread['replies'] = [
            ['post_id' => 'r1', 'parent_id' => null, 'message' => 'keep', 'ip' => '::1', 'timestamp' => 100],
            ['post_id' => 'r2', 'parent_id' => 'r1', 'message' => 'delete-me', 'ip' => '::1', 'timestamp' => 200],
            ['post_id' => 'r3', 'parent_id' => 'r2', 'message' => 'child1', 'ip' => '::1', 'timestamp' => 300],
            ['post_id' => 'r4', 'parent_id' => 'r2', 'message' => 'child2', 'ip' => '::1', 'timestamp' => 400],
            ['post_id' => 'r5', 'parent_id' => null, 'message' => 'keep2', 'ip' => '::1', 'timestamp' => 500],
        ];
        $thread['reply_count'] = 5;
        $this->store->writeJson("boards/general/threads/{$threadId}.json", $thread);

        // Delete r2 (cascading should delete r2, r3, r4)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['confirm' => '1', 'csrf_token' => $_SESSION['csrf_token']];

        $adminCtrl = new \AdminController(
            $this->store, new \Validator(), new \Security(),
            $this->template, $this->config
        );

        ob_start();
        try { $adminCtrl->deleteReply('general', $threadId, 'r2'); } catch (\Throwable $e) {}
        ob_end_clean();

        $thread = $this->store->readJson("boards/general/threads/{$threadId}.json");
        $this->assertCount(2, $thread['replies']); // r1 and r5 remain
        $remainingIds = array_column($thread['replies'], 'post_id');
        $this->assertContains('r1', $remainingIds);
        $this->assertContains('r5', $remainingIds);
        $this->assertNotContains('r2', $remainingIds);
        $this->assertNotContains('r3', $remainingIds);
        $this->assertNotContains('r4', $remainingIds);
    }

    // ========== CSRF Protection Tests ==========

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $this->createTestThread('general', 'CSRF', 'CSRF test');
        $index = $this->store->readJson('boards/general/threads.json');
        $threadId = $index[0]['thread_id'];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'message'    => 'No CSRF',
            'parent_id'  => '',
            'csrf_token' => 'invalid',
        ];
        $_SESSION['csrf_token'] = 'valid_token';

        $postCtrl = new \PostController(
            $this->store,
            new \Validator(),
            new \Security(),
            new \IpLogger($this->config['ip_logs_dir']),
            $this->config
        );

        ob_start();
        try { $postCtrl->createReply('general', $threadId); } catch (\Throwable $e) {}
        $output = ob_get_clean();

        $this->assertStringContainsString('Invalid security token', $output);
    }

    // ========== Helpers ==========

    private function createTestThread(string $boardId, string $subject, string $message): void
    {
        $threadId = \Helpers::generateId();
        $timestamp = time();
        $ip = '127.0.0.1';

        $thread = [
            'thread_id'     => $threadId,
            'board_id'      => $boardId,
            'subject'       => $subject,
            'created_at'    => $timestamp,
            'last_modified' => $timestamp,
            'reply_count'   => 0,
            'bump_score'    => 0,
            'bump_recency'  => $timestamp,
            'op' => [
                'post_id'   => $threadId,
                'message'   => $message,
                'ip'        => $ip,
                'timestamp' => $timestamp,
            ],
            'replies' => [],
        ];

        $this->store->writeJson("boards/{$boardId}/threads/{$threadId}.json", $thread);

        $index = $this->store->readJson("boards/{$boardId}/threads.json");
        $index[] = [
            'thread_id'       => $threadId,
            'subject'         => $subject,
            'message_excerpt' => \Helpers::excerpt($message),
            'poster_ip_hash'  => 'sha256:' . hash('sha256', $ip),
            'created_at'      => $timestamp,
            'last_modified'   => $timestamp,
            'reply_count'     => 0,
            'bump_score'      => 0,
            'bump_recency'    => $timestamp,
        ];
        $this->store->writeJson("boards/{$boardId}/threads.json", $index);
    }

    private function setupAdminSession(): void
    {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['csrf_token'] = \Security::generateCsrfToken();
    }
}
