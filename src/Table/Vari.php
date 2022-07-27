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

namespace Org\Snje\Videocmp\Table;

use Minifw\Common\Exception;
use Minifw\DB\Table;

class Vari extends Table
{
    public function setVari(string $type, array $values)
    {
        $path = APP_ROOT . '/config/vari/' . $type . '.php';
        if (!file_exists($path)) {
            return;
        }
        $cfg = require($path);

        $old = $this->query()->select(['name', 'value'])->where(['type' => $type])->hash()->exec();

        foreach ($values as $name => $value) {
            if (!isset($cfg[$name])) {
                continue;
            }

            $oldVal = $old[$name] ?? null;
            $oldVal = self::decodeValue($cfg[$name], $oldVal);

            $value = self::encodeValue($cfg[$name], $value, $oldVal);
            if ($value !== null) {
                if (isset($old[$name])) {
                    $this->query()->update(['name' => $name, 'value' => $value])->where(['type' => $type, 'name' => $name])->exec();
                } else {
                    $this->query()->insert(['type' => $type, 'name' => $name, 'value' => $value])->exec();
                }
            }
        }
    }

    public function getVari(string $type) : array
    {
        $path = APP_ROOT . '/config/vari/' . $type . '.php';
        if (!file_exists($path)) {
            return [];
        }
        $cfg = require($path);

        $hash = $this->query()->select(['name', 'value'])->where(['type' => $type])->hash()->exec();

        $result = [];

        foreach ($cfg as $name => $info) {
            if (isset($hash[$name])) {
                $result[$name] = self::decodeValue($info, $hash[$name]);
            } elseif (isset($info['default'])) {
                $result[$name] = $info['default'];
            } else {
                $result[$name] = '';
            }
        }

        return $result;
    }

    public static function encodeValue(array $cfg, $value, $oldVal = null)
    {
        if (empty($cfg['type'])) {
            throw new Exception('数据不合法');
        }

        switch ($cfg['type']) {
            case self::TYPE_INT:
                $value = (int) $value;
                if ($value === $oldVal) {
                    return null;
                }

                return $value;
            case self::TYPE_STRING:
                $value = (string) $value;
                if ($value === $oldVal) {
                    return null;
                }

                return $value;
            case self::TYPE_RAW:
                $value = (string) $value;
                if ($value === $oldVal) {
                    return null;
                }

                return ['rich', (string) $value];
            default:
                throw new Exception('数据不合法');
        }
    }

    public static function decodeValue(array $cfg, $value)
    {
        if (empty($cfg['type'])) {
            throw new Exception('数据不合法');
        }
        switch ($cfg['type']) {
            case self::TYPE_INT:
                return (int) $value;
            case self::TYPE_STRING:
            case self::TYPE_RAW:
                return (string) $value;
            default:
                throw new Exception('数据不合法');
        }
    }

    protected function _prase(array $post, array $odata = []) : array
    {
        throw new Exception('非法操作');
    }

    ///////////////////////////////
    public static string $tbname = 'vari';
    public static array $status = [
        'rowid' => true,
        'comment' => '变量',
    ];
    public static array $field = [
        'id' => ['type' => 'int', 'autoIncrement' => true, 'comment' => 'ID'],
        'type' => ['type' => 'text', 'comment' => '类型'],
        'name' => ['type' => 'text', 'comment' => '名称'],
        'value' => ['type' => 'text', 'comment' => '值'],
    ];
    public static array $index = [
        'PRIMARY' => ['fields' => ['id'], 'comment' => '主键'],
        'var_name' => ['fields' => ['type', 'name']],
    ];
    public static array $typeCfg = [
        self::TYPE_INT => [],
        self::TYPE_STRING => [],
        self::TYPE_RAW => [],
    ];
    public const TYPE_INT = 1;
    public const TYPE_STRING = 2;
    public const TYPE_RAW = 3;
}
