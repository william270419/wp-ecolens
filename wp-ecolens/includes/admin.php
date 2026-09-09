<?php
if (!defined('ABSPATH')) { exit; }
function ecolens_admin_page() {
    if (!current_user_can('manage_options')) { return; }
    ?>
    <div class="wrap wia-wrap">
      <header class="wia-header">
        <div><h1>🌱 WP EcoLens <span class="wia-version">v<?php echo esc_html(ECOLENS_VERSION); ?></span></h1><p>Imagens, desempenho e acessibilidade</p></div>
        <button type="button" class="button" id="wia-print">Imprimir relatório</button>
      </header>
      <div class="wia-main-layout">
        <aside class="wia-sidebar">
          <nav class="wia-nav" aria-label="Seções do EcoLens">
            <button type="button" class="wia-tab active" data-target="page" aria-pressed="true">Páginas</button>
            <button type="button" class="wia-tab" data-target="post" aria-pressed="false">Artigos (Posts)</button>
            <button type="button" class="wia-tab" data-target="config" aria-pressed="false">Configurações</button>
          </nav>
          <div class="wia-info-box"><h2>Revise antes de substituir</h2><p>A compressão cria uma cópia WebP. Os arquivos originais e as imagens publicadas permanecem preservados.</p></div>
        </aside>
        <main class="wia-content">
          <section id="wia-browser" aria-label="Conteúdos publicados">
            <div class="wia-controls"><label for="wia-search">Buscar conteúdos publicados</label><input id="wia-search" type="search" placeholder="Digite parte do título ou conteúdo"></div>
            <div id="wia-list-status" role="status"></div>
            <ul class="wia-list" id="wia-list"></ul>
            <nav id="wia-pagination" aria-label="Paginação de conteúdos"></nav>
            <p class="description">A análise de página envia a URL pública selecionada ao Google PageSpeed Insights e, se configurado, ao CrUX. Nenhuma análise externa é iniciada automaticamente.</p>
            <label for="wia-strategy">Dispositivo da análise:</label>
            <select id="wia-strategy"><option value="mobile">Celular</option><option value="desktop">Computador</option></select>
          </section>
          <section id="wia-config" hidden>
            <h2>Configurações</h2>
            <form id="wia-config-form">
              <p><label for="wia-limit">Meta por imagem (KB)</label><br><input id="wia-limit" type="number" min="10" max="100000" step="1" required value="<?php echo esc_attr(get_option('ecolens_limit', 200)); ?>"></p>
              <p>1 KB = 1.024 bytes. A meta classifica os arquivos encontrados e não representa uma nota de desempenho.</p>
              <button type="submit" class="button button-primary" id="wia-save-config">Salvar meta</button>
              <p id="wia-save-msg" role="status"></p>
            </form>
            <h3>Conexão com o Google</h3>
            <p>PageSpeed: <?php echo defined('ECOLENS_GOOGLE_API_KEY') && ECOLENS_GOOGLE_API_KEY ? 'chave configurada.' : 'sem chave; sujeito à disponibilidade e cota pública.'; ?><br>
            CrUX: <?php echo defined('ECOLENS_CRUX_API_KEY') && ECOLENS_CRUX_API_KEY ? 'chave configurada.' : 'chave não configurada.'; ?></p>
            <p>As chaves são configuradas no wp-config.php, conforme o README. Não são exibidas no navegador. Sem conexão externa, a auditoria de imagens e a criação de cópias WebP continuam disponíveis.</p>
          </section>
          <section class="wia-results-area" id="wia-results" aria-label="Relatório de auditoria">
            <h2>Relatório de auditoria</h2><p id="wia-status-summary" role="status"></p>
            <div id="wia-viewport" aria-live="polite">Selecione um conteúdo para iniciar.</div>
          </section>
        </main>
      </div>
    </div>
    <?php
}
