<?php $layout = 'layout'; ?>

<h1>Text Board</h1>

<?php if (empty($boards)): ?>
    <p>No boards have been created yet.</p>
<?php else: ?>
    <ul class="board-list">
    <?php foreach ($boards as $board): ?>
        <li>
            <a href="/boards/<?= urlencode($board['board_id']) ?>">
                <?= htmlspecialchars($board['name'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php if (!empty($board['description'])): ?>
                <div class="board-desc"><?= htmlspecialchars($board['description'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
