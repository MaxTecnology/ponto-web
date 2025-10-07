# Consentimento, LGPD e Privacidade

## Modal de consentimento (texto)
> Ao continuar, você autoriza a coleta de localização aproximada e informações do dispositivo para comprovar local de trabalho remoto e prevenir fraudes. Esses dados são acessíveis apenas ao RH/Admin e armazenados por até 12 meses (geolocalização), enquanto registros de ponto ficam por até 5 anos conforme política interna. Você pode negar; o registro será feito sem localização e marcado para revisão.

## Regras
- Salvar `geo_consent` por batida (boolean).
- Se negar: permitir batida; no RH, exibir flag `sem_geo`.
- Restringir visualização de GEO a `rh_manager`/`admin`.
- Configurar retenção (campo em `settings`) para limpeza futura (fora do MVP).

## Dados coletados
- Sempre: `ts_server`, `ip`, `user_agent`.
- Quando consentido: `geo {{lat, lon, accuracy_m}}`, `device_info`, `fingerprint_hash`.
