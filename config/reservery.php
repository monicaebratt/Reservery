<?php

return [
    // Además de la notificación dentro de la app, enviar correo
    'notificar_por_correo' => env('RESERVERY_NOTIFICAR_CORREO', true),

    // Consola para probar la API desde la web
    'consola_api' => env('RESERVERY_CONSOLA_API', false),
];
