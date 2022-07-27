<?php

/*
 * Copyright (C) ${year} Yang Ming <yangming0116@163.com>
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

namespace Org\Snje\Videocmp\Migrate;

use Minifw\Common\Exception;
use Minifw\Console\Utils;
use Minifw\DB\TableUtils;
use Minifw\DB\TableInfo;
use Org\Snje\Videocmp\Migrate as Base;

class ${classname} extends Base
{
     public static function getDBCfg() : array
    {
        $dbJson = file_get_contents(APP_ROOT . '/config/migrate/migrate_${version}_db.json');

        return json_decode($dbJson, true);
    }

    public static function getSql() : array
    {
        $sqlJson = file_get_contents(APP_ROOT . '/config/migrate/migrate_${version}_sql.json');

        return json_decode($sqlJson, true);
    }

    public function migrate() : void
    {
        $sqlList = self::getSql();
        $dbCfg = self::getDBCfg();
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
            $this->driver->commit();
        } catch (Exception $ex) {
            Utils::printException($ex);
            $this->driver->rollback();
        }
    }
}
