# Perguntas de Alinhamento do Projeto

Este roteiro de perguntas resume pontos que aparecem nos demais documentos de `doc/`. Responda (ou delegue) cada bloco para validar decisões e manter a geração de código no rumo certo.

## Stack & Fundamentais
- [x] Qual é a versão definitiva do Laravel/PHP? Permanecemos no Laravel 11 (com Breeze) ou migramos o MVP todo para Laravel 12 sem Breeze?  
  **Decisão:** Padronizar em Laravel 12 + PHP 8.3; manter Breeze apenas como dependência dev opcional (não obrigatório em produção).

- [x] Breeze continuará obrigatório? Se não, qual alternativa cobre autenticação básica e scaffolding de UI?  
  **Decisão:** Autenticação padrão do Laravel (LoginRequest + middleware `auth`). Breeze pode ser usado só como scaffold quando necessário.

- [x] Há alguma dependência adicional obrigatória além de Livewire 3 e Tailwind (por exemplo, pacote de fingerprint, libs JS específicas)?  
  **Decisão:** Usar fingerprint e coleta de device info com Web APIs nativas; nenhuma dependência JS adicional é mandatória no MVP.

## RBAC & Fluxos de Admin
- [x] O MVP realmente inclui um painel `/admin/users` para CRUD completo de usuários ou apenas a troca de `role` mencionada em `ROUTES.md`?  
  **Decisão:** Sim, painel completo `/admin/users` com criação/edição/desativação e configuração global.
- [x] Vamos introduzir o campo `deactivated_at` em `users`? Se sim, qual é o comportamento esperado nas telas e seeds?  
  **Decisão:** Manter `deactivated_at` para bloqueio de login. Painel admin controla ativação/desativação; seeds podem deixar `null`.

- [x] Existe necessidade de logs/auditoria adicionais quando um admin altera papéis ou ativa/desativa alguém?  
  **Decisão:** Não é obrigatório no MVP; registrar somente flash messages. Avaliar `activitylog` numa fase futura.

## Consentimento, Geo e Privacidade
- [x] A batida pode ocorrer sem geolocalização (como está em `CONSENT_LGPD.md`) ou deve ser bloqueada quando o consentimento é negado (como descreve `IMPLEMENTATION_NOTES.md`)?  
  **Decisão:** Geolocalização obrigatória; se o usuário negar, a batida é bloqueada com instruções para habilitar o recurso.

- [x] Como registramos a retenção de dados sensíveis? Precisamos já definir um job/command para limpar GEO após 12 meses?  
  **Decisão:** Reter os dados junto ao registro de ponto conforme política interna; não há job de limpeza automática no MVP (documentar a decisão).

- [x] Quais campos de `device_info` e `geo` são obrigatórios para o MVP? Há limites de tamanho/precisão aceitos?  
  **Decisão:** Manter payload atual (UA raw, plataforma, tela, timezone, hardware, fingerprint e `{lat, lon, accuracy_m}`) suficiente para validar dispositivo; limitar `fingerprint_hash` a 64 chars (SHA-256).

## Banco de Dados & Seeds
- [x] Qual chave usamos no `settings` para armazenar o intervalo mínimo (`min_interval_minutes`)? Vamos padronizar algo como `ponto_config` ou criar múltiplas chaves específicas?  
  **Decisão:** Usar `settings.key = 'ponto'` com JSON `{ "min_interval_minutes": <int> }`, editável no painel admin.

- [x] Há seeds adicionais obrigatórios (ex.: usuário admin) além dos citados em `DB_SCHEMA.md`?  
  **Decisão:** Manter seeds atuais (`colaborador`, `rh_manager`, `admin`) e configurações mínimas; novos seeds só quando surgirem módulos extras.

- [x] Precisamos de migrações extras (ex.: índices para filtros RH) além do que já está listado?  
  **Decisão:** Estrutura atual atende; índices adicionais podem ser inseridos quando as métricas indicarem necessidade.

## Livewire & UX
- [x] Qual é a expectativa para o modal de consentimento? Ele aparece a cada sessão, a cada batida ou até o usuário aceitar?  
  **Decisão:** Modal por sessão de navegador; aceitando uma vez mantém na `sessionStorage`. Ao negar/cancelar, batida é cancelada.
- [x] Vamos exibir tooltips/legendas para as flags (`sem_geo`, `ip_novo`, `fingerprint_novo`) no dashboard e espelho?  
  **Decisão:** Sim, badges com mensagens em português e tooltips explicativos.

- [x] Qual é o comportamento desejado quando o navegador bloqueia APIs (clipboard, geolocalização, etc.)? Exibimos mensagens específicas?  
  **Decisão:** Exibir alertas específicos em português orientando como habilitar o recurso e não enviar requisição sem localização.

## Fechamento, Ajustes & Export
- [x] O “fechamento lógico” precisa gerar algum registro persistido (ex.: tabela dedicada) ou basta uma flag em `settings`?  
  **Decisão:** Armazenar lista em `settings.key = 'ponto_fechamentos'` com `{start,end,closed_at,closed_by}`. Avaliar tabela dedicada numa evolução.

- [x] A exportação CSV deve incluir ajustes aprovados na linha (ex.: campos extras) ou somente batidas?  
  **Decisão:** Apenas batidas; ajustes seguem em fluxos próprios/relatórios internos.

- [x] Qual é a regra de cálculo para totalizações no espelho? Precisamos considerar tolerâncias/intervalos ainda não definidos?  
  **Decisão:** Calcular duração bruta pareando `IN/OUT` e `BREAK_OUT/BREAK_IN`, sem tolerâncias adicionais por enquanto (documentar possível extensão futura).

## Testes & Qualidade
- [x] Como medir a exigência de “filtros RH respondendo em < 2s”? Usaremos seed com volume fixo ou dados sintéticos nos testes automatizados?  
  **Decisão:** Criar seed/tarefa de teste com ~5 usuários e milhares de batidas; medir com testes feature usando fake clock/temporizador (assert total_time < 2s).

- [x] Existem testes de unidade/integrados além dos de feature listados em `TEST_PLAN.md` que são mandatórios (ex.: policies, helpers de timezone)?  
  **Decisão:** Acrescentar testes para helpers de timezone, policies (`view-rh`, `manage-roles`) e validação de configuração (`settings`).

- [x] Vamos usar cobertura mínima ou métricas específicas (CI, relatórios) para aprovar o MVP?  
  **Decisão:** Objetivo inicial de cobertura funcional >70% em testes feature/críticos, reportado via `phpunit --coverage-text`; CI pode ser plugado depois.

## Deploy & Operação
- [x] O checklist de deploy será seguido com Docker puro ou pretendemos usar Sail em produção? Há restrições de infraestrutura?  
  **Decisão:** Produção com Docker Compose custom (baseado no Sail) atrás de Nginx reverse proxy; Sail restrito ao ambiente de desenvolvimento.
- [x] Já temos política de backup definida (armazenamento, criptografia) ou precisamos detalhar antes do go-live?  
  **Decisão:** Backup diário via `mysqldump` + armazenamento de `storage/app`, envio para repositório remoto (definido na operação).
- [x] Existe SLA ou monitoramento obrigatório (ex.: Sentry, Uptime Kuma) que deve ser configurado desde o MVP?  
  **Decisão:** Monitoramento mínimo com Uptime Kuma + logs diários (channel `daily`); Sentry opcional para roadmap.

## Roadmap Futuro
- [x] Quais itens marcados como “futuro” (ex.: banco de horas, SSO, antifraude avançado) têm prioridade logo após o MVP?  
  **Decisão:** Prioridades pós-MVP: (1) configurações avançadas (tolerância/janelas), (2) integrações com folha/ERP via CSV/API, (3) antifraude aprimorado (raio geográfico).

- [x] Há integrações externas planejadas que demandam hooks no código desde agora (webhooks, APIs)?  
  **Decisão:** Nenhuma integração obrigatória no MVP; deixar export CSV e estrutura para futuros webhooks.

- [x] Como vamos versionar e comunicar evoluções das especificações (`doc/*.md`) para não quebrar a automação do gerador?  
  **Decisão:** Manter `doc/CHANGELOG.md` com entradas datadas; cada alteração relevante deve atualizar o changelog e referenciar arquivos afetados.
