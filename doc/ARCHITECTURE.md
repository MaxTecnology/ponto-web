# Arquitetura e Convenções

## Stack
- Laravel 12 (PHP 8.3+), Blade + **Livewire 3**.
- **MySQL** via Laravel Sail ou Docker Compose (serviço `mysql`, porta 3306).
- **Redis** apenas provisionado (não usar no MVP).
- Autenticação padrão Laravel (login/logout e middleware `auth`); opção de gerar scaffold Breeze somente para ambientes que precisarem, mas não é requisito.
- Tailwind (via Vite, configuração padrão do projeto).

## RBAC
- Coluna `role` em `users` com valores: `colaborador`, `rh_manager`, `admin`.
- Gate `view-rh`: permite acesso às rotas RH a usuários com role `rh_manager` **ou** `admin`.

## Timezone
- Armazenar **UTC** no banco.
- Exibir em `America/Maceio` nas views (helpers).

## Estrutura (esperada após geração)
```
app/
  Models/{Punch.php, AdjustRequest.php, Holiday.php, Setting.php}
  Policies/ (se necessário)
  Livewire/
    Ponto/{BaterPonto.php, MeuEspelho.php}
    Rh/{Dashboard.php, Ajustes.php, Fechamento.php}
config/
database/
  migrations/
  seeders/
resources/
  views/
    livewire/
      ponto/{bater-ponto.blade.php, meu-espelho.blade.php}
      rh/{dashboard.blade.php, ajustes.blade.php, fechamento.blade.php}
  layouts/app.blade.php
routes/web.php
tests/Feature/
```

## Dependências a instalar
- `composer install` (já inclui `livewire/livewire` e ferramentas dev como Breeze opcional).
- `npm install && npm run build` (ou `npm run dev`).
- Garantir `.env` com `DB_CONNECTION=mysql` e fuso `APP_TIMEZONE=America/Maceio`.

## Painel Administrativo
- Rota `/admin/users` com Livewire exibindo:
  - Cadastro de usuários (nome, e-mail, senha temporária, role).
  - Edição de nome/e-mail/role.
  - Desativação/reativação (`deactivated_at`).
  - Configurações operacionais (`settings.key = ponto`) como intervalo mínimo entre batidas.
