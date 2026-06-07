<?php $layout = 'layout'; ?>
<h1>Forbidden</h1>
<p><?= htmlspecialchars($message ?? 'Access denied.', ENT_QUOTES, 'UTF-8') ?></p>
<p><a href="/">&laquo; Back to Home</a></p>
