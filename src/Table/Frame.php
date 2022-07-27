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

class Frame extends Table
{
    protected function _prase(array $post, array $odata = []) : array
    {
        throw new Exception('非法操作');
    }
    public static string $tbname = 'frame';
    public static array $status = [
        'rowid' => true,
        'comment' => '视频帧信息',
    ];
    public static array $field = [
        'id' => ['type' => 'int', 'autoIncrement' => true, 'comment' => 'ID'],
        'file_id' => ['type' => 'int', 'comment' => '文件ID'],
        'hash1' => ['type' => 'int', 'comment' => '帧信息1'],
        'hash2' => ['type' => 'int', 'comment' => '帧信息2'],
        'hash3' => ['type' => 'int', 'comment' => '帧信息3'],
        'hash4' => ['type' => 'int', 'comment' => '帧信息4'],
        'hash5' => ['type' => 'int', 'comment' => '帧信息5'],
        'hash6' => ['type' => 'int', 'comment' => '帧信息6'],
        'hash7' => ['type' => 'int', 'comment' => '帧信息7'],
        'hash8' => ['type' => 'int', 'comment' => '帧信息8'],
    ];
    public static array $index = [
        'PRIMARY' => ['fields' => ['id'], 'comment' => '主键'],
        'frame_file_id' => ['fields' => ['file_id']],
        'frame_hash1' => ['fields' => ['hash1']],
        'frame_hash2' => ['fields' => ['hash2']],
        'frame_hash3' => ['fields' => ['hash3']],
        'frame_hash4' => ['fields' => ['hash4']],
        'frame_hash5' => ['fields' => ['hash5']],
        'frame_hash6' => ['fields' => ['hash6']],
        'frame_hash7' => ['fields' => ['hash7']],
        'frame_hash8' => ['fields' => ['hash8']],
    ];
}
