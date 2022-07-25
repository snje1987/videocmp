<?php

/*
 * Copyright (C) 2022 Yang Ming <yangming0116@163.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Org\Snje\Videocmp;

$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';

if (PHP_SAPI !== 'cli') {
    die('only can be used in cli mode.');
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('DATA_DIR')) {
    $dataDir = getenv("HOME");
    if (empty($dataDir)) {
        $dataDir = rtrim(sys_get_temp_dir(), '\\/');
    } else {
        $dataDir = rtrim($dataDir, '\\/');
    }

    $dataDir .= '/.videocmp';

    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0777, true);
    }
    define('DATA_DIR', $dataDir);
}

$app = new App();
$app->run($argv);
