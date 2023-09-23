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

use Minifw\Common\Exception;
use Minifw\Console\Utils;
use Minifw\DB\Driver;
use Minifw\DB\TableInfo;
use Minifw\DB\TableUtils;
use Org\Snje\Videocmp\Table\Vari;

class Migrate
{
    protected Driver $driver;
    const DATA_VERSION = 3;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function migrate($dataVersion) : void
    {
        $sqlList = [];
        $dbCfg = [];

        if ($dataVersion == 0) {
            $sqlList = self::getCurrent();
            $dbCfg = self::getDBCfg(self::DATA_VERSION - 1);
        } else {
            $sqlList = self::getSql($dataVersion);
            $dbCfg = self::getDBCfg($dataVersion);
        }

        $this->driver->begin();
        try {
            foreach ($sqlList as $sql) {
                $this->driver->exec($sql);
            }

            foreach ($dbCfg as $tbname => $cfg) {
                $info = TableInfo::loadFromArray($this->driver, $cfg);
                $diff = TableUtils::dbCmp($this->driver, $info);
                if (!$diff->isEmpty()) {
                    throw new Exception('数据迁移发生问题');
                }
            }

            if ($dataVersion == 0) {
                Vari::get()->setVari('system', ['data_version' => self::DATA_VERSION]);
            } else {
                Vari::get()->setVari('system', ['data_version' => $dataVersion + 1]);
            }
            $this->driver->commit();
        } catch (Exception $ex) {
            $this->driver->rollback();
            throw $ex;
        }
    }

    public static function getDBCfg($dataVersion) : array
    {
        $file = APP_ROOT . '/config/migrate/migrate_' . $dataVersion . '_db.json';
        $dbJson = file_get_contents($file);

        return json_decode($dbJson, true);
    }

    public static function getSql($dataVersion) : array
    {
        $file = APP_ROOT . '/config/migrate/migrate_' . $dataVersion . '_sql.json';
        $sqlJson = file_get_contents($file);

        return json_decode($sqlJson, true);
    }

    public static function getCurrent() : array
    {
        $file = APP_ROOT . '/config/migrate/current_sql.json';
        $sqlJson = file_get_contents($file);

        return json_decode($sqlJson, true);
    }

    public static function applyAll(Driver $driver)
    {
        $dataVersion = 0;
        $tables = $driver->getTables();
        if (in_array('vari', $tables)) {
            $system = Vari::get()->getVari('system');
            $dataVersion = $system['data_version'];
        }

        $obj = new self($driver);

        try {
            while (true) {
                if ($dataVersion == self::DATA_VERSION) {
                    break;
                }

                App::get()->print('正在迁移数据: migrate_' . $dataVersion);
                $obj->migrate($dataVersion);

                $system = Vari::get()->getVari('system');
                $dataVersion = $system['data_version'];
            }
        } catch (Exception $ex) {
            Utils::printException($ex);
        }
    }
}
