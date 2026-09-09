# Histórico de mudanças

## 1.0.1 - Preparação do repositório

- Adicionada a abertura `<?php` para usar o código como arquivo de plugin.
- Atualizada a referência de GitHub para o perfil `william270419`.
- Nome e textos ajustados para descrever a auditoria de imagens disponível hoje.
- Inclusão explícita do jQuery somente na tela do plugin.
- Proteção por nonce no salvamento da configuração, mantendo a checagem de permissão.
- Validação da meta de tamanho no navegador e no servidor (inteiro entre 10 e 100.000 KB).
- Validação do conteúdo solicitado antes da auditoria.
- Escape de títulos, endereços e informações exibidos dinamicamente no relatório.
- URLs de imagens limitadas a HTTP e HTTPS; mantido `wp_safe_remote_head` para requisições externas.
- Arquivos sem tamanho consultável recebem a classificação “sem dados”.
- Respostas HTTP malsucedidas não são utilizadas como tamanho de imagem.
- “Otimizado” substituído por “dentro da meta”, para evitar uma conclusão além da análise realizada.
- Tratamento de falhas nas requisições e atualização da meta sem recarregar a página.
- Bloqueio temporário dos botões durante as operações para evitar relatórios concorrentes.
- Ajustes pontuais de rótulos, anúncios de estado e layout em telas menores.
- Documentação de instalação, uso, limitações e desenvolvimento com IA.

## 1.0.0 - Código inicial

- Interface administrativa com abas de páginas, posts e configuração.
- Consulta de imagens locais e externas, filtro de conteúdo e impressão.
- Meta de tamanho por imagem e relatório visual.
