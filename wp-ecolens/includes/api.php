<?php
if (!defined('ABSPATH')) { exit; }

function ecolens_input($name, $default = '') {
    return isset($_POST[$name]) && is_string($_POST[$name]) ? wp_unslash($_POST[$name]) : $default;
}
function ecolens_authorize() {
    if (!current_user_can('manage_options')) { wp_send_json_error(['message' => 'Acesso restrito a administradores.'], 403); }
    check_ajax_referer('ecolens_nonce', 'nonce');
}
function ecolens_published_post() {
    $post = get_post(absint(ecolens_input('post_id')));
    if (!$post || $post->post_status !== 'publish' || !in_array($post->post_type, ['page', 'post'], true) || $post->post_password !== '') {
        wp_send_json_error(['message' => 'Selecione uma página ou post publicado, sem senha.'], 400);
    }
    return $post;
}
add_action('wp_ajax_ecolens_list', function () {
    ecolens_authorize();
    $type = ecolens_input('type');
    if (!in_array($type, ['page', 'post'], true)) { wp_send_json_error(['message' => 'Tipo de conteúdo inválido.'], 400); }
    $page = max(1, min(100000, absint(ecolens_input('page', '1'))));
    $query = new WP_Query([
        'post_type' => $type, 'post_status' => 'publish', 'has_password' => false,
        'posts_per_page' => 20, 'paged' => $page,
        's' => substr(sanitize_text_field(ecolens_input('search')), 0, 160),
        'orderby' => $type === 'page' ? ['title' => 'ASC', 'ID' => 'ASC'] : ['date' => 'DESC', 'ID' => 'DESC'],
    ]);
    $items = [];
    foreach ($query->posts as $post) {
        $items[] = ['id' => $post->ID, 'title' => get_the_title($post->ID), 'url' => get_permalink($post->ID)];
    }
    wp_send_json_success(['items' => $items, 'page' => $page, 'pages' => (int) $query->max_num_pages, 'total' => (int) $query->found_posts]);
});
add_action('wp_ajax_ecolens_get_images', function () {
    ecolens_authorize();
    $post = ecolens_published_post();
    $images = ecolens_collect_images($post->ID);
    foreach ($images as &$image) {
        $image['compressible'] = $image['id'] && in_array(get_post_mime_type($image['id']), ['image/jpeg', 'image/png'], true);
    }
    unset($image);
    wp_send_json_success(['images' => $images, 'limit' => (int) get_option('ecolens_limit', 200)]);
});
add_action('wp_ajax_ecolens_save_config', function () {
    ecolens_authorize();
    $limit = filter_var(ecolens_input('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 100000]]);
    if ($limit === false) { wp_send_json_error(['message' => 'Informe um inteiro entre 10 e 100.000 KB.'], 400); }
    update_option('ecolens_limit', $limit);
    if ((int) get_option('ecolens_limit') !== $limit) { wp_send_json_error(['message' => 'A meta não foi salva.'], 500); }
    wp_send_json_success(['limit' => $limit]);
});
add_action('wp_ajax_ecolens_compress', function () {
    ecolens_authorize();
    $result = ecolens_compress_image(absint(ecolens_input('attachment_id')));
    if (is_wp_error($result)) { wp_send_json_error(['message' => $result->get_error_message()], 400); }
    wp_send_json_success($result);
});

/** SWDM v4, one uncached first visit, no renewable-hosting discount. */
function ecolens_carbon($bytes) {
    if (!is_numeric($bytes) || $bytes < 0) { return null; }
    return ($bytes / 1000000000) * (0.055 + 0.059 + 0.080 + 0.012 + 0.013 + 0.081) * 494;
}
function ecolens_number($value) {
    return is_numeric($value) && is_finite((float) $value) && (float) $value >= 0 ? (float) $value : null;
}
function ecolens_parse_lighthouse($data) {
    $lh = $data['lighthouseResult'] ?? [];
    if (empty($lh['audits']) || !empty($lh['runtimeError'])) { return new WP_Error('analysis', 'O Google não conseguiu analisar esta página. Confira se ela é pública e acessível.'); }
    $audits = $lh['audits'];
    $weight = ecolens_number($audits['total-byte-weight']['numericValue'] ?? null);
    $issues = [];
    foreach (($lh['categories']['accessibility']['auditRefs'] ?? []) as $ref) {
        $audit = $audits[$ref['id']] ?? [];
        $score = ecolens_number($audit['score'] ?? null);
        if ($score !== null && $score < 1) { $issues[] = ['title' => sanitize_text_field($audit['title'] ?? $ref['id']), 'id' => sanitize_key($ref['id'])]; }
    }
    return [
        'bytes' => $weight, 'carbon' => ecolens_carbon($weight),
        'performance' => ecolens_number($lh['categories']['performance']['score'] ?? null),
        'accessibility' => ecolens_number($lh['categories']['accessibility']['score'] ?? null),
        'lcp' => ecolens_number($audits['largest-contentful-paint']['numericValue'] ?? null),
        'cls' => ecolens_number($audits['cumulative-layout-shift']['numericValue'] ?? null),
        'tbt' => ecolens_number($audits['total-blocking-time']['numericValue'] ?? null),
        'issues' => $issues, 'fetched' => sanitize_text_field($lh['fetchTime'] ?? gmdate('c')),
    ];
}
add_action('wp_ajax_ecolens_analyze_page', function () {
    ecolens_authorize();
    $post = ecolens_published_post();
    $url = get_permalink($post->ID);
    $strategy = ecolens_input('strategy', 'mobile');
    if (!in_array($strategy, ['mobile', 'desktop'], true)) { wp_send_json_error(['message' => 'Dispositivo inválido.'], 400); }
    // Only send public canonical URLs from this WordPress installation, never arbitrary input.
    if (!wp_http_validate_url($url)) { wp_send_json_error(['message' => 'A análise Google exige uma URL pública. Sites locais, privados ou protegidos não podem ser analisados.'], 400); }
    $cache_key = 'ecolens_psi_' . md5($url . $strategy . ECOLENS_VERSION);
    $cached = get_transient($cache_key);
    if (is_array($cached)) { $cached['cached'] = true; wp_send_json_success($cached); }
    $rate = 'ecolens_rate_' . get_current_user_id();
    if (get_transient($rate)) { wp_send_json_error(['message' => 'Aguarde um minuto entre novas análises externas.'], 429); }
    set_transient($rate, 1, 60);
    $endpoint = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . rawurlencode($url) . '&strategy=' . $strategy . '&locale=pt-BR&category=performance&category=accessibility';
    if (defined('ECOLENS_GOOGLE_API_KEY') && ECOLENS_GOOGLE_API_KEY) { $endpoint .= '&key=' . rawurlencode(ECOLENS_GOOGLE_API_KEY); }
    $response = wp_safe_remote_get($endpoint, ['timeout' => 60, 'redirection' => 0, 'limit_response_size' => 8 * 1024 * 1024]);
    if (is_wp_error($response)) { wp_send_json_error(['message' => 'Falha de conexão ou tempo esgotado no PageSpeed. Tente novamente mais tarde.'], 502); }
    if (wp_remote_retrieve_response_code($response) !== 200) { wp_send_json_error(['message' => 'PageSpeed indisponível, cota excedida ou chave inválida. Confira a API e tente mais tarde.'], 502); }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($data)) { wp_send_json_error(['message' => 'Resposta inválida ou acima do limite de tamanho do PageSpeed.'], 502); }
    $result = ecolens_parse_lighthouse($data);
    if (is_wp_error($result)) { wp_send_json_error(['message' => $result->get_error_message()], 502); }
    $result['url'] = esc_url_raw($url);
    $result['strategy'] = $strategy;
    $result['cached'] = false;
    set_transient($cache_key, $result, 10 * MINUTE_IN_SECONDS);
    wp_send_json_success($result);
});

add_action('wp_ajax_ecolens_crux', function () {
    ecolens_authorize();
    $post = ecolens_published_post();
    if (!defined('ECOLENS_CRUX_API_KEY') || !ECOLENS_CRUX_API_KEY) { wp_send_json_error(['message' => 'Configure a chave CrUX para consultar LCP, INP e CLS de visitantes reais.']); }
    $url = get_permalink($post->ID);
    if (!wp_http_validate_url($url)) { wp_send_json_error(['message' => 'O CrUX exige uma URL pública.'], 400); }
    $strategy = ecolens_input('strategy', 'mobile');
    if (!in_array($strategy, ['mobile', 'desktop'], true)) { wp_send_json_error(['message' => 'Dispositivo inválido.'], 400); }
    $key = 'ecolens_crux_' . md5($url . $strategy);
    $cached = get_transient($key);
    if (is_array($cached)) { wp_send_json_success($cached); }
    $response = wp_safe_remote_post('https://chromeuxreport.googleapis.com/v1/records:queryRecord?key=' . rawurlencode(ECOLENS_CRUX_API_KEY), [
        'timeout' => 20, 'redirection' => 0, 'limit_response_size' => 1024 * 1024,
        'headers' => ['Content-Type' => 'application/json'],
        'body' => wp_json_encode(['url' => $url, 'formFactor' => $strategy === 'mobile' ? 'PHONE' : 'DESKTOP', 'metrics' => ['largest_contentful_paint', 'interaction_to_next_paint', 'cumulative_layout_shift']]),
    ]);
    if (is_wp_error($response)) { wp_send_json_error(['message' => 'Não foi possível consultar o CrUX.']); }
    $status = wp_remote_retrieve_response_code($response);
    if ($status !== 200) { wp_send_json_error(['message' => $status === 404 ? 'O CrUX não tem dados suficientes para esta URL e dispositivo. Isso não indica desempenho ruim.' : 'CrUX indisponível. Confira a chave, a API habilitada e a cota.']); }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($data['record']['metrics'])) { wp_send_json_error(['message' => 'Dados CrUX não disponíveis.']); }
    $metrics = [];
    foreach (['largest_contentful_paint', 'interaction_to_next_paint', 'cumulative_layout_shift'] as $name) {
        $metrics[$name] = ecolens_number($data['record']['metrics'][$name]['percentiles']['p75'] ?? null);
    }
    $result = ['metrics' => $metrics, 'period' => $data['record']['collectionPeriod'] ?? null, 'url' => $url];
    set_transient($key, $result, HOUR_IN_SECONDS);
    wp_send_json_success($result);
});
