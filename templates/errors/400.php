<?php $layout = 'layout'; ?>
<h1>Bad Request</h1>
<p><?= htmlspecialchars($message ?? 'Bad request.', ENT_QUOTES, 'UTF-8') ?></p>
<p><a href="/">&laquo; Back to Home</a></p>
