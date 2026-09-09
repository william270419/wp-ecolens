# Histórico de mudanças

## 1.0.2

### Adicionado

- Paginação de páginas e posts com 20 itens por página e busca no servidor.
- Criação manual de cópias WebP de JPEG/PNG estáticos locais, com qualidade 80 e original preservado.
- Integração opcional PageSpeed Insights/Lighthouse: peso transferido, desempenho e verificações automáticas de acessibilidade.
- Integração opcional CrUX: LCP, INP e CLS de campo por URL e dispositivo, quando disponíveis.
- Estimativa SWDM v4 a partir do peso do carregamento, com hipóteses documentadas.
- Cache dos relatórios externos e limitação de novas chamadas PageSpeed.
- Testes de estado da interface com respostas AJAX simuladas.

### Corrigido

- Relatório anterior permanecendo ao trocar de aba.
- Respostas atrasadas sobrescrevendo o estado de outra aba.
- Ausência de mensagem específica para listas sem conteúdos.
- Salvamento que podia informar sucesso sem verificar o valor persistido.
- Layout de impressão ajustado para ocultar controles e evitar dividir cartões.

### Organização

- PHP, CSS e JavaScript separados em arquivos próprios.
- README com capturas do projeto pessoal Valorant identificadas como versão 1.0.1.
- Documentação separando evidências anteriores, testes simulados e validações pendentes da nova versão.

## 1.0.1

- Organização do snippet original como plugin instalável.
- Atualização da autoria e do endereço do projeto para o perfil atual do autor.
- Validação da meta e das requisições, tratamento de tamanhos desconhecidos e escape de resultados.
- Instalação, ativação e uso inicial demonstrados pelo autor em WordPress.

## 1.0.0

- Versão inicial do auditor de imagens, utilizada como snippet.
