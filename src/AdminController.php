<?php

/**
 * AdminController — handles admin operations.
 *
 * SDD Reference: Section 3.6
 */
class AdminController
{
    private FlatfileStore $store;
    private Validator $validator;
    private Security $security;
    private Template $template;
    private array $config;

    public function __construct(
        FlatfileStore $store,
        Validator $validator,
        Security $security,
        Template $template,
        array $config
    ) {
        $this->store     = $store;
        $this->validator = $validator;
        $this->security  = $security;
        $this->template  = $template;
        $this->config    = $config;
    }

    /**
     * GET /admin — dashboard with board stats.
     */
    public function dashboard(): void
    {
        Security::requireAdminSession();

        $boards = $this->store->readJson('boards.json');
        $stats = [];
        foreach ($boards as $board) {
            $threads = $this->store->readJson("boards/{$board['board_id']}/threads.json");
            $threadCount = count($threads);
            $replyCount = 0;
            $lastActivity = 0;
            foreach ($threads as $t) {
                $replyCount += $t['reply_count'] ?? 0;
                $lastActivity = max($lastActivity, $t['last_modified'] ?? 0);
            }
            $stats[] = [
                'board'        => $board,
                'thread_count' => $threadCount,
                'reply_count'  => $replyCount,
                'last_activity' => $lastActivity,
            ];
        }

        echo $this->template->render('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats'     => $stats,
            'layout'    => 'layout',
        ]);
    }

    /**
     * GET /admin/boards — manage boards.
     */
    public function manageBoards(): void
    {
        Security::requireAdminSession();
        $boards = $this->store->readJson('boards.json');

        echo $this->template->render('admin/board_manage', [
            'pageTitle' => 'Manage Boards',
            'boards'    => $boards,
            'layout'    => 'layout',
        ]);
    }

    /**
     * POST /admin/boards/create
     */
    public function createBoard(): void
    {
        Security::requireAdminSession();
        $this->validateCsrf();

        $boardId = trim($_POST['board_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!Validator::isValidBoardId($boardId)) {
            http_response_code(400);
            echo 'Invalid board ID. Use alphanumeric characters, hyphens, and underscores (1-32 chars).';
            return;
        }

        $boards = $this->store->readJson('boards.json');
        foreach ($boards as $b) {
            if ($b['board_id'] === $boardId) {
                http_response_code(400);
                echo 'Board already exists.';
                return;
            }
        }

        $boards[] = [
            'board_id'    => $boardId,
            'name'        => $name ?: $boardId,
            'description' => $description,
            'sort_order'  => count($boards) + 1,
            'max_threads' => $this->config['default_max_threads'],
            'created_at'  => time(),
        ];

        // Create board directory and empty threads.json
        $this->store->createDirectory("boards/{$boardId}");
        $this->store->createDirectory("boards/{$boardId}/threads");
        $this->store->writeJson("boards/{$boardId}/threads.json", []);

        $this->store->writeJson('boards.json', $boards);

        header('Location: /admin/boards', true, 303);
        exit;
    }

    /**
     * POST /admin/boards/{board_id}/rename
     */
    public function renameBoard(string $boardId): void
    {
        Security::requireAdminSession();
        $this->validateCsrf();

        $boards = $this->store->readJson('boards.json');
        foreach ($boards as &$b) {
            if ($b['board_id'] === $boardId) {
                $b['name'] = trim($_POST['name'] ?? $b['name']);
                $b['description'] = trim($_POST['description'] ?? $b['description']);
                break;
            }
        }
        $this->store->writeJson('boards.json', $boards);
        header('Location: /admin/boards', true, 303);
        exit;
    }

    /**
     * POST /admin/boards/{board_id}/delete
     */
    public function deleteBoard(string $boardId): void
    {
        Security::requireAdminSession();
        $this->validateCsrf();

        if (($_POST['confirm'] ?? '0') !== '1') {
            http_response_code(400);
            echo 'You must check the confirmation box.';
            return;
        }

        $this->store->deleteDirectory("boards/{$boardId}");

        $boards = $this->store->readJson('boards.json');
        $boards = array_values(array_filter($boards, fn($b) => $b['board_id'] !== $boardId));
        $this->store->writeJson('boards.json', $boards);

        header('Location: /admin/boards', true, 303);
        exit;
    }

    /**
     * GET /admin/boards/{board_id} — moderate threads in a board.
     */
    public function moderateBoard(string $boardId): void
    {
        Security::requireAdminSession();

        $threads = $this->store->readJson("boards/{$boardId}/threads.json");

        echo $this->template->render('admin/board_moderate', [
            'pageTitle' => 'Moderate: ' . self::escapeHtml($boardId),
            'boardId'   => $boardId,
            'threads'   => $threads,
            'layout'    => 'layout',
        ]);
    }

    /**
     * GET /admin/boards/{board_id}/thread/{thread_id} — moderate a thread.
     */
    public function moderateThread(string $boardId, string $threadId): void
    {
        Security::requireAdminSession();

        $threadPath = "boards/{$boardId}/threads/{$threadId}.json";
        $thread = $this->store->readJson($threadPath);
        $replies = $thread['replies'] ?? [];
        $op = $thread['op'] ?? [];

        $replyTree = BoardController::buildReplyTree($replies);
        $counter = 1;
        $op['post_number'] = $counter++;
        $this->numberReplies($replyTree, $counter);

        echo $this->template->render('admin/thread_moderate', [
            'pageTitle' => 'Moderate Thread',
            'boardId'   => $boardId,
            'threadId'  => $threadId,
            'thread'    => $thread,
            'op'        => $op,
            'replyTree' => $replyTree,
            'layout'    => 'layout',
        ]);
    }

    /**
     * POST /admin/boards/{board_id}/thread/{thread_id}/delete
     */
    public function deleteThread(string $boardId, string $threadId): void
    {
        Security::requireAdminSession();
        $this->validateCsrf();

        if (($_POST['confirm'] ?? '0') !== '1') {
            http_response_code(400);
            echo 'You must check the confirmation box.';
            return;
        }

        $this->store->delete("boards/{$boardId}/threads/{$threadId}.json");

        $indexPath = "boards/{$boardId}/threads.json";
        $index = $this->store->readJson($indexPath);
        $index = array_values(array_filter($index, fn($t) => $t['thread_id'] !== $threadId));
        $this->store->writeJson($indexPath, $index);

        header('Location: /admin/boards/' . urlencode($boardId), true, 303);
        exit;
    }

    /**
     * POST /admin/boards/{board_id}/thread/{thread_id}/reply/{post_id}/delete
     */
    public function deleteReply(string $boardId, string $threadId, string $postId): void
    {
        Security::requireAdminSession();
        $this->validateCsrf();

        if (($_POST['confirm'] ?? '0') !== '1') {
            http_response_code(400);
            echo 'You must check the confirmation box.';
            return;
        }

        $threadPath = "boards/{$boardId}/threads/{$threadId}.json";
        $thread = $this->store->readJson($threadPath);
        $replies = $thread['replies'] ?? [];

        // Cascading delete: collect all descendant IDs
        $idsToDelete = $this->collectDescendants($replies, $postId);

        if (empty($idsToDelete)) {
            http_response_code(400);
            echo 'Reply not found.';
            return;
        }

        $deleteSet = array_flip($idsToDelete);
        $remaining = array_values(array_filter($replies, fn($r) => !isset($deleteSet[$r['post_id']])));

        $thread['replies'] = $remaining;
        $thread['reply_count'] = count($remaining);

        // Recompute bump score
        [$bumpScore, $bumpRecency] = PostController::computeBumpScore($remaining);
        $thread['bump_score'] = $bumpScore;
        $thread['bump_recency'] = $bumpRecency ?: ($thread['op']['timestamp'] ?? time());

        $this->store->writeJson($threadPath, $thread);

        // Update index
        $indexPath = "boards/{$boardId}/threads.json";
        $index = $this->store->readJson($indexPath);
        foreach ($index as &$entry) {
            if ($entry['thread_id'] === $threadId) {
                $entry['reply_count']  = $thread['reply_count'];
                $entry['bump_score']   = $bumpScore;
                $entry['bump_recency'] = $bumpRecency ?: $entry['created_at'];
                break;
            }
        }
        unset($entry);
        $this->store->writeJson($indexPath, $index);

        header('Location: /admin/boards/' . urlencode($boardId) . '/thread/' . urlencode($threadId), true, 303);
        exit;
    }

    /**
     * Collect a target post_id and all its descendants using BFS.
     */
    private function collectDescendants(array $replies, string $targetPostId): array
    {
        // Build parent→children mapping
        $children = [];
        foreach ($replies as $reply) {
            $parentKey = $reply['parent_id'] ?? 'null';
            if ($parentKey === null || $parentKey === '') {
                $parentKey = 'null';
            }
            $children[$parentKey][] = $reply['post_id'];
        }

        // Check if target exists
        $exists = false;
        foreach ($replies as $r) {
            if ($r['post_id'] === $targetPostId) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            return [];
        }

        $ids = [$targetPostId];
        $queue = [$targetPostId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            foreach ($children[$current] ?? [] as $childId) {
                $ids[] = $childId;
                $queue[] = $childId;
            }
        }

        return $ids;
    }

    private function numberReplies(array &$tree, int &$counter): void
    {
        foreach ($tree as &$reply) {
            $reply['post_number'] = $counter++;
            if (!empty($reply['children'])) {
                $this->numberReplies($reply['children'], $counter);
            }
        }
    }

    private function validateCsrf(): void
    {
        if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Invalid security token.';
            exit;
        }
    }

    private static function escapeHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
