<?php

return [
    // Caminho relativo ao diretório "public" para exibir a logo customizada.
    // Exemplo: 'images/logo.svg'. Defina via APP_LOGO no .env.
    'logo_path' => env('APP_LOGO'),

    // Nome exibido ao lado da logo no cabeçalho.
    'display_name' => env('APP_BRAND', 'Sistema de Ponto G2A'),

    // Subtítulo opcional sob o nome (defina APP_BRAND_TAGLINE ou deixe nulo).
    'tagline' => env('APP_BRAND_TAGLINE'),
];
