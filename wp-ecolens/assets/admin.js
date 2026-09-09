/* global jQuery, EcoLens */
jQuery(function ($) {
    'use strict';
    const root = $('.wia-wrap');
    const viewport = $('#wia-viewport');
    let type = 'page', page = 1, listToken = 0, reportToken = 0, timer;
    let listRequest, reportRequest, cruxRequest;
    const esc = value => String(value == null ? '' : value).replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    const safeUrl = value => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) ? url.href : ''; } catch (_) { return ''; } };
    const fmt = (value, unit = '', digits = 0) => typeof value === 'number' && Number.isFinite(value) ? value.toLocaleString('pt-BR', {maximumFractionDigits: digits}) + unit : 'Não disponível';
    const message = resp => resp && resp.data && resp.data.message ? resp.data.message : 'Não foi possível concluir a operação.';
    const request = (action, data = {}, timeout = 90000) => $.ajax({url: EcoLens.ajax, method: 'POST', data: {...data, action, nonce: EcoLens.nonce}, timeout});

    function clearReport(text = 'Selecione um conteúdo para iniciar.') {
        reportToken++;
        if (reportRequest) reportRequest.abort();
        if (cruxRequest) cruxRequest.abort();
        $('#wia-status-summary').empty();
        viewport.text(text);
    }
    function loadList() {
        if (type === 'config') return;
        const token = ++listToken;
        if (listRequest) listRequest.abort();
        $('#wia-list').empty();
        $('#wia-pagination').empty();
        $('#wia-list-status').text('Carregando conteúdos…');
        listRequest = request('ecolens_list', {type, page, search: $('#wia-search').val()});
        listRequest.done(resp => {
            if (token !== listToken) return;
            if (!resp || !resp.success) { $('#wia-list-status').text(message(resp)); return; }
            const data = resp.data;
            $('#wia-list-status').text(data.total ? `${data.total} conteúdo(s) encontrado(s).` : (type === 'post' ? 'Nenhum post publicado encontrado.' : 'Nenhuma página publicada encontrada.'));
            $('#wia-list').html(data.items.map(item => `<li><div class="wia-list-info"><strong>${esc(item.title || '(Sem título)')}</strong><a href="${esc(safeUrl(item.url))}" target="_blank" rel="noopener noreferrer">Ver site</a></div><div class="wia-row-actions"><button type="button" class="button wia-audit" data-id="${Number(item.id)}">Auditar imagens</button><button type="button" class="button wia-analyze" data-id="${Number(item.id)}">Analisar página (Google)</button></div></li>`).join(''));
            if (data.pages > 1) {
                $('#wia-pagination').html(`<button type="button" class="button" data-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}>Anterior</button><span>Página ${page} de ${data.pages}</span><button type="button" class="button" data-page="${page + 1}" ${page >= data.pages ? 'disabled' : ''}>Próxima</button>`);
            }
        }).fail((xhr, status) => { if (token === listToken && status !== 'abort') $('#wia-list-status').text('Falha ao carregar. Troque de aba ou refaça a busca para tentar novamente.'); });
    }
    root.on('click', '.wia-tab', function () {
        type = $(this).data('target');
        page = 1;
        clearTimeout(timer);
        listToken++;
        if (listRequest) listRequest.abort();
        clearReport();
        root.find('.wia-tab').removeClass('active').attr('aria-pressed', 'false');
        $(this).addClass('active').attr('aria-pressed', 'true');
        $('#wia-browser, #wia-results').prop('hidden', type === 'config');
        $('#wia-config').prop('hidden', type !== 'config');
        $('#wia-search').val('');
        if (type !== 'config') loadList();
    });
    $('#wia-search').on('input', () => {
        clearTimeout(timer);
        listToken++;
        if (listRequest) listRequest.abort();
        $('#wia-list, #wia-pagination').empty();
        clearReport();
        page = 1;
        timer = setTimeout(loadList, 300);
    });
    $('#wia-pagination').on('click', 'button[data-page]', function () {
        page = Number($(this).data('page'));
        clearReport();
        loadList();
    });
    $('#wia-strategy').on('change', () => clearReport('Dispositivo alterado. Execute uma nova análise.'));
    $('#wia-print').on('click', () => window.print());

    root.on('click', '.wia-audit', function () {
        clearReport('Consultando imagens…');
        const token = reportToken;
        const title = $(this).closest('li').find('strong').text();
        reportRequest = request('ecolens_get_images', {post_id: $(this).data('id')});
        reportRequest.done(resp => {
            if (token !== reportToken) return;
            if (!resp || !resp.success) { viewport.text(message(resp)); return; }
            const images = resp.data.images || [];
            const threshold = Number(resp.data.limit) * 1024;
            if (!images.length) { viewport.text(`Resultados para: ${title}. Nenhuma imagem encontrada neste conteúdo.`); return; }
            let heavy = 0, unknown = 0, bytes = 0;
            const cards = images.map(img => {
                const known = typeof img.filesize === 'number' && img.filesize > 0;
                const over = known && img.filesize > threshold;
                if (over) heavy++;
                if (!known) unknown++; else bytes += img.filesize;
                return `<article class="wia-card-img"><div class="wia-badge ${known ? (over ? 'bad' : 'good') : 'unknown'}">${known ? (over ? 'ACIMA DA META' : 'DENTRO DA META') : 'SEM DADOS'}</div><div class="wia-thumb-box"><a href="${esc(safeUrl(img.url))}" target="_blank" rel="noopener noreferrer"><img src="${esc(safeUrl(img.url))}" alt="Abrir imagem auditada" loading="lazy"></a></div><div class="wia-meta-box"><strong class="wia-size">${esc(img.filesize_human)}</strong><span>${esc(img.width)} × ${esc(img.height)} px</span>${img.compressible ? `<p><button type="button" class="button wia-compress" data-id="${Number(img.id)}">Criar cópia WebP</button></p><div class="wia-compress-status" role="status"></div>` : ''}</div></article>`;
            }).join('');
            $('#wia-status-summary').text(`${heavy} acima da meta · ${unknown} sem dados`);
            viewport.html(`<h3>Resultados para: ${esc(title)}</h3><p>Soma dos arquivos de imagem com tamanho conhecido: ${fmt(bytes / 1024, ' KB', 1)}. Este valor não é o peso total da página.</p><div class="wia-img-grid">${cards}</div>`);
        }).fail((xhr, status) => { if (token === reportToken && status !== 'abort') viewport.text('Falha na consulta de imagens. Tente novamente.'); });
    });
    root.on('click', '.wia-compress', function () {
        const button = $(this), status = button.closest('.wia-meta-box').find('.wia-compress-status');
        if (button.prop('disabled')) return;
        button.prop('disabled', true);
        status.text('Criando cópia WebP; o original será preservado…');
        request('ecolens_compress', {attachment_id: button.data('id')}).done(resp => {
            status.text(message(resp));
            if (resp && resp.success) {
                const link = safeUrl(resp.data.edit);
                if (link) status.append($('<p>').append($('<a>', {href: link, target: '_blank', rel: 'noopener noreferrer', text: 'Abrir cópia na biblioteca'})));
                button.text('Cópia disponível');
            } else button.prop('disabled', false);
        }).fail(xhr => {
            status.text(xhr.responseJSON ? message(xhr.responseJSON) : 'A resposta foi interrompida. Confira a biblioteca antes de tentar novamente.');
            button.prop('disabled', false);
        });
    });
    function fieldMetrics(resp) {
        if (!resp || !resp.success) return `<p>${esc(message(resp))}</p>`;
        const metrics = resp.data.metrics;
        const rows = [['LCP', 'largest_contentful_paint', ' ms', 2500], ['INP', 'interaction_to_next_paint', ' ms', 200], ['CLS', 'cumulative_layout_shift', '', 0.1]];
        const values = rows.map(row => metrics[row[1]]);
        const complete = values.every(value => typeof value === 'number');
        const good = complete && rows.every(row => metrics[row[1]] <= row[3]);
        const period = resp.data.period;
        const date = value => value ? `${value.day}/${value.month}/${value.year}` : 'não informado';
        return `<p>Dados da URL selecionada, percentil 75. ${period ? `Período: ${esc(date(period.firstDate))} a ${esc(date(period.lastDate))}.` : ''}</p><ul>${rows.map(row => `<li>${row[0]}: ${fmt(metrics[row[1]], row[2], 3)}</li>`).join('')}</ul><p>${complete ? (good ? 'As três métricas atendem aos limiares bons no período consultado.' : 'Uma ou mais métricas não atendem aos limiares bons no período consultado.') : 'Dados incompletos: sem classificação geral de Core Web Vitals.'}</p>`;
    }
    root.on('click', '.wia-analyze', function () {
        clearReport('Analisando a página com o Google. Isso pode levar cerca de um minuto…');
        const token = reportToken, id = $(this).data('id'), strategy = $('#wia-strategy').val();
        const title = $(this).closest('li').find('strong').text();
        reportRequest = request('ecolens_analyze_page', {post_id: id, strategy});
        reportRequest.done(resp => {
            if (token !== reportToken) return;
            if (!resp || !resp.success) { viewport.text(message(resp)); return; }
            const d = resp.data;
            const score = value => value === null ? 'Não disponível' : fmt(value * 100, '/100');
            viewport.html(`<h3>Análise de página: ${esc(title)}</h3><p>${strategy === 'mobile' ? 'Celular' : 'Computador'} · ${esc(d.fetched)} ${d.cached ? '· resultado em cache' : ''}</p><h3>Desempenho em laboratório — Lighthouse</h3><ul><li>Nota de desempenho: ${score(d.performance)}</li><li>Peso transferido observado no teste: ${d.bytes === null ? 'Não disponível' : fmt(d.bytes / 1024, ' KB', 1)}</li><li>LCP: ${fmt(d.lcp, ' ms')}</li><li>CLS: ${fmt(d.cls, '', 3)}</li><li>TBT: ${fmt(d.tbt, ' ms')} — não substitui INP.</li></ul><h3>Core Web Vitals — visitantes reais (CrUX)</h3><div id="wia-crux">Consultando dados de campo…</div><h3>Acessibilidade automática — Lighthouse</h3><p>Nota: ${score(d.accessibility)}. Esta análise não certifica conformidade WCAG; complemente com testes manuais.</p>${d.issues.length ? `<ul>${d.issues.map(issue => `<li>${esc(issue.title)} <small>(${esc(issue.id)})</small></li>`).join('')}</ul>` : '<p>Nenhuma falha automática listada nesta resposta. Isso não comprova ausência de barreiras.</p>'}<h3>Estimativa de carbono</h3><p>${fmt(d.carbon, ' gCO₂e por carregamento sem cache', 4)}</p><p>Modelo SWDM v4, intensidades fixas, sem desconto de hospedagem renovável. É uma estimativa baseada no peso do teste, não uma medição das emissões reais. Fórmula e fontes no README.</p><p>O teste representa um carregamento específico; recursos carregados depois de interações podem não entrar no peso informado.</p>`);
            cruxRequest = request('ecolens_crux', {post_id: id, strategy}, 30000);
            cruxRequest.done(result => { if (token === reportToken) $('#wia-crux').html(fieldMetrics(result)); }).fail((xhr, status) => {
                if (token === reportToken && status !== 'abort') $('#wia-crux').text('Consulta CrUX indisponível. Os dados de laboratório acima continuam válidos para esse teste.');
            });
        }).fail((xhr, status) => {
            if (token === reportToken && status !== 'abort') viewport.text(xhr.responseJSON ? message(xhr.responseJSON) : 'Análise interrompida ou tempo esgotado. Tente novamente mais tarde.');
        });
    });
    $('#wia-config-form').on('submit', function (event) {
        event.preventDefault();
        const value = Number($('#wia-limit').val());
        if (!Number.isInteger(value) || value < 10 || value > 100000) { $('#wia-save-msg').text('Informe um inteiro entre 10 e 100.000 KB.'); return; }
        const button = $('#wia-save-config');
        if (button.prop('disabled')) return;
        button.prop('disabled', true);
        $('#wia-save-msg').text('Salvando…');
        request('ecolens_save_config', {limit: value}).done(resp => {
            $('#wia-save-msg').text(resp && resp.success ? 'Meta salva.' : message(resp));
            if (resp && resp.success) clearReport('Meta atualizada. Execute uma nova auditoria.');
        }).fail(xhr => $('#wia-save-msg').text(xhr.responseJSON ? message(xhr.responseJSON) : 'Não foi possível salvar a meta.')).always(() => button.prop('disabled', false));
    });
    loadList();
});
