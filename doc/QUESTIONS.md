# Perguntas de Alinhamento do Projeto

Este roteiro de perguntas resume pontos que aparecem nos demais documentos de `doc/`. Responda (ou delegue) cada bloco para validar decisões e manter a geração de código no rumo certo.

## Stack & Fundamentais
- [ ] Qual é a versão definitiva do Laravel/PHP? Permanecemos no Laravel 11 (com Breeze) ou migramos o MVP todo para Laravel 12 sem Breeze?

R= gostaria que decidisse pra mim o que seria melhor no projeto atual levando em consideracao que ja esta funcionado.

- [ ] Breeze continuará obrigatório? Se não, qual alternativa cobre autenticação básica e scaffolding de UI?

R= preciso de sua ajuda pra melhor escolha.

- [ ] Há alguma dependência adicional obrigatória além de Livewire 3 e Tailwind (por exemplo, pacote de fingerprint, libs JS específicas)?

R= Gostaria que me ajudasse tambem de acordo com o projeto.

## RBAC & Fluxos de Admin
- [ ] O MVP realmente inclui um painel `/admin/users` para CRUD completo de usuários ou apenas a troca de `role` mencionada em `ROUTES.md`?
R= sim gostaria do painel funcionando.
- [ ] Vamos introduzir o campo `deactivated_at` em `users`? Se sim, qual é o comportamento esperado nas telas e seeds?

R= preciso de sua ajuda pra melhor escolha

- [ ] Existe necessidade de logs/auditoria adicionais quando um admin altera papéis ou ativa/desativa alguém?

não precisamos desses log no momento.

## Consentimento, Geo e Privacidade
- [ ] A batida pode ocorrer sem geolocalização (como está em `CONSENT_LGPD.md`) ou deve ser bloqueada quando o consentimento é negado (como descreve `IMPLEMENTATION_NOTES.md`)?

R= preciso sempre capturar a geolocalizacao. Se acontecer de nao aceitar forçar permitir.

- [ ] Como registramos a retenção de dados sensíveis? Precisamos já definir um job/command para limpar GEO após 12 meses?

R= gostaria de sempre guardar.

- [ ] Quais campos de `device_info` e `geo` são obrigatórios para o MVP? Há limites de tamanho/precisão aceitos?

R= o que for necessario pra saber que o colaborador esta usando mesmo dispositivo.

## Banco de Dados & Seeds
- [ ] Qual chave usamos no `settings` para armazenar o intervalo mínimo (`min_interval_minutes`)? Vamos padronizar algo como `ponto_config` ou criar múltiplas chaves específicas?
R= essa parte seria bom a gente ter dentro do painel do admin a opcao pra ser modificado e ser da forma que vc achar melhor.

- [ ] Há seeds adicionais obrigatórios (ex.: usuário admin) além dos citados em `DB_SCHEMA.md`?

R= creio que não

- [ ] Precisamos de migrações extras (ex.: índices para filtros RH) além do que já está listado?

R= o que temos ja esta bom de inicio.

## Livewire & UX
- [ ] Qual é a expectativa para o modal de consentimento? Ele aparece a cada sessão, a cada batida ou até o usuário aceitar?
R= o que vc achar melhor.
- [ ] Vamos exibir tooltips/legendas para as flags (`sem_geo`, `ip_novo`, `fingerprint_novo`) no dashboard e espelho?

R= sim, porem gostaria que fosse uma mensagem em portugues.

- [ ] Qual é o comportamento desejado quando o navegador bloqueia APIs (clipboard, geolocalização, etc.)? Exibimos mensagens específicas?

R= gostaria de uma mensagem informativa.

## Fechamento, Ajustes & Export
- [ ] O “fechamento lógico” precisa gerar algum registro persistido (ex.: tabela dedicada) ou basta uma flag em `settings`?

R= gostaria que me ajudasse com essa parte pois ainda se encontra inlogica p[ra mim e pouco efetiva

- [ ] A exportação CSV deve incluir ajustes aprovados na linha (ex.: campos extras) ou somente batidas?

R= batidas está muito bom

- [ ] Qual é a regra de cálculo para totalizações no espelho? Precisamos considerar tolerâncias/intervalos ainda não definidos?

R= gostaria que me ajudasse com essa logica tambem.

## Testes & Qualidade
- [ ] Como medir a exigência de “filtros RH respondendo em < 2s”? Usaremos seed com volume fixo ou dados sintéticos nos testes automatizados?

R= gostaria que me ajudasse com essa logica tambem.

- [ ] Existem testes de unidade/integrados além dos de feature listados em `TEST_PLAN.md` que são mandatórios (ex.: policies, helpers de timezone)?

R= gostaria que me ajudasse com essa logica tambem.

- [ ] Vamos usar cobertura mínima ou métricas específicas (CI, relatórios) para aprovar o MVP?

R= gostaria que me ajudasse com essa logica tambem.

## Deploy & Operação
- [ ] O checklist de deploy será seguido com Docker puro ou pretendemos usar Sail em produção? Há restrições de infraestrutura?
- [ ] Já temos política de backup definida (armazenamento, criptografia) ou precisamos detalhar antes do go-live?
- [ ] Existe SLA ou monitoramento obrigatório (ex.: Sentry, Uptime Kuma) que deve ser configurado desde o MVP?

## Roadmap Futuro
- [ ] Quais itens marcados como “futuro” (ex.: banco de horas, SSO, antifraude avançado) têm prioridade logo após o MVP?

R= gostaria que me ajudasse com essa logica tambem.

- [ ] Há integrações externas planejadas que demandam hooks no código desde agora (webhooks, APIs)?

R= gostaria que me ajudasse com essa logica tambem.

- [ ] Como vamos versionar e comunicar evoluções das especificações (`doc/*.md`) para não quebrar a automação do gerador?

R= gostaria que me ajudasse com essa logica tambem.