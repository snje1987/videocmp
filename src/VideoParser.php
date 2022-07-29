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
use Minifw\Common\File;
use Minifw\Common\Utils;
use Minifw\Console\Process;
use Org\Snje\Videocmp\Table\File as TableFile;
use Org\Snje\Videocmp\Table\Frame;
use Phar;

class VideoParser
{
    public function parse(File $file, string $pathShow, array $options)
    {
        try {
            $this->curFilePath = $file->getFsPath();
            $this->curFileShow = $pathShow;
            $this->curId = 0;
            $this->matchFrames = [];
            $this->options = $options;
            $this->buffer = '';
            $this->frameTotal = 0;
            $this->frameSaved = 0;
            $this->frameDelay = 0;

            if (!self::isVideo($this->curFilePath)) {
                throw new Exception($this->curFileShow . " \033[32mskip\033[0m");
            }

            $size = filesize($this->curFilePath);
            $this->app->print($this->curFileShow . " \033[32m" . Utils::showSize($size) . "\033[0m");

            $this->app->setStatus('获取文件信息...');
            $dbFile = $this->findFile($file, $size);
            if ($dbFile !== null) {
                if ($this->curId > 0) {
                    return [
                        'replace' => $dbFile['path'],
                    ];
                } else {
                    return [
                        'same' => $dbFile['path'],
                    ];
                }
            }

            if ($this->options['save'] && $this->curId <= 0) {
                throw new Exception('添加文件失败');
            }

            $this->app->setStatus('获取视频长度...');
            $videoInfo = [];
            $videoInfo['duration'] = $this->getDuration();

            $this->app->setStatus('获取视频边界...');
            $videoInfo['crop'] = $this->getCrop($videoInfo['duration']);
            $videoInfo['crop_str'] = ($videoInfo['crop'][1] - $videoInfo['crop'][0] + 1) . ':' . ($videoInfo['crop'][3] - $videoInfo['crop'][2] + 1) . ':' . $videoInfo['crop'][0] . ':' . $videoInfo['crop'][2];
            $this->app->print("视频边界: \033[32m" . $videoInfo['crop_str'] . "\033[0m");

            $this->app->setStatus('开始分析视频: ' . $this->curFileShow);
            $this->dumpVideo($videoInfo);

            if ($this->curId > 0) {
                TableFile::get()->query()->update([
                    'frames' => $this->frameSaved,
                ])->where(['id' => $this->curId])->exec();
            }

            $this->app->reset();

            return $this->parseMatch();
        } catch (Exception $ex) {
            $this->app->print($ex->getMessage());

            return null;
        } finally {
            $this->app->reset();
        }
    }

    protected function parseMatch() : ?array
    {
        if (empty($this->matchFrames)) {
            return null;
        }

        $fileIds = array_keys($this->matchFrames);

        $fileInfo = [];

        $count = count($fileIds);
        $step = 20;
        $times = ceil($count / $step);

        $table = TableFile::get();

        for ($i = 0; $i < $times;$i++) {
            $ids = [];
            for ($j = 0; $j < $step;$j++) {
                $index = $i * $step + $j;
                if ($index >= $count) {
                    break;
                }
                $ids[] = $fileIds[$index];
            }
            $files = $table->query()->select(['id', 'path', 'frames'])->where(['id' => ['in', $ids]])->hash()->exec();
            foreach ($files as $id => $info) {
                $fileInfo[$id] = $info;
            }
        }

        $matchFile = [];
        foreach ($fileInfo as $id => $info) {
            if (!isset($this->matchFrames[$id])) {
                continue;
            }

            $pecent = (double) (count($this->matchFrames[$id]) * 100 / $info['frames']);
            $pecent2 = (double) (count($this->matchFrames[$id]) * 100 * self::FRAME_STEP / $this->frameTotal);

            if ($pecent2 > $pecent) {
                $pecent = $pecent2;
            }

            if ($pecent > 10) {
                $matchFile[] = [
                    'path' => $info['path'],
                    'pecent' => round($pecent, 2),
                ];
            }
        }

        if (!empty($matchFile)) {
            return [
                'match' => $matchFile,
            ];
        }

        return null;
    }

    protected function dumpVideo(array $videoInfo) : void
    {
        $phar = __FILE__;
        if (strncmp($phar, 'phar://', 7) == 0) {
            $tmp = Phar::running(false);
            if (!empty($tmp)) {
                $phar = $tmp;
            }
        } else {
            $file = 'src/VideoParser.php';
            if (substr($phar, -1 * strlen($file)) == $file) {
                $phar = substr($phar, 0, strlen($phar) - strlen($file)) . 'index.php';
            }
        }

        $cmd = 'ffmpeg -i "' . $this->curFilePath . '" -vf crop=' . $videoInfo['crop_str'] . ' -s ' . self::FRAME_SIZE . 'x' . self::FRAME_SIZE . ' -pix_fmt gray -f image2pipe -vcodec rawvideo - | "' . PHP_BINARY . '" "' . $phar . '" dump --size ' . self::FRAME_SIZE;

        $process = new Process($cmd);
        $msgCache = '';
        $process->setCallback(function (string $name, int $stream, string $data) use (&$msgCache, $videoInfo) {
            if ($stream == 1) {
                $this->dumpFrame($data);
            } elseif ($stream == 2) {
                $msgCache .= $data;

                $arr = explode('frame=', $msgCache);
                $count = count($arr);

                if (!preg_match('/^(\\d+) fps=(\\d+) .*? time=(\\d+):(\\d+):(\\d+).\\d+ .*$/', $arr[$count - 1])) {
                    $msgCache = array_pop($arr);
                }

                $total = Utils::showDuration($videoInfo['duration']);

                foreach ($arr as $line) {
                    if (preg_match('/^\\s*(\\d+) fps=(\\d+) .*? time=(\\d+):(\\d+):(\\d+)\\.\\d+ .*/', $line, $matches)) {
                        $sec = $matches[3] * 3600 + $matches[4] * 60 + $matches[5];
                        $pecent = round($sec * 100 / $videoInfo['duration'], 2);

                        $line = 'frame=' . $matches[1] . ' fps=' . $matches[2] . ' time=' . $matches[3] . ':' . $matches[4] . ':' . $matches[5] . '/' . $total . ' ' . $pecent . '%';
                        $this->app->setStatus($line);
                    }
                }
            }
        })->run();
    }

    protected function dumpFrame(string $data) : void
    {
        $this->buffer .= $data;

        while (strlen($this->buffer) >= 8) {
            $this->frameTotal++;

            $hashs = [];
            for ($i = 0; $i < 4; $i++) {
                $hashs[] = ord($this->buffer[$i]) << 8 | ord($this->buffer[$i + 1]);
            }
            $this->buffer = substr($this->buffer, 8);
            $this->matchHash($hashs);

            if ($this->options['save']) {
                if ($this->frameTotal % self::FRAME_STEP == 0) {
                    $this->frameDelay++;
                }
                if ($this->frameDelay > 0) {
                    if (!self::checkFrame($hashs)) {
                        continue;
                    }

                    Frame::get()->query()->insert([
                        'file_id' => $this->curId,
                        'hash1' => $hashs[0],
                        'hash2' => $hashs[1],
                        'hash3' => $hashs[2],
                        'hash4' => $hashs[3],
                    ])->exec();
                    $this->frameSaved++;
                    $this->frameDelay--;
                }
            }
        }
    }

    protected function matchHash($hashs) : void
    {
        $match = Frame::get()->query()->select([])->where([
            'hash1' => $hashs[0],
            'hash2' => $hashs[1],
            'hash3' => $hashs[2],
            'hash4' => $hashs[3],
        ], true)->all()->exec();

        if (!empty($match)) {
            $maxDistance = $this->options['distance'];
            foreach ($match as $one) {
                if ($one['file_id'] == $this->curId) {
                    continue;
                }
                $left = [
                    $one['hash1'], $one['hash2'], $one['hash3'], $one['hash4'],
                ];
                if (self::withinDistance($left, $hashs, $maxDistance)) {
                    $list[] = $one['file_id'];
                    if (!isset($this->matchFrames[$one['file_id']])) {
                        $this->matchFrames[$one['file_id']] = [];
                    }
                    $this->matchFrames[$one['file_id']][$one['id']] = 1;
                }
            }
        }
    }

    protected function getCrop(int $duration) : array
    {
        $offset = intval(($duration / 10));
        if ($offset <= 0) {
            $offset = 1;
        }

        $ss_list = [$offset];
        for ($i = 0; $i < 9; $i++) {
            $tmp = $ss_list[$i] + $offset;
            if ($tmp > $duration) {
                break;
            }
            $ss_list[] = $tmp;
        }

        $crop = null;

        foreach ($ss_list as $ss) {
            $cmd = 'ffmpeg -ss ' . $ss . ' -i "' . $this->curFilePath . '" -vframes 10 -vf cropdetect -f null - 2>&1 | grep \'cropdetect\'';
            $result = (new Process($cmd))->exec(1, $exitCode);
            $result = explode("\n", $result);
            foreach ($result as $v) {
                if (preg_match('/x1:(\\d+) x2:(\\d+) y1:(\\d+) y2:(\\d+) w:(\\d+) h:(\\d+)/', $v, $matches)) {
                    if ($crop === null) {
                        $crop = [$matches[1], $matches[2], $matches[3], $matches[4]];
                    } else {
                        $crop[0] = $crop[0] <= $matches[1] ? $crop[0] : $matches[1];
                        $crop[1] = $crop[1] >= $matches[2] ? $crop[1] : $matches[2];
                        $crop[2] = $crop[2] <= $matches[3] ? $crop[2] : $matches[3];
                        $crop[3] = $crop[3] >= $matches[4] ? $crop[3] : $matches[4];
                    }
                }
            }
        }

        if ($crop === null) {
            throw new Exception('获取视频边界失败:' . $this->curFileShow);
        }

        return $crop;
    }

    protected function getDuration() : int
    {
        $cmd = 'ffprobe -v quiet -print_format json -show_streams "' . $this->curFilePath . '"';
        $json = (new Process($cmd))->exec(1, $exitcode);
        $basic = json_decode($json, true);
        if (empty($basic || empty($info['streams']))) {
            throw new Exception('不是视频文件：' . $this->curFileShow);
        }

        $duration = 0;

        foreach ($basic['streams'] as $v) {
            if (isset($v['duration'])) {
                $new_duration = intval($v['duration']);
                if ($new_duration > $duration) {
                    $duration = $new_duration;
                }
            }
        }

        if ($duration <= 1) {
            throw new Exception('不是视频文件：' . $this->curFileShow);
        }

        return $duration;
    }

    protected function findFile(File $file, int $size)
    {
        $dbFile = TableFile::get()->query()
            ->select([])
            ->where(['size' => $size])->all()->exec();

        $sha = null;
        if (!empty($dbFile)) {
            $sha = sha1_file($file->getFsPath(), false);
            foreach ($dbFile as $one) {
                if ($sha === $one['sha']) {
                    if ($this->options['save'] && $this->options['replace']) {
                        TableFile::get()->query()
                            ->update([
                                'path' => $this->curFileShow,
                                'name' => $file->getName()
                            ])
                            ->where(['id' => $one['id']])->exec();
                        $this->curId = $one['id'];

                        return $one;
                    } else {
                        return $one;
                    }
                }
            }
        }

        if ($this->options['save']) {
            if ($sha === null) {
                $sha = sha1_file($file->getFsPath(), false);
            }

            $this->curId = TableFile::get()->query()->insert([
                'path' => $this->curFileShow,
                'name' => $file->getName(),
                'sha' => $sha,
                'size' => $size,
                'frames' => 0,
            ])->exec();
        }

        return null;
    }

    public static function checkFrame(array $hash)
    {
        $distance = 0;
        for ($i = 0; $i < 4; $i++) {
            $value = $hash[$i];
            for ($j = 0; $j < 16; $j++) {
                if (($value & 1) == 1) {
                    $distance++;
                }
                $value = $value >> 1;
            }
        }

        if ($distance >= 60 || $distance <= 4) {
            return false;
        }

        return true;
    }

    public static function withinDistance(array $hash1, array $hash2, int $max)
    {
        $distance = 0;
        for ($i = 0; $i < 4; $i++) {
            $match = ($hash1[$i] ^ $hash2[$i]);

            for ($j = 0; $j < 16; $j++) {
                if (($match & 1) == 1) {
                    $distance++;
                    if ($distance > $max) {
                        return false;
                    }
                }
                $match = $match >> 1;
            }
        }

        return true;
    }

    public static function isVideo(string $filename) : bool
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $ext_list = [
            'mp4' => 1,
            'avi' => 1,
            'rmvb' => 1,
            'rm' => 1,
            'mkv' => 1,
            'wmv' => 1,
        ];

        if (!isset($ext_list[$ext]) || $ext_list[$ext] == 0) {
            return false;
        }

        return true;
    }

    ////////////////////////////////////////////////

    protected string $curFilePath;
    protected string $curFileShow;
    protected string $buffer = '';
    protected int $frameTotal = 0;
    protected int $frameSaved = 0;
    protected int $frameDelay = 0;
    protected int $curId = 0;
    protected array $matchFrames = [];
    protected array $options;
    const FRAME_SIZE = 8;
    const FRAME_STEP = 25;

    public function __construct(App $app)
    {
        $this->app = $app;
    }
    protected App $app;
}
