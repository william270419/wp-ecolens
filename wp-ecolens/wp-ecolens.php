<?php
/**
 * Plugin Name: WP EcoLens - Auditor de imagens
 * Plugin URI: https://github.com/william270419/wp-ecolens
 * Description: Identifica imagens associadas a páginas e posts e compara o tamanho dos arquivos com uma meta configurável.
 * Version: 1.0.1
 * Author: William Marques
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;
add_action('admin_enqueue_scripts', function($hook) {
	if ('toplevel_page_wp-ecolens' === $hook) wp_enqueue_script('jquery');
});
add_action('admin_menu', function() {
	add_menu_page(
		'EcoLens Auditor', 
		'EcoLens Audit', 
		'manage_options', 
		'wp-ecolens', 
		'ecolens_admin_page', 
		'dashicons-performance', 
		80
	);
});
function ecolens_admin_page() {
	if ( !current_user_can('manage_options') ) return;
	$size_threshold = max(10, min(100000, (int) get_option('ecolens_limit', 200))) * 1024;
	$pages = get_pages(['sort_column' => 'post_title', 'post_status'=>'publish', 'number'=>50]);
	$posts = get_posts(['post_type'=>'post', 'numberposts'=>50, 'post_status'=>'publish']);
	$nonce = wp_create_nonce('ecolens_nonce');
?>
<div class="wrap wia-wrap">
	<header class="wia-header">
		<div class="wia-header-title">
			<h1>🌱 WP EcoLens <span class="wia-version">v1.0.1</span></h1>
			<p class="wia-subtitle">Auditor de imagens para WordPress</p>
		</div>
		<div class="wia-header-actions">
			<button class="button button-secondary" onclick="window.print()">🖨️ Imprimir Relatório / Interface</button>
		</div>
	</header>
	<div class="wia-main-layout">
		<aside class="wia-sidebar">
			<nav class="wia-nav">
				<button class="wia-tab active" data-target="pages"><span class="dashicons dashicons-admin-page"></span> Páginas</button>
				<button class="wia-tab" data-target="posts"><span class="dashicons dashicons-admin-post"></span> Artigos (Posts)</button>
				<button class="wia-tab" data-target="config"><span class="dashicons dashicons-admin-settings"></span> Configurar Meta</button>
			</nav>
			<div class="wia-info-box">
				<h4>Impacto Social</h4>
				<p>Identifique imagens acima da sua meta de tamanho para orientar melhorias no consumo de dados. O plugin não comprime arquivos automaticamente.</p>
			</div>
		</aside>
		<main class="wia-content">
			<div class="wia-controls">
				<input type="text" id="wia-search" aria-label="Filtrar páginas e posts" placeholder="🔍 Filtrar conteúdo..." />
			</div>
			<div class="wia-tab-section" id="wia-pages">
				<ul class="wia-list">
					<?php foreach($pages as $p): ?>
					<li>
						<div class="wia-list-info">
							<strong><?php echo esc_html($p->post_title); ?></strong>
							<a href="<?php echo esc_url(get_permalink($p->ID)); ?>" target="_blank" rel="noopener noreferrer" class="wia-link-ext">Ver site <span class="dashicons dashicons-external"></span></a>
						</div>
						<button class="button button-secondary wia-btn-audit" data-postid="<?php echo intval($p->ID); ?>">Auditar Página</button>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="wia-tab-section" id="wia-posts" style="display:none;">
				<ul class="wia-list">
					<?php foreach($posts as $p): ?>
					<li>
						<div class="wia-list-info">
							<strong><?php echo esc_html($p->post_title); ?></strong>
							<small><?php echo esc_html(get_the_date('d/m/Y', $p->ID)); ?></small>
						</div>
						<button class="button button-secondary wia-btn-audit" data-postid="<?php echo intval($p->ID); ?>">Auditar Post</button>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="wia-tab-section" id="wia-config" style="display:none;">
				<div class="wia-card">
					<h3>⚙️ Meta de Performance (Performance Budget)</h3>
					<form id="wia-config-form">
						<label for="wia-limit">Defina o limite máximo aceitável para uma imagem (KB):</label>
						<div class="wia-input-group">
							<input type="number" id="wia-limit" value="<?php echo intval($size_threshold / 1024); ?>" min="10" max="100000" step="1" required>
							<span>KB</span>
						</div>
						<p class="description">A meta inicial é 200 KB por imagem (1 KB = 1.024 bytes). Ajuste conforme o projeto. Este limite não garante uma pontuação de desempenho ou de Core Web Vitals.</p>
						<hr>
						<button class="button button-primary button-large" id="wia-save-config">Salvar Definições</button>
						<span id="wia-save-msg" role="status" aria-live="polite"></span>
					</form>
				</div>
			</div>
			<div class="wia-results-area">
				<div class="wia-results-header">
					<h3>Relatório de Auditoria</h3>
					<div class="wia-actions-right">
						<span id="wia-status-summary"></span>
					</div>
				</div>
				<div id="wia-viewport" aria-live="polite">
					<div class="wia-placeholder">
						<span class="dashicons dashicons-chart-pie"></span>
						<p>Selecione um item na lista acima para iniciar a auditoria.</p>
					</div>
				</div>
			</div>
		</main>
	</div>
</div>
<style>
.wia-wrap { --wia-primary: #2271b1; --wia-danger: #d63638; --wia-success: #00a32a; --wia-bg: #f0f0f1; }
.wia-wrap { background: #fff; padding: 0; border-radius: 8px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden; max-width: 1200px; margin: 20px auto; display: flex; flex-direction: column; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
.wia-header { background: #fff; padding: 20px 30px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
.wia-header h1 { margin: 0; font-size: 24px; color: #1d2327; display: flex; align-items: center; gap: 10px; }
.wia-version { font-size: 12px; background: #e5e5e5; padding: 2px 8px; border-radius: 10px; color: #666; }
.wia-subtitle { margin: 0; color: #646970; }
.wia-main-layout { display: grid; grid-template-columns: 280px 1fr; min-height: 600px; }
.wia-sidebar { background: #f6f7f7; border-right: 1px solid #ddd; padding: 20px; display: flex; flex-direction: column; gap: 20px; }
.wia-nav button { display: block; width: 100%; text-align: left; padding: 12px 15px; margin-bottom: 5px; border: none; background: transparent; cursor: pointer; border-radius: 6px; font-weight: 500; color: #3c434a; transition: .2s; font-size: 14px; }
.wia-nav button:hover { background: #e0e0e0; }
.wia-nav button.active { background: var(--wia-primary); color: #fff; box-shadow: 0 2px 5px rgba(34, 113, 177, 0.3); }
.wia-nav button .dashicons { margin-right: 8px; }
.wia-info-box { background: #e6f6ff; border: 1px solid #bce0fd; padding: 15px; border-radius: 6px; font-size: 13px; color: #003c66; }
.wia-info-box h4 { margin: 0 0 5px 0; font-weight: 700; }
.wia-content { padding: 30px; background: #fff; }
.wia-controls { margin-bottom: 20px; }
#wia-search { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
.wia-list { list-style: none; margin: 0; padding: 0; max-height: 500px; overflow-y: auto; }
.wia-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #f0f0f1; transition: .2s; }
.wia-list li:hover { background: #fcfcfc; }
.wia-list-info { display: flex; flex-direction: column; }
.wia-link-ext { font-size: 11px; text-decoration: none; color: #2271b1; }
.wia-results-area { margin-top: 30px; border-top: 2px solid #f0f0f1; padding-top: 20px; }
.wia-results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.wia-img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
.wia-card-img { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #fff; transition: .3s; position: relative; }
.wia-card-img:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.wia-badge { position: absolute; top: 10px; right: 10px; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.wia-badge.bad { background: var(--wia-danger); }
.wia-badge.good { background: var(--wia-success); }
.wia-badge.unknown { background: #646970; }
.wia-thumb-box { height: 140px; display: flex; align-items: center; justify-content: center; background: #eee; overflow: hidden; }
.wia-thumb-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
.wia-meta-box { padding: 12px; font-size: 13px; text-align: center; }
.wia-size { font-size: 16px; font-weight: 700; display: block; margin-bottom: 4px; }
.wia-dim { color: #888; font-size: 11px; }
.wia-placeholder { text-align: center; padding: 50px; color: #ccc; }
.wia-placeholder .dashicons { font-size: 60px; width: 60px; height: 60px; margin-bottom: 10px; }
.wia-loader { text-align: center; padding: 40px; color: var(--wia-primary); font-weight: 600; }
.wia-card { background: #fff; border: 1px solid #e5e5e5; padding: 25px; border-radius: 8px; max-width: 500px; }
.wia-input-group { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
#wia-limit { padding: 8px; font-size: 16px; width: 100px; }
@media screen and (max-width: 782px) {
	.wia-main-layout { grid-template-columns: 1fr; }
	.wia-header { flex-wrap: wrap; gap: 15px; padding: 20px; }
	.wia-sidebar { border-right: 0; border-bottom: 1px solid #ddd; }
	.wia-content { padding: 20px; min-width: 0; }
	.wia-list li, .wia-results-header { flex-wrap: wrap; gap: 10px; }
}
@media print {
	body * { visibility: hidden; }
	#adminmenumain, #wpadminbar, #wpfooter { display: none !important; }
	.wia-wrap, .wia-wrap * { visibility: visible; }
	.wia-wrap {
		position: absolute;
		left: 0;
		top: 0;
		width: 100%;
		max-width: 100%;
		margin: 0;
		box-shadow: none;
		border: none;
	}
	.wia-main-layout { display: grid; grid-template-columns: 200px 1fr; } /* Diminui a sidebar no papel */
	.wia-content { padding: 10px; }
	.wia-btn-audit, .wia-controls, .wia-header-actions button, #wia-save-config { display: none !important; }
	* { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
<script>
jQuery(function($){
	let threshold = <?php echo wp_json_encode($size_threshold); ?>;
	const nonce = <?php echo wp_json_encode($nonce); ?>;
	const escapeHtml = (value) => String(value == null ? '' : value).replace(/[&<>"']/g, (char) => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));

	$('.wia-tab').on('click', function(){
		let target = $(this).data('target');
		$('.wia-tab').removeClass('active');
		$(this).addClass('active');
		$('.wia-tab-section').hide();
		$('#wia-'+target).fadeIn(200);
	});

	$('#wia-search').on('input', function(){
		let term = $(this).val().toLowerCase();
		$('.wia-tab-section:visible li').each(function(){
			let text = $(this).text().toLowerCase();
			$(this).toggle(text.indexOf(term) > -1);
		});
	});

	$(document).on('click', '.wia-btn-audit', function(e){
		e.preventDefault();
		let postID = $(this).data('postid');
		let title = $(this).closest('li').find('strong').text();
		$('.wia-btn-audit, #wia-save-config').prop('disabled', true);

		$('#wia-viewport').html('<div class="wia-loader"><span class="dashicons dashicons-update spin"></span><br>Escaneando ativos digitais...</div>');
		$('#wia-status-summary').html('');

		$.post(ajaxurl, {
			action: 'ecolens_get_images',
			nonce: nonce,
			post_id: postID
		}, function(resp){
			if(!resp || !resp.success) {
				$('#wia-viewport').html('<div class="notice notice-error"><p>Erro ao auditar.</p></div>');
				return;
			}
			let imgs = resp.data.images || [];
			if(imgs.length === 0){
				$('#wia-viewport').html('<div class="wia-placeholder"><span class="dashicons dashicons-yes-alt"></span><p>Nenhuma imagem encontrada neste conteúdo.</p></div>');
				return;
			}
			let html = `<p style="margin-bottom:20px;"><strong>Resultados para:</strong> ${escapeHtml(title)}</p><div class="wia-img-grid">`;
			let issues = 0;
			let unknown = 0;
			imgs.forEach(img => {
				let known = typeof img.filesize === 'number' && Number.isFinite(img.filesize) && img.filesize > 0;
				let isHeavy = known && img.filesize > threshold;
				let badgeClass = !known ? 'unknown' : (isHeavy ? 'bad' : 'good');
				let badgeText = !known ? 'SEM DADOS' : (isHeavy ? 'ACIMA DA META' : 'DENTRO DA META');
				if(isHeavy) issues++;
				if(!known) unknown++;
				html += `
				<div class="wia-card-img" style="${isHeavy ? 'border-color:var(--wia-danger);' : ''}">
					<div class="wia-badge ${badgeClass}">${badgeText}</div>
					<div class="wia-thumb-box"><a href="${escapeHtml(img.url)}" target="_blank" rel="noopener noreferrer"><img src="${escapeHtml(img.url)}" alt="Visualizar imagem auditada" loading="lazy"></a></div>
					<div class="wia-meta-box"><span class="wia-size" style="${isHeavy ? 'color:var(--wia-danger);' : ''}">${escapeHtml(img.filesize_human)}</span><span class="wia-dim">${escapeHtml(img.width)} x ${escapeHtml(img.height)} px</span></div>
				</div>`;
			});
			html += '</div>';
			$('#wia-viewport').html(html).hide().fadeIn();
			$('#wia-status-summary').text(`${issues} acima da meta • ${unknown} sem dados`);
		}).fail(function() {
			$('#wia-viewport').text('Não foi possível concluir a auditoria. Recarregue a página e tente novamente.');
		}).always(function() {
			$('.wia-btn-audit, #wia-save-config').prop('disabled', false);
		});
	});

	$('#wia-config-form').on('submit', function(e){
		e.preventDefault();
		if ($('#wia-save-config').prop('disabled')) return;
		let val = Number($('#wia-limit').val());
		if (!Number.isInteger(val) || val < 10 || val > 100000) {
			$('#wia-save-msg').show().text('Informe um número inteiro entre 10 e 100.000 KB.');
			return;
		}
		$('.wia-btn-audit, #wia-save-config').prop('disabled', true);
		$('#wia-save-msg').show().text('Salvando...');
		$.post(ajaxurl, {action: 'ecolens_save_config', nonce: nonce, limit: val}, function(resp){
			if (!resp || !resp.success) {
				$('#wia-save-msg').text('Não foi possível salvar a meta.');
				return;
			}
			threshold = Number(resp.data.limit) * 1024;
			$('#wia-save-msg').text('Meta salva!');
			$('#wia-status-summary').text('');
			$('#wia-viewport').text('Meta atualizada. Execute uma nova auditoria para aplicar o limite.');
		}).fail(function() {
			$('#wia-save-msg').text('Falha ao salvar. Recarregue a página e tente novamente.');
		}).always(function() {
			$('.wia-btn-audit, #wia-save-config').prop('disabled', false);
		});
	});
});
</script>
<?php
}

add_action('wp_ajax_ecolens_get_images', function(){
	check_ajax_referer('ecolens_nonce', 'nonce');
	if(!current_user_can('manage_options')) wp_send_json_error();
	$post_id = isset($_POST['post_id']) && is_scalar($_POST['post_id']) ? absint($_POST['post_id']) : 0;
	$post = get_post($post_id);
	if (!$post || 'publish' !== $post->post_status || !in_array($post->post_type, ['post', 'page'], true)) {
		wp_send_json_error(['message' => 'Conteúdo publicado não encontrado.'], 400);
	}
	$images = ecolens_collect_images($post_id);
	wp_send_json_success(['images' => $images]);
});

add_action('wp_ajax_ecolens_save_config', function(){
	check_ajax_referer('ecolens_nonce', 'nonce');
	if(!current_user_can('manage_options')) wp_send_json_error();
	$raw_limit = isset($_POST['limit']) && is_string($_POST['limit']) ? wp_unslash($_POST['limit']) : '';
	$limit = filter_var($raw_limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 100000]]);
	if (false === $limit) wp_send_json_error(['message' => 'Meta inválida.'], 400);
	update_option('ecolens_limit', $limit);
	wp_send_json_success(['limit' => $limit]);
});

function ecolens_collect_images($post_id){
	$images = []; $seen = [];
	$add = function($data) use (&$images, &$seen){ if($data && !in_array($data['url'], $seen)){ $images[] = $data; $seen[] = $data['url']; } };
	if($thumb = get_post_thumbnail_id($post_id)) { $add(ecolens_get_attachment_info($thumb)); }
	$media = get_attached_media('image', $post_id);
	foreach($media as $att){ $add(ecolens_get_attachment_info($att->ID)); }
	$post = get_post($post_id);
	if($post){
		preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);
		if(!empty($matches[1])) {
			foreach($matches[1] as $src){
				$src = esc_url_raw(trim($src));
				if(!$src || in_array($src, $seen)) continue;
				$id = attachment_url_to_postid($src);
				if($id) { $add(ecolens_get_attachment_info($id)); } else { $add(ecolens_get_external_info($src)); }
			}
		}
	}
	return $images;
}

function ecolens_get_attachment_info($id){
	$url = esc_url_raw(wp_get_attachment_url($id), ['http', 'https']); if(!$url) return false;
	$file = get_attached_file($id); $meta = wp_get_attachment_metadata($id);
	$size = is_string($file) && is_file($file) && is_readable($file) ? filesize($file) : false;
	$size = is_int($size) && $size > 0 ? $size : null;
	return ['id' => $id, 'url' => $url, 'filesize' => $size, 'filesize_human' => null === $size ? 'Não disponível' : size_format($size), 'width' => $meta['width'] ?? '?', 'height' => $meta['height'] ?? '?'];
}

function ecolens_get_external_info($url){
	$url = esc_url_raw($url, ['http', 'https']);
	if (!$url) return false;
	$head = wp_safe_remote_head($url, ['timeout'=>2, 'redirection'=>2]);
	$size = null;
	if(!is_wp_error($head)) {
		$status = wp_remote_retrieve_response_code($head);
		$length = (string) wp_remote_retrieve_header($head, 'content-length');
		if ($status >= 200 && $status < 300 && ctype_digit($length) && (float) $length <= PHP_INT_MAX && (int) $length > 0) {
			$size = (int) $length;
		}
	}
	return ['id' => 0, 'url' => $url, 'filesize' => $size, 'filesize_human' => null === $size ? 'Não disponível' : size_format($size), 'width' => '?', 'height' => '?'];
}
