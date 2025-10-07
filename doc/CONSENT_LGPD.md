# Consentimento, LGPD e Privacidade

## Modal de consentimento (texto)
> Para registrar o ponto é obrigatório autorizar a coleta de localização aproximada e informações do dispositivo. Esses dados comprovam o local de trabalho remoto e ajudam a prevenir fraudes. Eles são acessíveis apenas ao RH/Admin e armazenados junto aos registros de ponto conforme política interna. Caso não autorize, não será possível concluir o registro.

## Regras
- Salvar `geo_consent` por batida (boolean).
- Se negar: bloquear a batida e exibir instrução informando que a localização é obrigatória.
- Restringir visualização de GEO a `rh_manager`/`admin`.
- Retenção: armazenar geolocalização e fingerprint junto ao registro de ponto enquanto o dado for necessário para fins trabalhistas (política interna registrada).

## Dados coletados
- Sempre: `ts_server`, `ip`, `user_agent`.
- Quando consentido: `geo {{lat, lon, accuracy_m}}`, `device_info`, `fingerprint_hash`.
