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

abstract class Command extends ConsoleCommand
{
    protected ?Driver $driver = null;
    protected App $app;

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
        $argv = ['-'];

        if (!empty($global['database'])) {
            $argv[] = '-db';
            $argv[] = $global['database'];
        }

        if (!empty($global['config'])) {
            $argv[] = '-c';
            $argv[] = $global['config'];
        }

        $argv[] = 'help';

        $this->app = App::get($argv);
        $this->driver = $this->app->getDriver();
    }

    protected function doHelp() : void
    {
        echo $this->parser->getManual() . "\n";
    }
}
