<?php

/**
 * Centralized application configuration.
 *
 * Single source of truth for all configurable values.
 * Returns a PHP array at the top level.
 *
 * SDD Reference: Section 3.3
 */

return [
    // --- Paths ---
    'data_dir'        => ROOT_DIR . '/data',
    'boards_dir'      => ROOT_DIR . '/data/boards',
    'ip_logs_dir'     => ROOT_DIR . '/data/ip_logs',
    'app_log_dir'     => ROOT_DIR . '/data/logs',
    'tmp_dir'         => ROOT_DIR . '/data/tmp',
    'template_dir'    => ROOT_DIR . '/templates',

    // --- Board Defaults ---
    'default_max_threads'   => 100,
    'threads_per_page'      => 20,
    'auto_refresh_seconds'  => 30,

    // --- Post Limits ---
    'max_message_length'     => 10000,
    'max_subject_length'     => 200,
    'max_thread_file_size'   => 524288,  // 512 KB
    'message_excerpt_length' => 150,

    // --- Rate Limiting ---
    'rate_limit_max_posts' => 5,
    'rate_limit_window'    => 60,       // seconds

    // --- Session ---
    'session_timeout'      => 3600,     // 1 hour

    // --- Password Hashing ---
    'password_algo'        => PASSWORD_ARGON2ID,
    'password_options'     => [
        'memory_cost' => 65536,
        'time_cost'   => 4,
        'threads'     => 3,
    ],
    'password_min_length'  => 8,

    // --- Security ---
    'csrf_token_length'    => 32,
    'csp_header'           => "default-src 'self'; script-src 'none'; style-src 'self' 'unsafe-inline';",

    // --- Display ---
    'timezone'             => 'UTC',
    'date_format'          => 'Y-m-d H:i:s',
    'reply_indent_px'      => 20,
    'max_indent_levels'    => 10,

    // --- Debug ---
    'debug'                => true,
];
