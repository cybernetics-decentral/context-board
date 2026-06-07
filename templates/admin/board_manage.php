<?php $layout = 'layout'; ?>

<h1>Manage Boards</h1>

<p>
    <a href="/admin">Dashboard</a> |
    <a href="/admin/logout">Logout</a>
</p>

<h2>Create New Board</h2>

<form method="POST" action="/admin/boards/create">
    <?= Security::getCsrfTokenField() ?>

    <p>
        <label for="board_id">Board ID (URL slug):</label>
        <input type="text" id="board_id" name="board_id" required
               pattern="[a-zA-Z0-9][a-zA-Z0-9_-]{0,30}[a-zA-Z0-9]"
               placeholder="e.g., general, tech-news">
    </p>

    <p>
        <label for="name">Display Name:</label>
        <input type="text" id="name" name="name" maxlength="100" placeholder="General Discussion">
    </p>

    <p>
        <label for="description">Description:</label>
        <input type="text" id="description" name="description" maxlength="500" placeholder="Talk about anything.">
    </p>

    <p>
        <button type="submit">Create Board</button>
    </p>
</form>

<h2>Existing Boards</h2>

<?php if (empty($boards)): ?>
    <p>No boards yet.</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($boards as $board): ?>
            <tr>
                <td><?= htmlspecialchars($board['board_id'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($board['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($board['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="/admin/boards/<?= urlencode($board['board_id']) ?>">Moderate</a>

                    <!-- Rename Form -->
                    <form method="POST" action="/admin/boards/<?= urlencode($board['board_id']) ?>/rename" style="display:inline;">
                        <?= Security::getCsrfTokenField() ?>
                        <input type="text" name="name" value="<?= htmlspecialchars($board['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" size="12">
                        <button type="submit">Rename</button>
                    </form>

                    <!-- Delete Form -->
                    <form method="POST" action="/admin/boards/<?= urlencode($board['board_id']) ?>/delete" style="display:inline;">
                        <?= Security::getCsrfTokenField() ?>
                        <label>
                            <input type="checkbox" name="confirm" value="1">
                            Confirm
                        </label>
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
