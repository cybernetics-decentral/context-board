<?php

/**
 * PostController — handles thread creation and reply submission.
 *
 * SDD Reference: Section 3.5
 */
class PostController
{
    private FlatfileStore $store;
    private Validator $validator;
    private Security $security;
    private IpLogger $ipLogger;
    private array $config;

    public function __construct(
        FlatfileStore $store,
        Validator $validator,
        Security $security,
        IpLogger $ipLogger,
        array $config
    ) {
        $this->store     = $store;
        $this->validator = $validator;
        $this->security  = $security;
        $this->ipLogger  = $ipLogger;
        $this->config    = $config;
    }

    /**
     * POST /boards/{board_id}/new — create a new thread.
     */
    public function createThread(string $boardId): void
    {
        $this->requirePost();

        // Validate board exists
        $boards = $this->store->readJson('boards.json');
        $board = null;
        foreach ($boards as $b) {
            if ($b['board_id'] === $boardId) {
                $board = $b;
                break;
            }
        }
        if ($board === null) {
            http_response_code(400);
            echo 'Board not found.';
            return;
        }

        // Validate CSRF token
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Invalid security token.';
            return;
        }

        // Rate limit
        $ip = $this->getClientIp();
        if ($this->isRateLimited($ip)) {
            http_response_code(429);
            echo 'You are posting too quickly. Please wait before posting again.';
            return;
        }

        // Sanitize inputs
        $message = Validator::sanitizeMessage($_POST['message'] ?? '');
        $subject = Validator::sanitizeSubject($_POST['subject'] ?? '');

        $error = Validator::validateMessageLength($message);
        if ($error !== null) {
            http_response_code(400);
            echo $error;
            return;
        }

        if (empty($subject)) {
            $subject = 'No Subject';
        }

        // Generate IDs
        $threadId = Helpers::generateId();
        $postId = $threadId;
        $timestamp = time();

        // Build thread object
        $thread = [
            'thread_id'      => $threadId,
            'board_id'       => $boardId,
            'subject'        => $subject,
            'created_at'     => $timestamp,
            'last_modified'  => $timestamp,
            'reply_count'    => 0,
            'bump_score'     => 0,
            'bump_recency'   => $timestamp,
            'op' => [
                'post_id'   => $postId,
                'message'   => $message,
                'ip'        => $ip,
                'timestamp' => $timestamp,
            ],
            'replies' => [],
        ];

        // Write thread file
        $threadsDir = "boards/{$boardId}/threads";
        if (!$this->store->exists($threadsDir)) {
            $this->store->createDirectory($threadsDir);
        }
        $this->store->writeJson("boards/{$boardId}/threads/{$threadId}.json", $thread);

        // Update thread index
        $indexPath = "boards/{$boardId}/threads.json";
        $index = $this->store->readJson($indexPath);
        $index[] = [
            'thread_id'       => $threadId,
            'subject'         => $subject,
            'message_excerpt' => Helpers::excerpt($message, $this->config['message_excerpt_length']),
            'poster_ip_hash'  => 'sha256:' . hash('sha256', $ip),
            'created_at'      => $timestamp,
            'last_modified'   => $timestamp,
            'reply_count'     => 0,
            'bump_score'      => 0,
            'bump_recency'    => $timestamp,
        ];

        // Auto-delete old threads if over max_threads
        $maxThreads = $board['max_threads'] ?? $this->config['default_max_threads'];
        if ($maxThreads > 0 && count($index) > $maxThreads) {
            $this->pruneThreads($index, $boardId, $maxThreads);
        }

        $this->store->writeJson($indexPath, $index);

        // Log IP
        $this->recordRateLimit($ip);
        $this->ipLogger->log($boardId, $threadId, $postId, $ip, 'new_thread');

        // Redirect to thread view
        header('Location: /boards/' . urlencode($boardId) . '/thread/' . urlencode($threadId), true, 303);
        exit;
    }

    /**
     * GET /boards/{board_id}/thread/{thread_id}/reply — render reply form.
     */
    public function replyForm(string $boardId, string $threadId): void
    {
        $parentId = $_GET['parent_id'] ?? '';

        $template = new Template(ROOT_DIR . '/templates');
        echo $template->render('reply_form', [
            'pageTitle'    => 'Post a Reply',
            'boardId'      => $boardId,
            'threadId'     => $threadId,
            'parentId'     => $parentId,
            'breadcrumbs'  => '<a href="/">Home</a> &raquo; <a href="/boards/' . urlencode($boardId) . '">' . self::escapeHtml($boardId) . '</a> &raquo; <a href="/boards/' . urlencode($boardId) . '/thread/' . urlencode($threadId) . '">Thread</a> &raquo; Reply',
            'layout'       => 'layout',
        ]);
    }

    /**
     * POST /boards/{board_id}/thread/{thread_id}/reply — create a reply.
     */
    public function createReply(string $boardId, string $threadId): void
    {
        $this->requirePost();

        $threadPath = "boards/{$boardId}/threads/{$threadId}.json";
        if (!$this->store->exists($threadPath)) {
            http_response_code(400);
            echo 'Thread not found.';
            return;
        }

        // Validate CSRF token
        if (!$this->security->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Invalid security token.';
            return;
        }

        // Rate limit
        $ip = $this->getClientIp();
        if ($this->isRateLimited($ip)) {
            http_response_code(429);
            echo 'You are posting too quickly. Please wait before posting again.';
            return;
        }

        // Sanitize inputs
        $message = Validator::sanitizeMessage($_POST['message'] ?? '');
        $parentId = $_POST['parent_id'] ?? null;

        $error = Validator::validateMessageLength($message);
        if ($error !== null) {
            http_response_code(400);
            echo $error;
            return;
        }

        // Read thread
        $thread = $this->store->readJson($threadPath);

        // Validate parent_id if provided
        if (!empty($parentId)) {
            $validParent = false;
            if (($thread['op']['post_id'] ?? '') === $parentId) {
                $validParent = true;
            } else {
                foreach ($thread['replies'] ?? [] as $reply) {
                    if (($reply['post_id'] ?? '') === $parentId) {
                        $validParent = true;
                        break;
                    }
                }
            }
            if (!$validParent) {
                http_response_code(400);
                echo 'Invalid parent post.';
                return;
            }
        } else {
            $parentId = null;
        }

        // Check file size
        $rawSize = strlen(json_encode($thread));
        if ($rawSize > $this->config['max_thread_file_size']) {
            http_response_code(413);
            echo 'This thread has reached the maximum number of replies.';
            return;
        }

        // Generate reply
        $postId = Helpers::generateId();
        $timestamp = time();
        $reply = [
            'post_id'   => $postId,
            'parent_id' => $parentId,
            'message'   => $message,
            'ip'        => $ip,
            'timestamp' => $timestamp,
        ];

        $thread['replies'][] = $reply;
        $thread['reply_count'] = count($thread['replies']);
        $thread['last_modified'] = $timestamp;

        // Recompute bump score
        [$bumpScore, $bumpRecency] = self::computeBumpScore($thread['replies']);
        $thread['bump_score'] = $bumpScore;
        $thread['bump_recency'] = $bumpRecency ?: $timestamp;

        // Write thread file
        $this->store->writeJson($threadPath, $thread);

        // Update thread index
        $indexPath = "boards/{$boardId}/threads.json";
        $index = $this->store->readJson($indexPath);
        foreach ($index as &$entry) {
            if ($entry['thread_id'] === $threadId) {
                $entry['last_modified'] = $timestamp;
                $entry['reply_count']   = $thread['reply_count'];
                $entry['bump_score']    = $bumpScore;
                $entry['bump_recency']  = $bumpRecency ?: $timestamp;
                break;
            }
        }
        unset($entry);
        $this->store->writeJson($indexPath, $index);

        // Log
        $this->recordRateLimit($ip);
        $this->ipLogger->log($boardId, $threadId, $postId, $ip, 'reply');

        header('Location: /boards/' . urlencode($boardId) . '/thread/' . urlencode($threadId) . '#post-' . urlencode($postId), true, 303);
        exit;
    }

    /**
     * Compute bump_score and bump_recency from replies array.
     */
    public static function computeBumpScore(array $replies): array
    {
        if (empty($replies)) {
            return [0, 0];
        }

        $children = [];
        foreach ($replies as $reply) {
            $parentKey = $reply['parent_id'] ?? 'null';
            if ($parentKey === null || $parentKey === '') {
                $parentKey = 'null';
            }
            $children[$parentKey][] = $reply;
        }

        $topLevel = $children['null'] ?? [];
        if (empty($topLevel)) {
            return [0, 0];
        }

        $bestScore = 0;
        $bestRecency = 0;

        foreach ($topLevel as $topReply) {
            [$branchSize, $branchRecency] = self::computeBranchStats($topReply, $children);
            if ($branchSize > $bestScore) {
                $bestScore = $branchSize;
                $bestRecency = $branchRecency;
            } elseif ($branchSize === $bestScore) {
                $bestRecency = max($bestRecency, $branchRecency);
            }
        }

        return [$bestScore, $bestRecency];
    }

    private static function computeBranchStats(array $reply, array &$children): array
    {
        $count = 1;
        $maxTimestamp = $reply['timestamp'] ?? 0;
        $childReplies = $children[$reply['post_id'] ?? ''] ?? [];

        foreach ($childReplies as $child) {
            [$childCount, $childTime] = self::computeBranchStats($child, $children);
            $count += $childCount;
            $maxTimestamp = max($maxTimestamp, $childTime);
        }

        return [$count, $maxTimestamp];
    }

    /**
     * Prune threads exceeding max_threads.
     */
    private function pruneThreads(array &$index, string $boardId, int $maxThreads): void
    {
        while (count($index) > $maxThreads) {
            // Find thread with lowest bump_score, then oldest bump_recency
            $worstIdx = 0;
            $worstScore = $index[0]['bump_score'] ?? 0;
            $worstRecency = $index[0]['bump_recency'] ?? PHP_INT_MAX;

            for ($i = 1; $i < count($index); $i++) {
                $score = $index[$i]['bump_score'] ?? 0;
                $recency = $index[$i]['bump_recency'] ?? PHP_INT_MAX;

                if ($score < $worstScore || ($score === $worstScore && $recency < $worstRecency)) {
                    $worstIdx = $i;
                    $worstScore = $score;
                    $worstRecency = $recency;
                }
            }

            $threadId = $index[$worstIdx]['thread_id'];
            $this->store->delete("boards/{$boardId}/threads/{$threadId}.json");
            array_splice($index, $worstIdx, 1);
        }
    }

    private function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!Validator::isValidIp($ip)) {
            $ip = '0.0.0.0';
        }
        return $ip;
    }

    private function isRateLimited(string $ip): bool
    {
        $rateFile = $this->config['tmp_dir'] . '/ratelimit_' . md5($ip) . '.json';
        $now = time();
        $window = $this->config['rate_limit_window'];
        $maxPosts = $this->config['rate_limit_max_posts'];

        $timestamps = [];
        if (file_exists($rateFile)) {
            $data = @json_decode(file_get_contents($rateFile), true);
            $timestamps = $data['timestamps'] ?? [];
        }

        // Remove old entries
        $timestamps = array_filter($timestamps, fn($t) => ($now - $t) < $window);
        $timestamps = array_values($timestamps);

        return count($timestamps) >= $maxPosts;
    }

    private function recordRateLimit(string $ip): void
    {
        $rateFile = $this->config['tmp_dir'] . '/ratelimit_' . md5($ip) . '.json';
        $now = time();
        $window = $this->config['rate_limit_window'];

        $timestamps = [];
        if (file_exists($rateFile)) {
            $data = @json_decode(file_get_contents($rateFile), true);
            $timestamps = $data['timestamps'] ?? [];
        }

        $timestamps = array_filter($timestamps, fn($t) => ($now - $t) < $window);
        $timestamps = array_values($timestamps);
        $timestamps[] = $now;

        file_put_contents($rateFile, json_encode(['timestamps' => $timestamps]), LOCK_EX);
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }
    }

    private static function escapeHtml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
