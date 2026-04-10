<?php

if (!defined('ABSPATH')) {
    exit;
}

function score_ticker_admin_menu() {
    add_menu_page(
        __('Resultados ticker', 'score-ticker'),
        __('Resultados ticker', 'score-ticker'),
        'manage_options',
        'score-ticker',
        'score_ticker_admin_page',
        'dashicons-table-row-before',
        58
    );
}
add_action('admin_menu', 'score_ticker_admin_menu');

function score_ticker_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table = score_ticker_table_name();

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $row = null;
    if ($edit_id) {
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id),
            ARRAY_A
        );
        if (!$row) {
            $edit_id = 0;
        }
    }

    $rows = $wpdb->get_results(
        "SELECT id, match_date, team_1_name, team_1_score, team_2_name, team_2_score
         FROM {$table}
         ORDER BY match_date DESC, id DESC",
        ARRAY_A
    );
    if (!is_array($rows)) {
        $rows = [];
    }
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('Resultados del ticker', 'score-ticker'); ?></h1>
      <p class="description">
        <?php echo esc_html(
            sprintf(
                __('Los datos se guardan en la tabla de tu hosting: %s', 'score-ticker'),
                $table
            )
        ); ?>
      </p>

      <h2><?php echo $edit_id ? esc_html__('Editar partido', 'score-ticker') : esc_html__('Añadir partido', 'score-ticker'); ?></h2>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width: 520px; margin-bottom: 2rem;">
        <?php wp_nonce_field('score_ticker_match', 'score_ticker_nonce'); ?>
        <input type="hidden" name="action" value="score_ticker_save_match" />
        <input type="hidden" name="id" value="<?php echo $edit_id ? (int) $edit_id : ''; ?>" />
        <table class="form-table" role="presentation">
          <tr>
            <th scope="row"><label for="st_date"><?php esc_html_e('Fecha', 'score-ticker'); ?></label></th>
            <td>
              <input name="match_date" id="st_date" type="date" class="regular-text" required
                value="<?php echo $row ? esc_attr($row['match_date']) : ''; ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="st_t1"><?php esc_html_e('Equipo 1', 'score-ticker'); ?></label></th>
            <td>
              <input name="team_1_name" id="st_t1" type="text" class="regular-text" maxlength="64" required
                value="<?php echo $row ? esc_attr($row['team_1_name']) : ''; ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="st_s1"><?php esc_html_e('Goles equipo 1', 'score-ticker'); ?></label></th>
            <td>
              <input name="team_1_score" id="st_s1" type="number" class="small-text" required step="1"
                value="<?php echo $row ? (int) $row['team_1_score'] : '0'; ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="st_t2"><?php esc_html_e('Equipo 2', 'score-ticker'); ?></label></th>
            <td>
              <input name="team_2_name" id="st_t2" type="text" class="regular-text" maxlength="64" required
                value="<?php echo $row ? esc_attr($row['team_2_name']) : ''; ?>" />
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="st_s2"><?php esc_html_e('Goles equipo 2', 'score-ticker'); ?></label></th>
            <td>
              <input name="team_2_score" id="st_s2" type="number" class="small-text" required step="1"
                value="<?php echo $row ? (int) $row['team_2_score'] : '0'; ?>" />
            </td>
          </tr>
        </table>
        <?php submit_button($edit_id ? __('Guardar cambios', 'score-ticker') : __('Añadir partido', 'score-ticker')); ?>
        <?php if ($edit_id) : ?>
          <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=score-ticker')); ?>"><?php esc_html_e('Cancelar edición', 'score-ticker'); ?></a>
        <?php endif; ?>
      </form>

      <h2><?php esc_html_e('Partidos registrados', 'score-ticker'); ?></h2>
      <table class="widefat striped">
        <thead>
          <tr>
            <th><?php esc_html_e('ID', 'score-ticker'); ?></th>
            <th><?php esc_html_e('Fecha', 'score-ticker'); ?></th>
            <th><?php esc_html_e('Partido', 'score-ticker'); ?></th>
            <th><?php esc_html_e('Resultado', 'score-ticker'); ?></th>
            <th><?php esc_html_e('Acciones', 'score-ticker'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)) : ?>
            <tr><td colspan="5"><?php esc_html_e('No hay partidos.', 'score-ticker'); ?></td></tr>
          <?php else : ?>
            <?php foreach ($rows as $r) : ?>
              <tr>
                <td><?php echo (int) $r['id']; ?></td>
                <td><?php echo esc_html($r['match_date']); ?></td>
                <td><?php echo esc_html($r['team_1_name'] . ' — ' . $r['team_2_name']); ?></td>
                <td><?php echo (int) $r['team_1_score'] . ' — ' . (int) $r['team_2_score']; ?></td>
                <td>
                  <a href="<?php echo esc_url(add_query_arg(['page' => 'score-ticker', 'edit' => (int) $r['id']], admin_url('admin.php'))); ?>"><?php esc_html_e('Editar', 'score-ticker'); ?></a>
                  |
                  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                    <?php wp_nonce_field('score_ticker_delete', 'score_ticker_del_nonce'); ?>
                    <input type="hidden" name="action" value="score_ticker_delete_match" />
                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>" />
                    <button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('¿Eliminar este partido?', 'score-ticker')); ?>');"><?php esc_html_e('Eliminar', 'score-ticker'); ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

function score_ticker_handle_save_match() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos.', 'score-ticker'));
    }
    check_admin_referer('score_ticker_match', 'score_ticker_nonce');

    global $wpdb;
    $table = score_ticker_table_name();

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $date = isset($_POST['match_date']) ? sanitize_text_field(wp_unslash($_POST['match_date'])) : '';
    $t1 = isset($_POST['team_1_name']) ? sanitize_text_field(wp_unslash($_POST['team_1_name'])) : '';
    $s1 = isset($_POST['team_1_score']) ? intval($_POST['team_1_score']) : 0;
    $t2 = isset($_POST['team_2_name']) ? sanitize_text_field(wp_unslash($_POST['team_2_name'])) : '';
    $s2 = isset($_POST['team_2_score']) ? intval($_POST['team_2_score']) : 0;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        wp_safe_redirect(add_query_arg('score_ticker_err', '1', admin_url('admin.php?page=score-ticker')));
        exit;
    }

    $data = [
        'match_date' => $date,
        'team_1_name' => $t1,
        'team_1_score' => $s1,
        'team_2_name' => $t2,
        'team_2_score' => $s2,
    ];
    $format = ['%s', '%s', '%d', '%s', '%d'];

    if ($id) {
        $wpdb->update($table, $data, ['id' => $id], $format, ['%d']);
    } else {
        $wpdb->insert($table, $data, $format);
    }

    wp_safe_redirect(admin_url('admin.php?page=score-ticker&updated=1'));
    exit;
}
add_action('admin_post_score_ticker_save_match', 'score_ticker_handle_save_match');

function score_ticker_handle_delete_match() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos.', 'score-ticker'));
    }
    check_admin_referer('score_ticker_delete', 'score_ticker_del_nonce');

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if ($id) {
        global $wpdb;
        $wpdb->delete(score_ticker_table_name(), ['id' => $id], ['%d']);
    }

    wp_safe_redirect(admin_url('admin.php?page=score-ticker&deleted=1'));
    exit;
}
add_action('admin_post_score_ticker_delete_match', 'score_ticker_handle_delete_match');

function score_ticker_admin_notices() {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'toplevel_page_score-ticker') {
        return;
    }
    if (!empty($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Partido guardado.', 'score-ticker') . '</p></div>';
    }
    if (!empty($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Partido eliminado.', 'score-ticker') . '</p></div>';
    }
    if (!empty($_GET['score_ticker_err'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Fecha no válida.', 'score-ticker') . '</p></div>';
    }
}
add_action('admin_notices', 'score_ticker_admin_notices');
