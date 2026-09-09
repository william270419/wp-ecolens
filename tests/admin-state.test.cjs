// Tests for async UI state with a small jQuery/AJAX double, not a WordPress integration test.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const elements = new Map(), requests = [];
class Element {
    constructor() { this.value = ''; this.content = ''; this.props = {}; this.dataValues = {}; this.events = {}; }
    on(event, selector, callback) { this.events[event + ':' + (typeof selector === 'string' ? selector : '')] = callback || selector; return this; }
    text(value) { if (value === undefined) return this.content; this.content = String(value); return this; }
    html(value) { return this.text(value); }
    val(value) { if (value === undefined) return this.value; this.value = value; return this; }
    prop(name, value) { if (value === undefined) return this.props[name]; this.props[name] = value; return this; }
    data(name) { return this.dataValues[name]; }
    find(selector) { return this.children?.[selector] || $(selector); }
    closest() { return this.parent || this; }
    empty() { return this.text(''); }
    addClass() { return this; }
    removeClass() { return this; }
    attr() { return this; }
    append() { return this; }
}
function $(value) {
    if (typeof value === 'function') return value($);
    if (value instanceof Element) return value;
    if (!elements.has(value)) elements.set(value, new Element());
    return elements.get(value);
}
$.ajax = options => {
    const item = {options, doneHandlers: [], failHandlers: [], alwaysHandlers: [],
        done(fn) { this.doneHandlers.push(fn); return this; },
        fail(fn) { this.failHandlers.push(fn); return this; },
        always(fn) { this.alwaysHandlers.push(fn); return this; },
        abort() { this.aborted = true; this.failHandlers.forEach(fn => fn({}, 'abort')); },
        resolve(data) { this.doneHandlers.forEach(fn => fn(data)); this.alwaysHandlers.forEach(fn => fn()); }
    };
    requests.push(item);
    return item;
};
function fire(selector, event, delegated, data = {}) {
    const element = new Element(); element.dataValues = data;
    const title = new Element().text('Teste <script>');
    element.parent = new Element(); element.parent.children = {strong: title};
    const handler = $(selector).events[event + ':' + delegated];
    assert.equal(typeof handler, 'function');
    handler.call(element, {preventDefault() {}});
    return element;
}
const latest = action => [...requests].reverse().find(item => item.options.data.action === action);
const context = {jQuery: $, EcoLens: {ajax: '/admin-ajax.php', nonce: 'test'}, URL, console, setTimeout, clearTimeout, window: {print() {}}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../wp-ecolens/assets/admin.js'), 'utf8'), context);
assert.equal(latest('ecolens_list').options.data.type, 'page');
latest('ecolens_list').resolve({success:true,data:{total:61,pages:4,items:[{id:1,title:'Teste',url:'https://example.com'}]}});
assert.match($('#wia-pagination').content, /de 4/);
fire('#wia-pagination','click','button[data-page]',{page:4});
assert.equal(latest('ecolens_list').options.data.page,4);

fire('.wia-wrap','click','.wia-audit',{id:1});
const stale = latest('ecolens_get_images');
fire('.wia-wrap','click','.wia-tab',{target:'post'});
stale.resolve({success:true,data:{limit:200,images:[{url:'https://example.com/a.png',filesize:400000,filesize_human:'391 KB',width:1,height:1}]}});
assert.equal($('#wia-viewport').content,'Selecione um conteúdo para iniciar.');
latest('ecolens_list').resolve({success:true,data:{total:0,pages:0,items:[]}});
assert.equal($('#wia-list-status').content,'Nenhum post publicado encontrado.');
assert.equal($('#wia-pagination').content,'');

fire('.wia-wrap','click','.wia-tab',{target:'page'});
fire('.wia-wrap','click','.wia-audit',{id:1});
latest('ecolens_get_images').resolve({success:true,data:{limit:200,images:[
    {url:'javascript:alert(1)',filesize:204800,filesize_human:'200 KB',width:1,height:1},
    {url:'https://example.com/a.png',filesize:null,filesize_human:'Indisponível',width:'?',height:'?'}
]}});
assert.equal($('#wia-status-summary').content,'0 acima da meta · 1 sem dados');
assert.doesNotMatch($('#wia-viewport').content, /javascript:|<script>/);
assert.match($('#wia-viewport').content,/&lt;script&gt;/);
fire('.wia-wrap','click','.wia-audit',{id:2});
latest('ecolens_get_images').resolve({success:true,data:{limit:200,images:[]}});
assert.match($('#wia-viewport').content,/Nenhuma imagem encontrada neste conteúdo/);

$('#wia-strategy').val('mobile');
fire('.wia-wrap','click','.wia-analyze',{id:1});
latest('ecolens_analyze_page').resolve({success:true,data:{bytes:null,carbon:null,performance:null,accessibility:null,lcp:null,cls:null,tbt:null,issues:[],fetched:'test'}});
assert.match($('#wia-viewport').content,/Não disponível/);
assert.doesNotMatch($('#wia-viewport').content,/NaN|undefined/);
const staleCrux = latest('ecolens_crux');
fire('.wia-wrap','click','.wia-tab',{target:'config'});
staleCrux.resolve({success:true,data:{metrics:{largest_contentful_paint:1,interaction_to_next_paint:1,cumulative_layout_shift:0}}});
assert.equal($('#wia-viewport').content,'Selecione um conteúdo para iniciar.');
assert.equal($('#wia-crux').content,'');

$('#wia-limit').val('100');
fire('#wia-config-form','submit','');
latest('ecolens_save_config').resolve({success:true,data:{limit:100}});
assert.equal($('#wia-save-msg').content,'Meta salva.');
assert.equal($('#wia-save-config').prop('disabled'),false);
console.log('PASS: pagination, stale audit/CrUX responses, empty states, threshold boundary, escaping, unavailable metrics, save flow.');
