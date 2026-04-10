<?php
/**
 * Plugin Name: Score Ticker
 * Description: Ticker de partidos leyendo de base de datos.
 * Version: 1.1.0
 * Text Domain: score-ticker
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SCORE_TICKER_VERSION', '1.1.0');
define('SCORE_TICKER_PATH', plugin_dir_path(__FILE__));
define('SCORE_TICKER_URL', plugin_dir_url(__FILE__));

function score_ticker_load_textdomain() {
    load_plugin_textdomain(
        'score-ticker',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'score_ticker_load_textdomain');

require_once SCORE_TICKER_PATH . 'includes/install.php';
require_once SCORE_TICKER_PATH . 'includes/admin.php';

register_activation_hook(__FILE__, 'score_ticker_activate_plugin');

function score_ticker_rest_routes() {
    register_rest_route('score-ticker/v1', '/matches', [
        'methods' => 'GET',
        'callback' => 'score_ticker_rest_get_matches',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'score_ticker_rest_routes');

function score_ticker_rest_get_matches() {
    global $wpdb;
    $table = score_ticker_table_name();
    $rows = $wpdb->get_results(
        "SELECT id, match_date, team_1_name, team_1_score, team_2_name, team_2_score
         FROM {$table}
         ORDER BY match_date ASC, id ASC",
        ARRAY_A
    );
    if (!is_array($rows)) {
        $rows = [];
    }
    foreach ($rows as &$r) {
        if (isset($r['match_date'])) {
            $r['match_date'] = (string) $r['match_date'];
        }
        $r['team_1_score'] = (int) $r['team_1_score'];
        $r['team_2_score'] = (int) $r['team_2_score'];
    }
    return new WP_REST_Response($rows, 200);
}

function score_ticker_enqueue() {
    wp_register_style(
        'score-ticker',
        SCORE_TICKER_URL . 'assets/ticker.css',
        [],
        SCORE_TICKER_VERSION
    );
    wp_register_script(
        'score-ticker',
        SCORE_TICKER_URL . 'assets/ticker.js',
        [],
        SCORE_TICKER_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'score_ticker_enqueue');

function score_ticker_shortcode() {
    wp_enqueue_style(
        'score-ticker-font',
        'https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@400;700&display=swap',
        [],
        null
    );
    wp_enqueue_style('score-ticker');
    wp_enqueue_script('score-ticker');
    wp_localize_script('score-ticker', 'scoreTickerData', [
        'restUrl' => esc_url_raw(rest_url('score-ticker/v1/matches')),
        'locale' => determine_locale(),
        'i18n' => [
            'loadFailed' => __('No se pudieron cargar los datos.', 'score-ticker'),
            'noMatches' => __('No hay partidos registrados.', 'score-ticker'),
            'networkError' => __('Error de red.', 'score-ticker'),
        ],
    ]);

    ob_start();
    ?>
<div class="score-ticker-wrap score-ticker-wrap--wp" id="scoreTickerWp">
  <button type="button" class="ticker-nav ticker-nav--prev" aria-label="<?php echo esc_attr__('Anterior', 'score-ticker'); ?>">&#8249;</button>
  <div class="ticker-viewport" id="tickerViewport">
    <div class="ticker-track" id="tickerTrack" hidden></div>
    <div class="ticker-empty" id="tickerPlaceholder" hidden></div>
  </div>
  <button type="button" class="ticker-nav ticker-nav--next" aria-label="<?php echo esc_attr__('Siguiente', 'score-ticker'); ?>">&#8250;</button>
</div>
    <?php
    return ob_get_clean();
}
add_shortcode('score_ticker', 'score_ticker_shortcode');
