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

namespace Org\Snje\Videocmp;

use Minifw\Common\Exception;
use Minifw\Common\File as CommonFile;
use Minifw\Console\Console;
use Minifw\Console\OptionParser;
use Minifw\Console\Utils;
use Minifw\DB\Driver;
use Minifw\DB\Driver\Sqlite3;
use Minifw\DB\Query;
use Org\Snje\Videocmp\Table\File;
use Org\Snje\Videocmp\Table\Frame;

class App
{
    protected Console $console;
    protected Config $config;
    protected OptionParser $parser;
    protected ?Driver $driver = null;
    protected array $options;
    protected array $input;
    protected string $action;
    protected static ?self $instance = null;

    public static function get(?array $argv = null) : ?self
    {
        if (self::$instance === null) {
            try {
                self::$instance = new self($argv);
            } catch (Exception $ex) {
                $msg = $ex->getMessage();
                if (defined('DEBUG') && DEBUG) {
                    $msg = $ex->getFile() . '[' . $ex->getLine() . ']: ' . $msg;
                }
                echo $msg . "\n";

                return null;
            }
        }

        return self::$instance;
    }

    protected function __construct($argv)
    {
        $options = require(APP_ROOT . '/config/optionCfg.php');
        $this->parser = new OptionParser($options);

        array_shift($argv);
        $info = $this->parser->parse($argv);

        $this->options = $info['options'];
        $this->input = $info['input'];
        $this->action = $info['action'];

        $this->init($info['global']);
    }

    protected function init(array $global) : void
    {
        if (!empty($global['database'])) {
            $config = [];
            $config['file'] = $global['database'];
            $this->driver = new Sqlite3($config);
        }

        if (!empty($global['config'])) {
            $configPath = $global['config'];
        } else {
            $configPath = DATA_DIR . '/config.json';
        }

        $this->config = new Config($configPath);
        if ($this->config->get('debug')) {
            define('DEBUG', 1);
        } else {
            define('DEBUG', 0);
        }

        if ($this->driver !== null) {
            Query::setDefaultDriver($this->driver);
        } elseif ($this->action !== 'help' && $this->action !== 'config' && $this->action !== 'dump') {
            throw new Exception('?????????????????????');
        }

        if ($this->action !== 'dump') {
            $this->console = new Console();
        }

        set_error_handler(function ($code, $msg, $file, $line) {
            if (DEBUG) {
                $msg = '[' . $code . '] ' . $file . '[' . $line . ']: ' . $msg;
            }
            $this->console->print($msg);
        });
    }

    public function run() : void
    {
        try {
            $function = 'do' . ucfirst($this->action);
            if (!method_exists($this, $function)) {
                throw new Exception('???????????????');
            }

            if ($this->driver !== null) {
                Migrate::applyAll($this->driver);
            }

            call_user_func([$this, $function], $this->options, $this->input);
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            if (DEBUG) {
                $msg = '[' . $ex->getCode() . '] ' . $ex->getFile() . '[' . $ex->getLine() . ']: ' . $msg;
            }

            $this->console->reset()->print($msg);
        }
    }

    public function getDriver() : ?Driver
    {
        return $this->driver;
    }

    public function print($msg) : Console
    {
        return $this->console->print($msg);
    }

    public function setStatus($msg) : Console
    {
        return $this->console->setStatus($msg);
    }

    public function reset() : Console
    {
        return $this->console->reset();
    }

    /////////////////////////////////////

    protected function doConfig() : void
    {
        if (!empty($this->options['get'])) {
            $name = $this->options['get'];
            echo $this->config->show($name) . "\n";
        }
        if (!empty($this->options['set'])) {
            $pair = $this->options['set'];
            $this->config->set($pair[0], $pair[1])->save();
            echo $this->config->show($pair[0]) . "\n";
        }
    }

    protected function doHelp() : void
    {
        echo $this->parser->getManual() . "\n";
    }

    protected function doScan(array $options, array $input) : void
    {
        $result = [];
        $out = $options['out'];

        $parser = new VideoParser($this, $options['distance']);
        foreach ($input as $path) {
            if (!file_exists($path)) {
                throw new Exception('???????????????: ' . $path);
            }
            $path = Utils::getFullPath($path);
            $file = new CommonFile($path);
            $file->map(function (CommonFile $file, string $relPath) use ($parser, $options, $out) {
                if ($options['save']) {
                    $this->driver->begin();
                }

                try {
                    $ret = $parser->parse($file, $options);
                    if (!empty($ret)) {
                        $lines = [];
                        if (isset($ret['total'])) {
                            $lines[] = '[' . $ret['total'] . '] ' . $file->getFsPath();
                        } else {
                            $lines[] = $file->getFsPath();
                        }
                        if (isset($ret['same'])) {
                            $lines[] = '????????????: ' . $ret['same'];
                        }
                        if (isset($ret['replace'])) {
                            $lines[] = '????????????: ' . $ret['replace'];
                        }
                        if (isset($ret['match'])) {
                            foreach ($ret['match'] as $value) {
                                $line = str_pad($value['pecent1'], 7, ' ', STR_PAD_LEFT) . '??? ' . str_pad($value['pecent2'], 7, ' ', STR_PAD_LEFT) . '???';

                                $line .= ' [' . $value['match'] . '/' . $value['total'] . ']';
                                $line .= ' ' . $value['path'];
                                $lines[] = $line;
                            }
                        }

                        $lines[] = '';

                        file_put_contents($out, implode("\n", $lines) . "\n", FILE_APPEND);
                    }

                    if ($options['save']) {
                        $this->driver->commit();
                    }
                } catch (Exception $ex) {
                    if ($options['save']) {
                        $this->driver->rollback();
                    }
                    throw $ex;
                }
            }, true, '', CommonFile::LOOP_TARGET_FILE);
        }
    }

    protected function doDump(array $options) : void
    {
        $size = $options['size'];
        $pixs = $size * $size;

        while (true) {
            $buffer = fread(STDIN, $pixs);
            if ($buffer === false || strlen($buffer) != $pixs) {
                break;
            }

            $total = 0;
            $colors = [];
            for ($i = 0; $i < $pixs; $i++) {
                $colors[$i] = ord($buffer[$i]);
                $total += $colors[$i];
            }

            $avg = (double) $total / $pixs;

            $hashs = '';
            for ($i = 0; $i < $size; $i++) {
                $byte = 0;
                for ($j = 0; $j < $size; $j++) {
                    if ($colors[$i * $size + $j] > $avg) {
                        $byte = $byte | (1 << $j);
                    }
                }
                $hashs .= chr($byte);
            }
            echo $hashs;
        }
    }

    protected function doMissing(array $options, array $input) : void
    {
        File::get()->query()->all()->map(function ($data) use ($options) {
            $path = $data['path'];
            if (!file_exists($path)) {
                $this->print($data['path']);
                if ($options['save']) {
                    try {
                        $this->driver->begin();

                        File::get()->query()->delete()->where(['id' => $data['id']])->exec();
                        Frame::get()->query()->delete()->where(['file_id' => $data['id']])->exec();

                        $this->driver->commit();
                    } catch (Exception $ex) {
                        $this->driver->rollback();
                        throw $ex;
                    }
                }
            }
        });
    }
}
