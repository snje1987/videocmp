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

class File extends Table
{
    protected function _prase(array $post, array $odata = []) : array
    {
        throw new Exception('非法操作');
    }
    public static string $tbname = 'file';
    public static array $status = [
        'rowid' => true,
        'comment' => '文件信息',
    ];
    public static array $field = [
        'id' => ['type' => 'int', 'autoIncrement' => true, 'comment' => 'ID'],
        'name' => ['type' => 'text', 'comment' => '文件名'],
        'path' => ['type' => 'text', 'comment' => '文件路径'],
        'size' => ['type' => 'text', 'comment' => '大小'],
        'sha' => ['type' => 'text', 'comment' => 'sha'],
        'frames' => ['type' => 'int', 'comment' => '总帧数'],
    ];
    public static array $index = [
        'PRIMARY' => ['fields' => ['id'], 'comment' => '主键'],
        'file_sha' => ['fields' => ['sha'], 'unique' => true],
        'size' => ['fields' => ['size']],
    ];
}
