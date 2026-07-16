<?php

/**
 * Crée les dossiers générés par Wayfinder (gitignored) pour éviter les erreurs
 * file_put_contents / FilesystemIterator sur un clone ou après nettoyage.
 */
$root = dirname(__DIR__);

foreach (['resources/js/actions', 'resources/js/routes', 'resources/js/wayfinder'] as $relative) {
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
        fwrite(STDERR, "Impossible de créer le répertoire : {$path}\n");
        exit(1);
    }
}
