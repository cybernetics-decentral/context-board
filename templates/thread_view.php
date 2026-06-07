<?php $layout = 'layout'; ?>

<?php
function renderReplies(array $tree, string $boardId, string $threadId): void {
    foreach ($tree as $reply):
        $depth = min((int)($reply['depth'] ?? 0), 10);
?>
        <div class="post post-reply reply-depth-<?= $depth ?>"
             id="post-<?= htmlspecialchars($reply['post_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="post-meta">
                <span class="post-number">#<?= (int)($reply['post_number'] ?? 0) ?></span>
                <span class="post-time"><?= htmlspecialchars(Helpers::relativeTime($reply['timestamp'] ?? time()), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="post-message"><?= htmlspecialchars($reply['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            <a class="post-reply-link"
               href="/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($threadId) ?>/reply?parent_id=<?= urlencode($reply['post_id'] ?? '') ?>">
               [Reply]
            </a>
        </div>
        <?php if (!empty($reply['children'])): ?>
            <?php renderReplies($reply['children'], $boardId, $threadId) ?>
        <?php endif;
    endforeach;
}
?>

<h1 class="thread-subject-title"><?= htmlspecialchars($thread['subject'] ?? 'No Subject', ENT_QUOTES, 'UTF-8') ?></h1>

<!-- OP -->
<div class="post post-op" id="post-<?= htmlspecialchars($op['post_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <div class="post-meta">
        <span class="post-number">#<?= (int)($op['post_number'] ?? 1) ?></span>
        <span class="post-time"><?= htmlspecialchars(Helpers::relativeTime($op['timestamp'] ?? time()), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="post-message"><?= htmlspecialchars($op['message'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
    <a class="post-reply-link"
       href="/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($threadId) ?>/reply">
       [Reply]
    </a>
</div>

<!-- Replies -->
<?php renderReplies($replyTree ?? [], $boardId, $threadId) ?>

<p style="margin-top: 1rem;">
    <a href="/boards/<?= urlencode($boardId) ?>/thread/<?= urlencode($threadId) ?>/reply" class="button">Post a Reply</a>
</p>

<p><a href="/boards/<?= urlencode($boardId) ?>">&laquo; Back to board</a></p>
