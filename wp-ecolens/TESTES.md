# Verificação do WP EcoLens

## Evidência de uso — versão 1.0.1

- [x] Instalação e ativação relatadas pelo autor, com captura do plugin ativo.
- [x] Auditorias de páginas e posts demonstradas em capturas anteriores.
- [x] Página de teste do projeto Valorant com três imagens: 274 KB, 77 KB e 106 KB, com uma acima da meta de 200 KB.
- [x] Conteúdo sem imagens relatado pelo autor. O print da aba de posts vazia também mostrou que o relatório anterior permanecia na tela; esse comportamento motivou a correção.
- [x] Tela de configuração exibindo 200 KB.
- [x] Impressão em PDF gerada pelo autor na versão anterior; havia cartões divididos entre páginas.

Esses resultados não comprovam as funções acrescentadas na 1.0.2.

## Verificações executadas — código da versão 1.0.2

- [x] Sintaxe JavaScript: `node --check wp-ecolens/assets/admin.js`.
- [x] Testes de estado da interface com jQuery e AJAX simulados: `node tests/admin-state.test.cjs`, executado na raiz do repositório.
- [x] Paginação solicitando a página correta.
- [x] Resposta antiga de auditoria ignorada após troca de aba.
- [x] Resposta antiga do CrUX ignorada após troca de aba.
- [x] Mensagens distintas para lista de posts vazia e conteúdo sem imagens.
- [x] Imagem exatamente no limite classificada dentro da meta; tamanho ausente classificado sem dados.
- [x] Título escapado, protocolo de link inseguro removido e métricas ausentes sem NaN.
- [x] Fluxo de retorno de salvamento da meta e liberação do botão.

Os testes usam respostas simuladas: não executam WordPress, o banco de dados, a conversão de imagem ou as APIs Google. O ambiente de preparação não disponibilizou PHP/WordPress; não foi executado `php -l` nem teste de integração PHP. Não há matriz de compatibilidade validada.

## Roteiro manual — versão 1.0.2

Registre WordPress, PHP, GD/Imagick, navegador e dispositivo utilizados. Execute primeiro em uma instalação de desenvolvimento.

### Atualização e permissões

- [ ] Atualizar a 1.0.1 pelo ZIP; ativar sem erro e abrir o painel.
- [ ] Confirmar que a meta previamente salva foi preservada.
- [ ] Confirmar que assinante/editor sem `manage_options` não acessa a tela nem executa as seis ações AJAX.
- [ ] Rejeitar nonce ausente ou inválido em cada ação.
- [ ] Confirmar que rascunhos, IDs inexistentes e posts protegidos por senha não são auditados.

### Listas, busca e relatório

- [ ] Criar mais de 50 conteúdos e chegar ao último pela paginação.
- [ ] Buscar um conteúdo que estava além dos primeiros 50 itens.
- [ ] Alternar rapidamente páginas, posts e configurações durante consultas lentas; nenhum relatório antigo deve reaparecer.
- [ ] Auditar imagem destacada, anexo, imagem em `<img src>` e URL repetida.
- [ ] Comparar o tamanho de imagem local com o arquivo do servidor.
- [ ] Testar imagem externa sem Content-Length, com 404 e com HEAD rejeitado: apresentar sem dados.
- [ ] Salvar 100 KB, reabrir o painel e conferir a classificação após nova auditoria.
- [ ] Rejeitar valor vazio, negativo, decimal, abaixo de 10 e acima de 100.000 no servidor.
- [ ] Simular falha de salvamento; não exibir sucesso indevido.

### Cópias WebP

- [ ] Converter JPEG e PNG estáticos. Confirmar redução, visual, transparência quando aplicável e metadados na biblioteca.
- [ ] Conferir que arquivo original e conteúdo publicado não mudaram.
- [ ] Repetir a conversão da mesma origem: reutilizar a cópia existente.
- [ ] Confirmar que cópia maior é descartada sem criar anexo vazio.
- [ ] Rejeitar APNG, GIF, SVG, WebP de entrada, arquivo externo e arquivo acima dos limites.
- [ ] Em servidor sem WebP, apresentar mensagem sem alterar a origem.
- [ ] Testar falha de gravação e duas solicitações simultâneas do mesmo anexo.

### PageSpeed, CrUX e carbono

- [ ] Configurar chaves válidas e analisar URL pública em celular e computador.
- [ ] Comparar peso, notas e métricas com a resposta Google do mesmo teste.
- [ ] Conferir que o peso total não é confundido com a soma das imagens locais.
- [ ] Testar cota excedida, chave inválida, timeout e resposta incompleta.
- [ ] Conferir cache e limite de um minuto entre novas consultas PageSpeed.
- [ ] Sem dados CrUX, não emitir classificação de CWV nem mostrar dados de outra origem como se fossem da página.
- [ ] Com CrUX, conferir LCP, INP, CLS, percentil 75 e datas do período.
- [ ] Conferir a fórmula SWDM v4: para 1.000.000 bytes, o resultado é 0,1482 gCO₂e nas hipóteses documentadas. Peso ausente deve resultar em estimativa indisponível.
- [ ] Conferir lista de falhas automáticas de acessibilidade e aviso de que não certifica WCAG.

### Interface e impressão

- [ ] Usar teclado e testar foco dos controles.
- [ ] Conferir celular e tela estreita.
- [ ] Imprimir relatório de imagens com várias páginas; os cartões não devem ser divididos e os controles devem ficar ocultos.
- [ ] Conferir que outras telas administrativas não carregam CSS ou JS do EcoLens.
