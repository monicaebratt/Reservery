<?php

return [
    // RF11: además de la notificación dentro de la app, enviar correo
    'notificar_por_correo' => env('RESERVERY_NOTIFICAR_CORREO', true),

    // Consola para probar la API desde la web. Es una herramienta de desarrollo:
    // apagada para los clientes; se activa solo para la sustentación.
    'consola_api' => env('RESERVERY_CONSOLA_API', false),
];
