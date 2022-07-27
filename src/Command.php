<?php

/*
 * Copyright (C) 2021 Yang Ming <yangming0116@163.com>
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

use Minifw\Console\Command as ConsoleCommand;
use Minifw\DB\Driver;
use Minifw\DB\Driver\Sqlite3;
use Minifw\DB\Query;

abstract class Command extends ConsoleCommand
{
    protected ?Driver $driver = null;
    protected Config $config;

    public static function getConfig() : array
    {
        $config = require(APP_ROOT . '/config/optionCfg.php');

        return [
            'oppositePrefix' => $config['oppositePrefix'],
            'global' => $config['global'],
            'actions' => [
                'help' => [
                    'comment' => '显示本信息',
                ],
                'config' => $config['actions']['config'],
            ]
        ];
    }

    protected function init(array $global)
    {
        if (!empty($global['database'])) {
            $config = [];
            $config['file'] = $global['database'];
            $this->driver = new Sqlite3($config);
        }

        if (!empty($global['config'])) {
            $configPath = $global['config'];
        } else {
            $configPath = DATA_DIR . '/config.json';
        }

        $this->config = new Config($configPath);
        if ($this->config->get('debug')) {
            define('DEBUG', 1);
        } else {
            define('DEBUG', 0);
        }

        if ($this->driver === null) {
            $database = $this->config->get('database');
            if (!empty($database)) {
                $config = [];
                $config['file'] = $database;
                $this->driver = new Sqlite3($config);
            }
        }

        if (!$this->driver !== null) {
            Query::setDefaultDriver($this->driver);
        }
    }

    protected function doHelp() : void
    {
        echo $this->parser->getManual() . "\n";
    }

    protected function doConfig($options, $input) : void
    {
        if (!empty($options['get'])) {
            $name = $options['get'];
            echo $this->config->show($name) . "\n";
        }
        if (!empty($options['set'])) {
            $pair = $options['set'];
            $this->config->set($pair[0], $pair[1])->save();
            echo $this->config->show($pair[0]) . "\n";
        }
    }
}
