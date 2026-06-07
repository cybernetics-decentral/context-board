<?php

/**
 * IpLogger — append-only IP address logging.
 *
 * SDD Reference: Section 3.9
 */
class IpLogger
{
    private string $ipLogsDir;

    public function __construct(string $ipLogsDir)
    {
        $this->ipLogsDir = rtrim($ipLogsDir, '/');
    }

    /**
     * Log a post action with IP address.
     */
    public function log(string $boardId, string $threadId, string $postId, string $ip, string $action): void
    {
        $date = gmdate('Y-m-d');
        $logPath = $this->ipLogsDir . '/' . $date . '.log';

        $entry = [
            'timestamp' => time(),
            'board_id'  => $boardId,
            'thread_id' => $threadId,
            'post_id'   => $postId,
            'ip'        => $ip,
            'action'    => $action,
        ];

        if (!is_dir($this->ipLogsDir)) {
            mkdir($this->ipLogsDir, 0755, true);
        }

        $line = json_encode($entry, JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }
}
