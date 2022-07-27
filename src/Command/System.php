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

namespace Org\Snje\Videocmp\Command;

use Minifw\Console\Option;
use Minifw\DB\TableUtils;
use Org\Snje\Videocmp\Command;

class System extends Command
{
    public static function getConfig() : array
    {
        $actions = [
            'dbdiff' => [
                'comment' => '对比数据库与定义文件之间的差异',
                'options' => [
                    'apply' => [
                        'alias' => 'a',
                        'comment' => '是否把修改应用到数据库中',
                        'default' => false,
                        'paramType' => Option::PARAM_BOOL
                    ]
                ]
            ]
        ];

        $cfg = parent::getConfig();
        $cfg['actions'] = array_merge($actions, $cfg['actions']);

        return $cfg;
    }

    protected function doDbdiff(array $options, array $input) : void
    {
        $diffList = TableUtils::obj2dbCmpAll($this->driver, 'Org\\Snje\\Videocmp\\Table', APP_ROOT . '/src/Table');

        if ($options['apply']) {
            $this->driver->begin();
        }

        foreach ($diffList as $diff) {
            echo $diff->display() . "\n";

            if ($options['apply']) {
                $diff->apply($this->driver);
            }
        }

        if ($options['apply']) {
            $this->driver->commit();
        }
    }
}
