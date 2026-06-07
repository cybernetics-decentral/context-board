<?php $layout = 'layout'; ?>
<h1>Page Not Found</h1>
<p><?= htmlspecialchars($message ?? 'The requested page was not found.', ENT_QUOTES, 'UTF-8') ?></p>
<p><a href="/">&laquo; Back to Home</a></p>
