<?php $layout = 'layout'; ?>

<?php
function renderAdminReplies(array $tree, string $boardId, string $threadId): void {
    foreach ($tree as $reply):
        $depth = min((int)($reply['depth'] ?? 0), 10);
?>
        <div class="post post-reply reply-depth-<?= $depth ?>"
             id="post-<?= htmlspecialchars($reply['post_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="post-meta">
                <span class="post-number">#<?= (int)($reply['post_number'] ?? 0) ?></span>
                <span class="post-time"><?= htmlspecialchars(Helpers::relativeTime($reply['timestamp'] ?? time()), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="post-ip">IP: <?= htmlspecialchars($reply['ip'] ?? '?', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="post-message"><?= htmlspecialchars($reply['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>

            <form method="POST" action="/admin/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($threadId) ?>/reply/<?= urlencode($reply['post_id'] ?? '') ?>/delete">
                <?= Security::getCsrfTokenField() ?>
                <label>
                    <input type="checkbox" name="confirm" value="1"> Confirm delete (cascades to nested replies)
                </label>
                <button type="submit" class="danger">Delete Reply</button>
            </form>
        </div>
        <?php if (!empty($reply['children'])): ?>
            <?php renderAdminReplies($reply['children'], $boardId, $threadId) ?>
        <?php endif;
    endforeach;
}
?>

<h1>Moderate Thread</h1>

<p>
    <a href="/admin/boards/<?= urlencode($boardId) ?>">Board</a> |
    <a href="/admin">Dashboard</a> |
    <a href="/admin/logout">Logout</a>
</p>

<!-- OP -->
<div class="post post-op">
    <div class="post-meta">
        <span class="post-number">#<?= (int)($op['post_number'] ?? 1) ?></span>
        <span class="post-time"><?= htmlspecialchars(Helpers::relativeTime($op['timestamp'] ?? time()), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="post-ip">IP: <?= htmlspecialchars($op['ip'] ?? '?', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="post-message"><?= htmlspecialchars($op['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
</div>

<!-- Replies -->
<?php renderAdminReplies($replyTree ?? [], $boardId, $threadId) ?>
