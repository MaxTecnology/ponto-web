# Implementação do MVP — Sistema de Ponto Web

Este documento resume o que foi construído no MVP conforme as especificações de `doc/`.

## Visão Geral
- Stack: Laravel 12 (PHP 8.4), Blade + Livewire 3, Tailwind via Vite.
- Autenticação padrão Laravel (Breeze não instalado, mas estrutura compatível).
- Perfis: `colaborador`, `rh_manager`, `admin`.
- Tabela `users` expandida com `role` e `deactivated_at` (controle de acesso e bloqueio de login).
- Timezone: dados em UTC, exibição em America/Maceio.

## Fluxos do Colaborador
- **Bater Ponto (`/ponto`)**: coleta tipo, observação, geolocalização (obrigatória), fingerprint e device info detalhado. Backend valida intervalo mínimo configurável em `settings`.
- **Meu Espelho (`/meu-espelho`)**: visão mensal com batidas em horário local, total trabalhado e formulário de solicitação de ajuste (criar/editar/remover pendentes). Tabela “Minhas Solicitações” mostra status e comentários do RH.
- **Perfil (`/perfil`)**: atualizar nome e alterar senha com verificação da senha atual.

## Fluxos do RH
- **Dashboard (`/rh/ponto`)**: filtros reativos (período, usuário, tipo, flags) e tabela com dados ricos (geo + link Google Maps, device info). Export mantém filtros (`/rh/export`).
- **Ajustes (`/rh/ajustes`)**: lista pendentes, aprovar/rejeitar com comentário (audit registrado, status refletido no espelho do colaborador).
- **Fechamento (`/rh/fechamento`)**: resumo de pendências, fechamento lógico (histórico guardado em `settings` com nome do executor) e acesso rápido ao CSV.

## Fluxos do Admin
- **Gerência (`/admin/users`)**:
  - Criar usuário (nome, e-mail, senha, perfil).
  - Editar nome/e-mail/perfil dos existentes.
  - Desativar/reativar contas (exibe data de desativação). Contas desativadas não fazem login.

## Seeds e Contas de Teste
Executar `php artisan migrate --seed` (via Sail: `./vendor/bin/sail artisan migrate --seed`). Contas padrão com senha `secret123`:
- `admin@example.com` — admin
- `rh@example.com` — rh_manager
- `colab@example.com` — colaborador

## Regras Específicas
- Geolocalização obrigatória para registrar batida.
- Intervalo mínimo entre batidas configurável (`settings.key = ponto`, campo `min_interval_minutes`).
- Links inválidos são redirecionados conforme o perfil (admin → `/admin/users`, RH → `/rh/ponto`, colaborador → `/ponto`, convidados → `/login`).

## Estrutura de Dados
- Tabelas: `punches`, `adjust_requests`, `holidays`, `settings` conforme `doc/DB_SCHEMA.md`.
- `punches.device_info` inclui SO, navegador, categoria, tela, idiomas, brands, `hardware_concurrency` e `device_memory`.
- `adjust_requests.audit` guarda eventos (edição/aprovação/rejeição) com timestamp ISO.

## Testes
- `tests/Feature/PontoFeatureTest.php`: cobre batidas, geolocalização negada, filtros RH, aprovação de ajuste, export CSV, RBAC e intervalo mínimo.

## Comandos Úteis
```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=UserRoleSeeder
./vendor/bin/sail artisan test
./vendor/bin/sail npm run dev
```

Para visualizar rapidamente o ambiente: `./vendor/bin/sail up -d` seguido dos comandos acima.

---
 Atualize este documento sempre que ampliar o escopo (ex.: cadastro avançado, workflows adicionais ou integrações externas).
