# Changelog — Sistema de Ponto Web

## 2025-01-15
- Alinhada stack oficial para Laravel 12 + autenticação padrão (Breeze opcional apenas para scaffolds).
- Documentadas rotas `/admin/users` e uso de `deactivated_at` e `settings.key = ponto`/`ponto_fechamentos`.
- Atualizado fluxo de consentimento: geolocalização obrigatória e mensagens em português orientando habilitação.
- Incluída referência a painel administrativo para ajustes operacionais (intervalo mínimo entre batidas).
- Ajustados testes de feature para refletir o bloqueio de batidas sem geolocalização e o painel de configurações.
- Implementado sistema de notificações instantâneas (toasts) em ações Livewire para dar feedback aos usuários.
- Melhorias para colaboradores: countdown para próxima batida, resumo mensal detalhado, alertas de risco, filtros nas solicitações e ações rápidas no espelho.
- Painéis do RH atualizados: dashboard com cards de resumo, alertas rápidos e destaque visual; ajustes com métricas e histórico; fechamento com timeline e atalhos.
- Criada esta trilha para registrar evoluções que impactam o gerador Codex e o time de desenvolvimento.
