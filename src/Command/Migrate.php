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

use Minifw\Common\File as CommonFile;
use Minifw\Common\FileUtils;
use Minifw\DB\TableInfo;
use Org\Snje\Videocmp\Command;
use Org\Snje\Videocmp\Migrate as MigrateMigrate;

class Migrate extends Command
{
    public static function getConfig() : array
    {
        $actions = [
            'gen' => [
                'comment' => '生成迁移工具',
                'options' => [
                ]
            ],
            'apply' => [
                'comment' => '进行数据迁移',
                'options' => [
                ]
            ],
        ];

        $cfg = parent::getConfig();
        $cfg['actions'] = array_merge($actions, $cfg['actions']);

        return $cfg;
    }

    protected function doGen(array $options, array $input) : void
    {
        $old = MigrateMigrate::getDBCfg(MigrateMigrate::DATA_VERSION - 2);

        $dir = new CommonFile(APP_ROOT . '/src/Table');
        $list = $dir->ls('.php');

        $sqlList = [];
        $dbCfg = [];
        $current = [];
        foreach ($list as $file) {
            if ($file['dir']) {
                continue;
            }
            $classname = 'Org\\Snje\\Videocmp\\Table\\' . FileUtils::filename($file['name']);
            $obj = $classname::get();
            $newInfo = TableInfo::loadFromObject($obj);
            $dbCfg[$newInfo->tbname] = $newInfo->toArray();
            if (isset($old[$classname::$tbname])) {
                $oldInfo = TableInfo::loadFromArray($this->driver, $old[$classname::$tbname]);
            } else {
                $oldInfo = null;
            }

            $diff = $newInfo->cmp($oldInfo);
            if (!$diff->isEmpty()) {
                $trans = $diff->getSql();
                $sqlList = array_merge($sqlList, $trans);
            }
            $create = $newInfo->cmp(null);
            if (!$create->isEmpty()) {
                $trans = $create->getSql();
                $current = array_merge($current, $trans);
            }
        }

        if (empty($sqlList)) {
            echo '数据库结构无修改' . PHP_EOL;

            return;
        }

        $prefix = APP_ROOT . '/config/migrate/migrate_' . (MigrateMigrate::DATA_VERSION - 1);
        $file = new CommonFile($prefix . '_db.json');
        $file->putContent(json_encode($dbCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $file = new CommonFile($prefix . '_sql.json');
        $file->putContent(json_encode($sqlList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $file = new CommonFile(APP_ROOT . '/config/migrate/current_sql.json');
        $file->putContent(json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    protected function doApply(array $options, array $input) : void
    {
        MigrateMigrate::applyAll($this->driver);
    }
}
