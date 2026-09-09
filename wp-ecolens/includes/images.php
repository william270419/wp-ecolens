<?php
if (!defined('ABSPATH')) { exit; }

/** Create a separate media item. Never replace the source file or page content. */
function ecolens_compress_image($id) {
    if (!current_user_can('upload_files') || !current_user_can('edit_post', $id)) {
        return new WP_Error('permission', 'Sem permissão para criar uma cópia desta imagem.');
    }
    $file = get_attached_file($id);
    $mime = get_post_mime_type($id);
    $uploads = wp_upload_dir();
    $real = is_string($file) ? realpath($file) : false;
    $base = realpath($uploads['basedir']);
    if (!$real || !$base || strpos(wp_normalize_path($real), trailingslashit(wp_normalize_path($base))) !== 0 || !is_readable($real)) {
        return new WP_Error('file', 'Arquivo local não disponível na pasta de uploads.');
    }
    if (!in_array($mime, ['image/jpeg', 'image/png'], true) || get_post_type($id) !== 'attachment') {
        return new WP_Error('format', 'A criação de WebP aceita imagens JPEG e PNG estáticas da biblioteca.');
    }
    $dimensions = wp_getimagesize($real);
    $before = filesize($real);
    if (!$dimensions || $dimensions['mime'] !== $mime || $before <= 0 || $before > 20 * 1024 * 1024 || $dimensions[0] * $dimensions[1] > 25000000) {
        return new WP_Error('size', 'Imagem inválida ou acima do limite de 20 MB / 25 megapixels.');
    }
    // APNG would lose its animation in conversion. Check PNG chunks before IDAT.
    if ($mime === 'image/png') {
        $handle = fopen($real, 'rb');
        if (!$handle) { return new WP_Error('file', 'Não foi possível ler a imagem.'); }
        fseek($handle, 8);
        $static = false;
        while (!feof($handle)) {
            $chunk = fread($handle, 8);
            if (strlen($chunk) !== 8) { break; }
            $length = unpack('Nlength', substr($chunk, 0, 4))['length'];
            $type = substr($chunk, 4, 4);
            if ($type === 'acTL') { break; }
            if ($type === 'IDAT') { $static = true; break; }
            if ($length > $before || fseek($handle, $length + 4, SEEK_CUR) !== 0) { break; }
        }
        fclose($handle);
        if (!$static) { return new WP_Error('animation', 'PNG animado ou estrutura não reconhecida; original preservado.'); }
    }
    if (!wp_image_editor_supports(['mime_type' => 'image/webp'])) {
        return new WP_Error('webp', 'O editor de imagens deste servidor não oferece suporte a WebP.');
    }
    $fingerprint = hash_file('sha256', $real);
    $previous = get_post_meta($id, '_ecolens_webp', true);
    if (is_array($previous) && ($previous['hash'] ?? '') === $fingerprint && isset($previous['id']) && get_post_status($previous['id']) === 'inherit' && is_file((string) get_attached_file($previous['id']))) {
        return ['id' => $previous['id'], 'url' => wp_get_attachment_url($previous['id']), 'edit' => get_edit_post_link($previous['id'], 'raw'), 'message' => 'A cópia WebP desta imagem já existe na biblioteca.'];
    }
    $lock = 'ecolens_compress_lock_' . $id;
    if (!add_option($lock, time(), '', false)) {
        return new WP_Error('busy', 'Esta imagem já está sendo processada. Se a execução foi interrompida, consulte o README.');
    }
    $saved_path = '';
    $attachment = 0;
    try {
        if (!empty($uploads['error'])) { return new WP_Error('uploads', 'A pasta de uploads não está disponível.'); }
        $editor = wp_get_image_editor($real);
        if (is_wp_error($editor)) { return new WP_Error('editor', 'Não foi possível abrir a imagem no editor do WordPress.'); }
        $quality = $editor->set_quality(80);
        if (is_wp_error($quality)) { return new WP_Error('quality', 'O editor não aceitou a qualidade de compressão.'); }
        $name = wp_unique_filename($uploads['path'], sanitize_file_name(pathinfo($real, PATHINFO_FILENAME) . '-ecolens-' . wp_generate_password(8, false, false) . '.webp'));
        $result = $editor->save(trailingslashit($uploads['path']) . $name, 'image/webp');
        if (is_wp_error($result)) { return new WP_Error('save', 'Não foi possível criar a cópia WebP.'); }
        $saved_path = $result['path'];
        $after = filesize($saved_path);
        if ($after <= 0 || $after >= $before) { return new WP_Error('no_gain', 'A cópia não ficou menor. Ela foi descartada e o original foi preservado.'); }
        $attachment = wp_insert_attachment([
            'post_mime_type' => 'image/webp',
            'post_title' => get_the_title($id) . ' — EcoLens WebP',
            'post_status' => 'inherit',
        ], $saved_path, 0, true);
        if (is_wp_error($attachment)) { $attachment = 0; return new WP_Error('media', 'Não foi possível registrar a cópia na biblioteca.'); }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        wp_update_attachment_metadata($attachment, wp_generate_attachment_metadata($attachment, $saved_path));
        update_post_meta($attachment, '_wp_attachment_image_alt', get_post_meta($id, '_wp_attachment_image_alt', true));
        update_post_meta($id, '_ecolens_webp', ['id' => $attachment, 'hash' => $fingerprint]);
        return ['id' => $attachment, 'url' => wp_get_attachment_url($attachment), 'edit' => get_edit_post_link($attachment, 'raw'), 'message' => 'Cópia criada: ' . size_format($before) . ' → ' . size_format($after) . '. Economia de ' . round(100 * (1 - $after / $before), 1) . '%. Substitua a imagem no conteúdo se desejar.'];
    } finally {
        if (!$attachment && $saved_path && is_file($saved_path)) { wp_delete_file($saved_path); }
        delete_option($lock);
    }
}

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
