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
        'hashh' => ['type' => 'int', 'default' => '', 'comment' => '高位信息'],
        'hashl' => ['type' => 'int', 'default' => '', 'comment' => '低位信息'],
    ];
    public static array $index = [
        'PRIMARY' => ['fields' => ['id'], 'comment' => '主键'],
        'frame_file_id' => ['fields' => ['file_id']],
        'frame_hashh' => ['fields' => ['hashh']],
        'frame_hashl' => ['fields' => ['hashl']],
    ];
}
