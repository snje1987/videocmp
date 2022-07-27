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
use Minifw\Console\Console;
use Minifw\Console\OptionParser;
use Minifw\DB\Driver;
use Minifw\DB\Driver\Sqlite3;

class App
{
    protected Console $console;
    protected Config $config;
    protected OptionParser $parser;
    protected ?Driver $driver = null;
    protected array $options;
    protected array $input;
    protected string $function;
    protected static ?self $instance = null;

    public static function get($argv) : ?self
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
        $this->console = new Console();

        $options = require(APP_ROOT . '/config/optionCfg.php');
        $this->parser = new OptionParser($options);

        array_shift($argv);
        $info = $this->parser->parse($argv);

        $this->options = $info['options'];
        $this->input = $info['input'];
        $action = $info['action'];

        $this->function = 'do' . ucfirst($action);
        if (!method_exists($this, $this->function)) {
            throw new Exception('操作不存在');
        }

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

        if ($this->driver === null) {
            $database = $this->config->get('database');
            if (!empty($database)) {
                $config = [];
                $config['file'] = $database;
                $this->driver = new Sqlite3($config);
            }
        }
    }

    public function run() : void
    {
        try {
            call_user_func([$this, $this->function], $this->options);
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            if (DEBUG) {
                $msg = $ex->getFile() . '[' . $ex->getLine() . ']: ' . $msg;
            }

            $this->console->reset()->print($msg);
        }
    }

    public function getDriver() : ?Driver
    {
        return $this->driver;
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
}
