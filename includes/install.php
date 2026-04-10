<?php

if (!defined('ABSPATH')) {
    exit;
}

function score_ticker_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'score_ticker_matches';
}

function score_ticker_install_db($seed_samples = false) {
    global $wpdb;
    $table = score_ticker_table_name();
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        match_date date NOT NULL,
        team_1_name varchar(64) NOT NULL,
        team_1_score smallint NOT NULL,
        team_2_name varchar(64) NOT NULL,
        team_2_score smallint NOT NULL,
        PRIMARY KEY  (id),
        KEY match_date (match_date)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    if ($seed_samples) {
        $n = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($n === 0) {
            $samples = [
                ['2018-09-01', 'TEN', 2, 'DEP', 1],
                ['2018-09-01', 'SPO', 0, 'ALB', 1],
                ['2018-09-02', 'BOC', 2, 'RAC', 0],
            ];
            foreach ($samples as $r) {
                $wpdb->insert(
                    $table,
                    [
                        'match_date' => $r[0],
                        'team_1_name' => $r[1],
                        'team_1_score' => $r[2],
                        'team_2_name' => $r[3],
                        'team_2_score' => $r[4],
                    ],
                    ['%s', '%s', '%d', '%s', '%d']
                );
            }
        }
    }
}

function score_ticker_activate_plugin() {
    score_ticker_install_db(true);
}
add_action('plugins_loaded', 'score_ticker_ensure_table', 5);

function score_ticker_ensure_table() {
    global $wpdb;
    $table = score_ticker_table_name();
    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($exists !== $table) {
        score_ticker_install_db(false);
    }
}
