<?php
/**
 * bootstrap.php
 *
 * Fixe l'erreur "date(): It is not safe to rely on the system's timezone settings(...)"
 * sur les environnements Docker.
 *
 * Fournis le trait initializer contenant des méthodes d'entretient.
 *
 * @author    Nicolas DUPRE
 * @release   23/10/2017
 * @version   1.0.0
 * @package   Index
 */

date_default_timezone_set('Europe/Paris');


trait initializer
{
    /**
     * Nettoie le dossier spécifié de manière recursive.
     * @param string $path Chemin vers le dossier à parcourir et nettoyer.
     */
    protected static function cleanseDir($path, $exclude)
    {
        $dir = scandir($path);

        foreach ($dir as $key => $file) {
            if (!in_array($file, $exclude)) {
                $full_path = $path . '/' . $file;
                if (is_dir($full_path)) {
                    self::cleanseDir($full_path, $exclude);
                    rmdir($full_path);
                } else {
                    unlink($full_path);
                }
            }
        }
    }

    /**
     * Copy de manière recursive la source vers le dossier de destination.
     *
     * @param string $srcPath   Fichier ou dossier source.
     * @param string $destPath  Dossier de destination. Etant récursive, ça ne peut être un fichier.
     * @param array $exclude    Nom de fichiers ou dossiers à exclure du processus.
     *
     * @return bool
     */
    protected static function recursiveCopy($srcPath, $destPath, $exclude = [])
    {
        $basename = basename($srcPath);

        // Traitement dans le cas exceptionnelle path/*
        if ($basename === '*') {
            // Vaut pour /.
            $basename = '.';
            $srcPath = str_replace('*', '.', $srcPath);
        }


        if (!in_array($basename, $exclude)) {
            if (is_file($srcPath)) {
                copy($srcPath, $destPath . '/' . $basename);
                return true;
            } else if (is_dir($srcPath)) {
                $fullDestPath = $destPath;
                /**
                 * Si on ne pointe pas le contenu, copie du dossier
                 */
                if ($basename !== '.') {
                    // Création du (sous-)dossier cible
                    mkdir($destPath . '/' . $basename, 0755);
                    $fullDestPath = $destPath . '/' . $basename;
                }

                // Lecture du dossier
                if ($dh = opendir($srcPath)) {
                    while ($file = readdir($dh)) {
                        // Ne pas traiter les référence . et .. dans le dossier parcouru.
                        if (preg_match("/^[.]{1,2}$/", $file)) continue;

                        // Copie :
                        self::recursiveCopy($srcPath . '/' . $file, $fullDestPath, $exclude);
                    }
                }

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
