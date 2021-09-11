<?php

/*
 * Make phar by https://github.com/DaisukeDaisuke/BehaviorPackLoader/blob/master/build/make-phar.php
 * (C) DaisukeDaisuke(https://github.com/DaisukeDaisuke)
 */

$file_phar = "build/CoralReef.phar";
if (file_exists($file_phar)) {
    echo "Phar file already exists, overwriting...";
    echo PHP_EOL;
    Phar::unlinkArchive($file_phar);
}

$files = [];
$dir = getcwd() . DIRECTORY_SEPARATOR;

$exclusions = ["github", ".gitignore", "composer.json", "composer.lock", "build", ".git", ".idea", "LICENCES", "deploy.bat", "docker-compose.yml"];

foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir)) as $path => $file) {
    $bool = true;
    foreach ($exclusions as $exclusion) {
        if (strpos($path, $exclusion) !== false) {
            $bool = false;
        }
    }

    if (!$bool) {
        continue;
    }

    if ($file->isFile() === false) {
        continue;
    }
    if (isset($argv[2]) && isset($argv[3])) {
        $fileString = file_get_contents($path);
        str_replace($argv[2], $argv[3], $fileString);
        file_put_contents($path, $fileString);
    }
    $files[str_replace($dir, "", $path)] = $path;
}

if (isset($argv[1])) {
    str_replace('master', 'dev', $argv[1]);
    $yaml = yaml_parse_file('plugin.yml');
    $yaml['version'] = $yaml['version'] . '.' . $argv[1];
    yaml_emit_file('plugin.yml', $yaml);
}

echo "Compressing..." . PHP_EOL;
$phar = new Phar($file_phar, 0);
$phar->startBuffering();
$phar->setSignatureAlgorithm(\Phar::SHA1);
$phar->buildFromIterator(new \ArrayIterator($files));
$phar->setStub("<?php __HALT_COMPILER(); ?>");
$phar->compressFiles(Phar::GZ);
$phar->stopBuffering();
echo "end." . PHP_EOL;
