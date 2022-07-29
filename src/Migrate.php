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

use Minifw\DB\Driver;
use Org\Snje\Videocmp\Table\Vari;

abstract class Migrate
{
    protected Driver $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    abstract public function migrate() : void;

    abstract public static function getDBCfg() : array;

    abstract public static function getSql() : array;

    public static function applyAll(Driver $driver)
    {
        $dataVersion = 0;
        $tables = $driver->getTables();
        if (in_array('vari', $tables)) {
            $system = Vari::get()->getVari('system');
            $dataVersion = $system['data_version'];
        }

        while (true) {
            $classname = __NAMESPACE__ . '\\Migrate\\Migrate_' . $dataVersion;
            if (!class_exists($classname) || !is_subclass_of($classname, self::class)) {
                return;
            }

            App::get()->print('正在迁移数据: migrate_' . $dataVersion);

            $obj = new $classname($driver);
            $obj->migrate();

            $system = Vari::get()->getVari('system');
            $dataVersion = $system['data_version'];
        }
    }
}
