# Rotas e Proteções

## Middleware padrão
- Todas as rotas abaixo exigem `auth`.

## Colaborador
- `GET /ponto` → Livewire `Ponto\BaterPonto` (bater ponto).
- `GET /meu-espelho` → Livewire `Ponto\MeuEspelho` (espelho mensal).

## RH
- `GET /rh/ponto` → Livewire `Rh\Dashboard` (lista/filtra batidas). `can:view-rh`
- `GET /rh/ajustes` → Livewire `Rh\Ajustes` (aprovar/rejeitar). `can:view-rh`
- `GET /rh/fechamento` → Livewire `Rh\Fechamento`. `can:view-rh`
- `GET /rh/export` → export CSV (respeita filtros atuais). `can:view-rh`

## Admin simples
- `GET /admin/users` → Livewire `Admin\UserRoles` (CRUD de usuários + config `min_interval_minutes`). Proteger com `role=admin` (gate `manage-roles`).
- Rotas visitantes e fallback redirecionam usuário autenticado conforme perfil (admin → `/admin/users`, RH → `/rh/ponto`, colaborador → `/ponto`).
