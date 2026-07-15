<?php

namespace App\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ViteInertiaPage
{
    /**
     * Chemin Vite (resources/js/pages/…) aligné sur le disque (casse réelle).
     *
     * Sous Windows, le système de fichiers est insensible à la casse, mais le manifest
     * Vite (construit en local ou en CI) lui l'est. On résout donc toujours la casse
     * canonique en parcourant les répertoires niveau par niveau.
     */
    public static function scriptPath(?string $component): ?string
    {
        if ($component === null || $component === '') {
            return null;
        }

        $relative = str_replace('\\', '/', $component).'.vue';
        $base = resource_path('js/pages');

        $canonical = self::resolveCaseSensitivePath($base, $relative);
        if ($canonical !== null) {
            return 'resources/js/pages/'.$canonical;
        }

        // Recherche large (chemins très imbriqués ou réorganisés).
        if (! is_dir($base)) {
            return 'resources/js/pages/'.$relative;
        }

        $target = strtolower($relative);
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'vue') {
                continue;
            }
            $path = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            if (strtolower($path) === $target) {
                return 'resources/js/pages/'.$path;
            }
        }

        return 'resources/js/pages/'.$relative;
    }

    /**
     * Reconstruit le chemin en remplaçant chaque segment par la version réellement
     * présente sur disque (sensible à la casse). Renvoie null si un segment manque.
     */
    private static function resolveCaseSensitivePath(string $base, string $relative): ?string
    {
        if (! is_dir($base)) {
            return null;
        }
        $segments = array_values(array_filter(explode('/', $relative), fn ($s) => $s !== ''));
        if ($segments === []) {
            return null;
        }

        $currentDir = $base;
        $resolved = [];
        foreach ($segments as $segment) {
            $entries = @scandir($currentDir);
            if ($entries === false) {
                return null;
            }
            $match = null;
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (strcasecmp($entry, $segment) === 0) {
                    $match = $entry;
                    break;
                }
            }
            if ($match === null) {
                return null;
            }
            $resolved[] = $match;
            $currentDir = $currentDir.DIRECTORY_SEPARATOR.$match;
        }

        return implode('/', $resolved);
    }
}
