# Arquitetura e Convenções

## Stack
- Laravel 11 (PHP 8.3), Blade + **Livewire 3**.
- **MySQL** via Laravel Sail (serviço `mysql`, porta 3306).
- **Redis** apenas provisionado (não usar no MVP).
- **Laravel Breeze** para autenticação (e-mail/senha). Manter 2FA preparado, porém desativado.
- Tailwind (o que vier com o Breeze).

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
- `composer require livewire/livewire`
- `composer require laravel/breeze --dev` + `php artisan breeze:install blade`
- `npm install && npm run build` (ou `npm run dev`)
- Garantir `.env` com `DB_CONNECTION=mysql` e fuso `APP_TIMEZONE=America/Maceio`.
