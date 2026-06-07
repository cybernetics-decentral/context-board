<?php

/**
 * BoardController — handles read-only display operations.
 *
 * SDD Reference: Section 3.4
 */
class BoardController
{
    private FlatfileStore $store;
    private Template $template;
    private array $config;

    public function __construct(FlatfileStore $store, Template $template, array $config)
    {
        $this->store    = $store;
        $this->template = $template;
        $this->config   = $config;
    }

    /**
     * GET / — list all sub-boards.
     */
    public function index(): void
    {
        $boards = $this->store->readJson('boards.json');
        usort($boards, fn($a, $b) => ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0));

        echo $this->template->render('board_index', [
            'pageTitle' => 'Context Board',
            'boards'    => $boards,
            'layout'    => 'layout',
        ]);
    }

    /**
     * GET /boards/{board_id} — list threads in a board.
     */
    public function showBoard(string $boardId): void
    {
        $boards = $this->store->readJson('boards.json');
        $board = null;
        foreach ($boards as $b) {
            if ($b['board_id'] === $boardId) {
                $board = $b;
                break;
            }
        }
        if ($board === null) {
            http_response_code(404);
            echo $this->template->render('errors/404', [
                'pageTitle' => 'Not Found',
                'message'   => 'Board not found.',
                'layout'    => 'layout',
            ]);
            return;
        }

        $threadsPath = "boards/{$boardId}/threads.json";
        $threads = $this->store->readJson($threadsPath);

        // Sort by bump_score DESC, then bump_recency DESC
        usort($threads, function ($a, $b) {
            $scoreCmp = ($b['bump_score'] ?? 0) <=> ($a['bump_score'] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }
            return ($b['bump_recency'] ?? 0) <=> ($a['bump_recency'] ?? 0);
        });

        // Pagination
        $perPage = $this->config['threads_per_page'];
        $totalThreads = count($threads);
        $totalPages = max(1, (int)ceil($totalThreads / $perPage));
        $page = (int)($_GET['page'] ?? 1);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        $pageThreads = array_slice($threads, $offset, $perPage);

        echo $this->template->render('thread_list', [
            'pageTitle'     => $board['name'],
            'board'         => $board,
            'threads'       => $pageThreads,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalThreads'  => $totalThreads,
            'autoRefresh'   => true,
            'refreshSeconds'=> $this->config['auto_refresh_seconds'],
            'breadcrumbs'   => '<a href="/">Home</a> &raquo; ' . self::escapeHtml($board['name']),
            'layout'        => 'layout',
        ]);
    }

    /**
     * GET /boards/{board_id}/new — render new thread form.
     */
    public function newThreadForm(string $boardId): void
    {
        $boards = $this->store->readJson('boards.json');
        $board = null;
        foreach ($boards as $b) {
            if ($b['board_id'] === $boardId) {
                $board = $b;
                break;
            }
        }
        if ($board === null) {
            http_response_code(404);
            echo $this->template->render('errors/404', [
                'pageTitle' => 'Not Found',
                'message'   => 'Board not found.',
                'layout'    => 'layout',
            ]);
            return;
        }

        echo $this->template->render('new_thread_form', [
            'pageTitle'   => 'New Thread — ' . self::escapeHtml($board['name']),
            'board'       => $board,
            'board_id'    => $boardId,
            'breadcrumbs' => '<a href="/">Home</a> &raquo; <a href="/boards/' . urlencode($boardId) . '">' . self::escapeHtml($board['name']) . '</a> &raquo; New Thread',
            'layout'      => 'layout',
        ]);
    }

    /**
     * GET /boards/{board_id}/thread/{thread_id} — display a thread with nested replies.
     */
    public function showThread(string $boardId, string $threadId): void
    {
        $threadPath = "boards/{$boardId}/threads/{$threadId}.json";
        if (!$this->store->exists($threadPath)) {
            http_response_code(404);
            echo $this->template->render('errors/404', [
                'pageTitle' => 'Not Found',
                'message'   => 'Thread not found.',
                'layout'    => 'layout',
            ]);
            return;
        }

        $thread = $this->store->readJson($threadPath);
        $replies = $thread['replies'] ?? [];
        $op = $thread['op'] ?? [];

        // Build reply tree
        $replyTree = $this->buildReplyTree($replies);

        // Assign post numbers (depth-first)
        $counter = 1;
        $op['post_number'] = $counter++;
        $this->numberReplies($replyTree, $counter);

        echo $this->template->render('thread_view', [
            'pageTitle'     => ($thread['subject'] ?: 'No Subject'),
            'boardId'       => $boardId,
            'threadId'      => $threadId,
            'thread'        => $thread,
            'op'            => $op,
            'replyTree'     => $replyTree,
            'autoRefresh'   => true,
            'refreshSeconds'=> $this->config['auto_refresh_seconds'],
            'breadcrumbs'   => '<a href="/">Home</a> &raquo; <a href="/boards/' . urlencode($boardId) . '">' . self::escapeHtml($boardId) . '</a> &raquo; ' . self::escapeHtml($thread['subject'] ?: 'Thread'),
            'layout'        => 'layout',
        ]);
    }

    /**
     * Build a nested reply tree from a flat array.
     */
    public static function buildReplyTree(array $replies): array
    {
        $byParent = [];
        foreach ($replies as $reply) {
            $parentKey = $reply['parent_id'] ?? 'null';
            if ($parentKey === null || $parentKey === '') {
                $parentKey = 'null';
            }
            $byParent[$parentKey][] = $reply;
        }

        // Sort each group chronologically
        foreach ($byParent as &$group) {
            usort($group, fn($a, $b) => ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0));
        }

        return self::buildLevel($byParent, 'null', 0);
    }

    private static function buildLevel(array &$byParent, string $parentKey, int $depth): array
    {
        $nodes = [];
        foreach ($byParent[$parentKey] ?? [] as $reply) {
            $reply['depth'] = $depth;
            $reply['children'] = self::buildLevel($byParent, $reply['post_id'] ?? '', $depth + 1);
            $nodes[] = $reply;
        }
        return $nodes;
    }

    /**
     * Assign sequential post numbers via depth-first traversal.
     */
    private function numberReplies(array &$tree, int &$counter): void
    {
        foreach ($tree as &$reply) {
            $reply['post_number'] = $counter++;
            if (!empty($reply['children'])) {
                $this->numberReplies($reply['children'], $counter);
            }
        }
    }

    private static function escapeHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
