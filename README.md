# WP EcoLens

**Auditor de imagens para WordPress.**

O EcoLens ajuda a encontrar imagens acima de uma meta de tamanho em páginas e posts. A consulta acontece no painel do WordPress e apresenta miniaturas, tamanho dos arquivos e dimensões quando disponíveis.

O objetivo é facilitar a identificação de arquivos que merecem revisão, pensando também em quem acessa sites com conexões limitadas. O plugin informa os resultados; a otimização das imagens é feita separadamente.

## Funcionalidades

- Listagem de até 50 páginas publicadas e 50 posts publicados.
- Filtro de texto sobre os itens carregados.
- Consulta individual das imagens associadas a cada conteúdo.
- Identificação da imagem destacada, das imagens anexadas ao conteúdo e dos endereços encontrados em tags `<img src>` no conteúdo salvo.
- Meta configurável por imagem, inicialmente de 200 KB, usando 1 KB = 1.024 bytes.
- Classificação em **acima da meta**, **dentro da meta** ou **sem dados**.
- Miniaturas, links dos arquivos e dimensões quando disponíveis.
- Impressão da interface e do relatório pela função de impressão do navegador.
- Acesso restrito a usuários com a permissão administrativa `manage_options`.

## Tecnologias

PHP, WordPress Plugin API, JavaScript, jQuery, AJAX, HTML e CSS. Para consultar arquivos externos, utiliza a WordPress HTTP API.

Não depende de Elementor, de serviços de IA ou de uma chave de API para funcionar.

## Instalação

1. Baixe o pacote ZIP do projeto.
2. Em uma instalação de desenvolvimento do WordPress, vá a **Plugins → Adicionar plugin → Enviar plugin**.
3. Selecione o ZIP, instale e ative o plugin.
4. Abra **EcoLens Audit** no menu administrativo.

Também é possível copiar a pasta do projeto para `wp-content/plugins/wp-ecolens/` e ativá-lo pelo painel. O arquivo `wp-ecolens.php` deve estar diretamente nessa pasta.

Se esta versão substituir um snippet usado no WPCode ou Code Snippets, desative o snippet antes de ativar o plugin para evitar funções duplicadas.

## Como usar

1. Na aba **Configurar Meta**, defina o tamanho máximo por imagem em KB e salve.
2. Escolha **Páginas** ou **Artigos (Posts)**.
3. Clique no botão de auditoria do conteúdo desejado.
4. Revise os arquivos acima da meta e aqueles cujo tamanho não pôde ser consultado.

Após mudar a meta, execute uma nova auditoria. Estar dentro do limite de tamanho não significa que a imagem ou a página esteja completamente otimizada.

## O que os resultados representam

Para arquivos locais da biblioteca de mídia, o plugin consulta o arquivo do anexo no servidor e seus metadados. Para outros endereços de imagem encontrados no conteúdo, tenta obter o tamanho por uma requisição HTTP `HEAD`, sem baixar o corpo do arquivo no servidor. As miniaturas exibidas no relatório são carregadas pelo navegador.

Quando a consulta falha ou o servidor não informa um tamanho válido, o resultado é **sem dados**. Essa condição não é tratada como uma imagem leve.

## Limitações atuais

- A análise usa o conteúdo armazenado no WordPress, sem abrir a página em um navegador para observar os arquivos efetivamente carregados.
- Imagens anexadas podem ser listadas mesmo que não apareçam na página publicada.
- Não percorre dados internos do Elementor, imagens de fundo em CSS, `srcset`, blocos dinâmicos ou conteúdo inserido por JavaScript.
- O tamanho do arquivo do anexo pode ser diferente do tamanho da versão responsiva ou comprimida entregue ao visitante.
- As consultas externas são sequenciais; conteúdos com muitas imagens externas podem demorar ou atingir limites do servidor.
- Não há paginação para acessar conteúdos além dos 50 itens de cada lista.
- Não comprime imagens, não calcula o peso total de uma página e não mede emissões de carbono, Core Web Vitals ou conformidade de acessibilidade.

## Desenvolvimento e uso de IA

Projeto pessoal de **William Marques**, desenvolvido com apoio do **ChatGPT** na criação e revisão do código. O repositório apresenta essa experiência de desenvolvimento assistido por IA de forma transparente.

A versão 1.0.1 organiza o código como plugin instalável e acrescenta ajustes de validação, proteção das requisições e apresentação dos resultados. As alterações estão descritas no [histórico de mudanças](CHANGELOG.md).

## Estado de validação

Esta versão precisa passar pelo teste de ativação e uso em uma instalação de desenvolvimento do WordPress. Não há uma matriz de compatibilidade validada de versões de WordPress e PHP. O [roteiro de verificação](TESTES.md) lista os cenários a conferir antes de usar em produção.

## Próximas melhorias

- Paginação das listas de páginas e posts.
- Cobertura de imagens de fundo e de dados do Elementor.
- Exportação dos resultados em CSV.
- Cache e processamento em lotes das consultas externas.
- Testes de integração com WordPress.

## Autor e licença

[William Marques](https://github.com/william270419).

Distribuído sob a licença GPLv2, declarada no código original. Consulte [LICENSE](LICENSE).
