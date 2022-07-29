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

use Minifw\Common\Exception;
use Minifw\Common\File as CommonFile;
use Minifw\Common\FileUtils;
use Minifw\DB\TableInfo;
use Org\Snje\Videocmp\Command;
use Org\Snje\Videocmp\Migrate as MigrateMigrate;
use Org\Snje\Videocmp\Table\Vari;

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
            'remove' => [
                'comment' => '删除最后一个迁移工具',
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
        $system = Vari::get()->getVari('system');

        $old = [];
        if ($system['data_version'] > 0) {
            $oldClass = 'Org\\Snje\\Videocmp\\Migrate\\Migrate_' . ($system['data_version'] - 1);
            if (!class_exists($oldClass) || !is_subclass_of($oldClass, MigrateMigrate::class)) {
                throw new Exception('上一版本信息未找到');
            }
            $old = $oldClass::getDBCfg();
        }

        $dir = new CommonFile(APP_ROOT . '/src/Table');
        $list = $dir->ls('.php');

        $sqlList = [];
        $dbCfg = [];
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
        }

        $tpl = file_get_contents(APP_ROOT . '/config/tpl/migrate.class.tpl');
        $vars = [
            'classname' => 'Migrate_' . $system['data_version'],
            'year' => date('Y'),
            'version' => $system['data_version']
        ];

        foreach ($vars as $name => $value) {
            $tpl = str_replace('${' . $name . '}', $value, $tpl);
        }

        $path = APP_ROOT . '/src/Migrate/Migrate_' . $system['data_version'] . '.php';
        $file = new CommonFile($path);
        $file->putContent($tpl);

        $prefix = APP_ROOT . '/config/migrate/migrate_' . $system['data_version'];
        $file = new CommonFile($prefix . '_db.json');
        $file->putContent(json_encode($dbCfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        $file = new CommonFile($prefix . '_sql.json');
        $file->putContent(json_encode($sqlList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        Vari::get()->setVari('system', ['data_version' => $system['data_version'] + 1]);
    }

    protected function doApply(array $options, array $input) : void
    {
        MigrateMigrate::applyAll($this->driver);
    }

    protected function doRemove()
    {
        $system = Vari::get()->getVari('system');
        if ($system['data_version'] <= 0) {
            throw new Exception('还未创建迁移工具');
        }

        $classname = 'Org\\Snje\\Videocmp\\Migrate\\Migrate_' . ($system['data_version'] - 1);
        if (!class_exists($classname) || !is_subclass_of($classname, MigrateMigrate::class)) {
            throw new Exception('迁移工具未找到');
        }

        $path = APP_ROOT . '/src/Migrate/Migrate_' . ($system['data_version'] - 1) . '.php';
        if (file_exists($path)) {
            unlink($path);
        }

        $prefix = APP_ROOT . '/config/migrate/migrate_' . ($system['data_version'] - 1);
        $files = ['_db.json', '_sql.json'];
        foreach ($files as $file) {
            $full = $prefix . $file;
            if (file_exists($full)) {
                unlink($full);
            }
        }

        Vari::get()->setVari('system', ['data_version' => $system['data_version'] - 1]);
    }
}
