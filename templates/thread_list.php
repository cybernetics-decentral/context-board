<?php $layout = 'layout'; ?>

<h1><?= htmlspecialchars($board['name'] ?? 'Board', ENT_QUOTES, 'UTF-8') ?></h1>

<?php if (!empty($board['description'])): ?>
    <p><?= htmlspecialchars($board['description'], ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<p>
    <a href="/boards/<?= urlencode($board['board_id'] ?? '') ?>/new" class="button">[ New Thread ]</a>
</p>

<?php if (empty($threads)): ?>
    <p>No threads yet. <a href="/boards/<?= urlencode($board['board_id'] ?? '') ?>/new">Create the first one!</a></p>
<?php else: ?>
    <table class="thread-list">
        <thead>
            <tr>
                <th>Subject</th>
                <th>Replies</th>
                <th>Bump</th>
                <th>Last Activity</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($threads as $thread): ?>
            <tr>
                <td>
                    <a class="thread-subject" href="/boards/<?= urlencode($board['board_id'] ?? '') ?>/thread/<?= urlencode($thread['thread_id']) ?>">
                        <?= htmlspecialchars($thread['subject'] ?? 'No Subject', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <div class="thread-excerpt"><?= htmlspecialchars($thread['message_excerpt'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </td>
                <td class="thread-meta"><?= (int)($thread['reply_count'] ?? 0) ?></td>
                <td class="thread-meta"><?= (int)($thread['bump_score'] ?? 0) ?></td>
                <td class="thread-meta"><?= htmlspecialchars(Helpers::relativeTime($thread['last_modified'] ?? $thread['created_at'] ?? time()), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php if ($p === $page): ?>
                <strong><?= $p ?></strong>
            <?php else: ?>
                <a href="/boards/<?= urlencode($board['board_id'] ?? '') ?>?page=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
