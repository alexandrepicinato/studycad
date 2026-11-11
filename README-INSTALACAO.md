# StudyCad Studio Didático — 1.0.1

Versão de estudo construtivo para instalação direta em hospedagem `public_html/`. Implementação nova com HTML/CSS/JavaScript puro e PHP 8.1+; **não é o binário WebAssembly original nem reproduz todos os recursos de CADCraft Rust**. Sem Node, npm, CDN ou MySQL.

## Instalação

1. Extraia o **conteúdo desta pasta** diretamente em `public_html/` (ou em uma subpasta vazia na `public_html/`). Não coloque a própria pasta entre o domínio e o arquivo `index.php`.
2. Abra o domínio via **HTTPS** e carregue `index.php`.
3. A planta exemplo será gerada automaticamente e salva no armazenamento local do navegador.
4. Para salvar projetos também no servidor: abra **Sincronização no servidor** (painel direito), crie administrador com senha forte de no mínimo 10 caracteres. **Faça a configuração imediatamente após publicar**, pois até o primeiro cadastro qualquer visitante com acesso à aplicação pode tentar assumir o administrador. Se não precisar da sincronização, pode desativar `api.php` na hospedagem.
5. Abra a conta em outro dispositivo para listar e carregar os projetos enviados. O envio é manual e não é edição simultânea colaborativa.
6. No Chrome/Edge/Android, use o menu do navegador para **Instalar aplicativo** quando servido por HTTPS.

## Ferramentas

- Etapas didáticas: terreno, edificação, programa de necessidades, visualização da obra por etapas (terreno, fundações conceituais, lajes, paredes, esquadrias e cobertura).
- Dimensionamento paramétrico com terrenos, recuos, largura/profundidade, pavimentos (1–8), pé-direito, telhado plano/duas águas, cômodos configuráveis.
- Partição automática retangular de áreas conforme pesos típicos e ambientes informados. A distribuição inicial é **conceitual**: não garante conectividade, acessibilidade, recuos legais nem dimensionamento normativo.
- Editor SVG 2D: arrastar/redimensionar ambientes, editar dimensões e nomenclatura, desenhar ambientes, paredes, adicionar portas/janelas por proximidade, medir, zoom, pan, selecionar, duplicar, excluir, desfazer/refazer.
- Vista 3D isométrica com câmera orbital por arrasto, zoom e pavimentos selecionáveis; reconstrução gráfica após as edições. Não é BIM/IFC nem CAD sólido.
- Salva automaticamente no navegador (localStorage). Importa/exporta JSON de projeto; exporta plantas 2D em SVG, DXF R12 simplificado, PNG e imagem da vista 3D.
- Persistência **opcional** no servidor via conta PHP (arquivo JSON), sessões, hash de senha, token CSRF e pasta `data/` negada por `.htaccess`. Não requer MySQL.
- PWA offline **somente após primeira instalação com HTTPS** e cache da interface. Recursos sincronizados exigem conexão.

## Segurança da hospedagem

- Exige Apache 2.4 com `AllowOverride` para regras `Require all denied`; a API tenta armazenar os arquivos fora de `public_html` (na pasta privada `.cadcraft-private-*` um nível acima). Se a hospedagem não permitir, usa `/data/`, que deve **não** estar acessível publicamente. Confirme com uma requisição ao caminho `/data/account.json` (deve responder `403` ou `404`, nunca conteúdo).
- Se o host não suporta `.htaccess` (ex.: Nginx), **configure manualmente a negação de todo `/data/`** antes de ativar sincronização. Caso não consiga, não ative armazenamento no servidor.
- Configure HTTPS, PHP 8.1+, JSON, sessões PHP e permissão de escrita na pasta acima de `public_html` para persistência externa ou, como alternativa, em `data/` e `data/projects/`. Os outros arquivos podem permanecer somente leitura.
- Realize backups de `.cadcraft-private-*` ou `data/` (conforme onde o servidor armazenar os arquivos) e dos arquivos JSON locais. Browser cache não é backup.
- O primeiro usuário que instalar a API torna-se administrador. Não exponha o endereço antes da configuração; considere proteção temporária por autenticação HTTP no painel de hospedagem.

## Limites e conformidade

**Este sistema produz estudos preliminares**, não projetos executivos, memoriais de cálculo ou plantas aprovadas. Não implementa DWG, IFC, análise estrutural, levantamento topográfico, aprovação legal, compatibilização BIM, projetos de instalações ou dimensionamentos conforme ABNT/NBR. O DXF contém entidades LINE/TEXT simplificadas, não objetos paramétricos. O sistema não importa DXF/DWG e não integra automaticamente o motor CAD original.

## CadCraft original

O usuário forneceu também o projeto open-source CADCraft Rust 0.6.2. Para preservar o material, o pacote complementar `extras/cadcraft-main.zip` contém o código-fonte original e as licenças MIT/Apache e atribuições; **esse código não é executável diretamente em uma hospedagem PHP**. A versão Rust WebAssembly exige compilar o frontend ou instalar a release oficial compilada separadamente, procedimento descrito no pacote anterior.

## Exemplos incluídos

- `exemplos/Residencia-Modelo.json`: exemplo de projeto de dois pavimentos, importável via botão **Importar**.
- `exemplos/Planta-Modelo.png`: imagem de referência da planta conceitual gerada.
- `RELATORIO-DE-VALIDACAO.txt`: testes executados e limitações de validação.

## Identidade StudyCad
Logo integrada ao cabeçalho, ícones PWA e título. Cache atualizado. As chaves de projetos e pastas privadas permanecem compatíveis com a versão anterior.
Para atualizar, substitua os arquivos do aplicativo e preserve data/ e pastas privadas. Reabra a página após a atualização do service worker.

## Abrir no CadCraft — 1.0.2
Após gerar ou renderizar, clique em **Abrir no CadCraft** no cabeçalho.
Baixe o DXF e use Arquivo → Abrir no CadCraft instalado, ou o seletor de
arquivos de uma versão web já compilada. Exporta o pavimento selecionado,
em 2D, com 1 unidade = 1 metro. Repita a exportação para os demais pavimentos.
O navegador não inicia o aplicativo local automaticamente. O código-fonte
original incluído em extras/ não é um editor web pronto para execução.
Faça também backup JSON: o DXF não contém o modelo paramétrico 3D e não
pode ser reimportado pelo StudyCad. Edições no CadCraft ficam no DXF.

## CadCraft integrado — 1.0.3
O botão Abrir no CadCraft carrega o editor WebAssembly incluído em cadcraft/
e envia automaticamente a planta 2D do pavimento selecionado. Não precisa
instalar nem compilar o CadCraft. Use hospedagem HTTP/HTTPS; não file://.
Voltar ao StudyCad mantém o editor carregado e suas edições.
Para enviar outra planta, use Exportar → Abrir planta atual no CadCraft.
Salve as edições CAD antes de enviar outra planta.
Salvar/exportar no CadCraft entrega o arquivo ao StudyCad e inicia download;
o botão Baixar última versão CAD permite repetir o download recebido.
As edições CAD não são convertidas em ambientes paramétricos StudyCad.
O modelo 3D didático não é transferido. 1 unidade DXF corresponde a 1 metro.
A primeira abertura baixa aproximadamente 21 MB; aguarde o carregamento.
Licenças originais preservadas dentro de cadcraft/.
