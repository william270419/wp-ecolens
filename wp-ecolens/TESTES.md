# Roteiro de verificação no WordPress

Os itens abaixo são testes pendentes em uma instalação real do WordPress. Não representam resultados aprovados.

Use uma instalação de desenvolvimento e registre as versões de WordPress, PHP e navegador ao testar. Ative o log de erros somente nesse ambiente, se necessário.

## Ativação e permissões

- [ ] Instalar e ativar o ZIP sem erro fatal ou saída inesperada.
- [ ] Confirmar que o menu EcoLens Audit abre para um administrador.
- [ ] Confirmar que um usuário sem `manage_options` não acessa a tela nem executa as ações AJAX.
- [ ] Confirmar que as duas ações AJAX rejeitam nonce ausente ou inválido.

## Configuração

- [ ] Confirmar a meta inicial de 200 KB em uma instalação sem opção salva.
- [ ] Salvar 100 KB, auditar uma imagem maior que 100 KB e conferir o alerta sem recarregar a tela.
- [ ] Reabrir a tela e confirmar que a meta ficou salva.
- [ ] Rejeitar valor vazio, negativo, decimal, menor que 10 ou maior que 100.000 KB, inclusive em requisições diretas ao servidor.
- [ ] Confirmar que uma falha de salvamento não exibe mensagem de sucesso.

## Relatório

- [ ] Auditar conteúdo sem imagens e conferir a mensagem correspondente.
- [ ] Auditar conteúdo com imagem destacada, anexo de mídia e tag `<img src>`.
- [ ] Confirmar que endereços repetidos não geram cartões repetidos.
- [ ] Conferir manualmente o tamanho de uma imagem local com o arquivo no servidor.
- [ ] Conferir imagens abaixo, exatamente no limite e acima da meta.
- [ ] Conferir imagem externa com `Content-Length` válido.
- [ ] Conferir servidor que rejeita `HEAD`, retorna 404 ou omite `Content-Length`: mostrar “sem dados”.
- [ ] Conferir anexo sem arquivo local legível: mostrar “sem dados”.
- [ ] Usar título com `<`, `>`, aspas e `&`: exibir como texto, sem criar HTML.
- [ ] Simular falha de rede: retirar o estado de carregamento e liberar os botões.
- [ ] Confirmar que mudar a meta limpa o relatório anterior até uma nova auditoria.
- [ ] Confirmar que um ID inexistente ou um rascunho não gera uma auditoria publicada.

## Interface

- [ ] Alternar as abas, filtrar os itens e navegar com teclado.
- [ ] Conferir a interface em tela pequena.
- [ ] Conferir a prévia de impressão com resultados.
- [ ] Confirmar que outras telas administrativas não recebem os estilos do plugin.
