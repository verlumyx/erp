<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Logos públicos de las plataformas
    |--------------------------------------------------------------------------
    |
    | Ruta pública (relativa a /public) donde el comando `streaming:download-logos`
    | guarda los SVG y desde donde el frontend los sirve como `logo_url`.
    |
    */

    'logo_path' => 'images/streaming',

    /*
    |--------------------------------------------------------------------------
    | Catálogo de plataformas de streaming
    |--------------------------------------------------------------------------
    |
    | Servicios precargados automáticamente en cada empresa nueva. El `slug`
    | corresponde al identificador de Simple Icons (https://simpleicons.org)
    | usado para descargar el logo y construir el `logo_url`.
    |
    */

    'default_services' => [
        ['name' => 'Netflix',         'slug' => 'netflix',       'max_profiles' => 5],
        ['name' => 'Disney+',         'slug' => 'disneyplus',    'max_profiles' => 7, 'download' => false],
        ['name' => 'Max',             'slug' => 'max',           'max_profiles' => 5],
        ['name' => 'Prime Video',     'slug' => 'primevideo',    'max_profiles' => 6, 'download' => false],
        ['name' => 'Spotify',         'slug' => 'spotify',       'max_profiles' => 6],
        ['name' => 'YouTube Premium', 'slug' => 'youtube',       'max_profiles' => 5],
        ['name' => 'Crunchyroll',     'slug' => 'crunchyroll',   'max_profiles' => 4],
        ['name' => 'Apple TV+',       'slug' => 'appletv',       'max_profiles' => 6],
        ['name' => 'Paramount+',      'slug' => 'paramountplus', 'max_profiles' => 6],
    ],

];
