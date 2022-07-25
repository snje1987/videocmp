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

class App
{
    protected Console $console;
    protected Config $config;
    protected OptionParser $parser;

    public function __construct()
    {
        $this->config = new Config(DATA_DIR . '/config.json');
        if ($this->config->get('debug')) {
            define('DEBUG', 1);
        } else {
            define('DEBUG', 0);
        }

        $this->console = new Console();

        $options = require(APP_ROOT . '/config/optionCfg.php');
        $this->parser = new OptionParser($options);
    }

    public function run(array $argv) : void
    {
        try {
            array_shift($argv);
            $action = array_shift($argv);

            $action = $this->parser->getAction($action);
            $options = $this->parser->getOptions($action, $argv);

            $function = 'do' . ucfirst($action);
            if (!method_exists($this, $function)) {
                throw new Exception('操作不存在');
            }

            call_user_func([$this, $function], $options);
        } catch (Exception $ex) {
            $msg = $ex->getMessage();
            if (DEBUG) {
                $msg = $ex->getFile() . '[' . $ex->getLine() . ']: ' . $msg;
            }

            $this->console->reset()->print($msg);
        }
    }

    /////////////////////////////////////

    protected function doConfig(array $opts) : void
    {
        if (!empty($opts['options']['get'])) {
            $name = $opts['options']['get'];
            echo $this->config->show($name) . "\n";
        }
        if (!empty($opts['options']['set'])) {
            $pair = $opts['options']['set'];
            $this->config->set($pair[0], $pair[1])->save();
            echo $this->config->show($pair[0]) . "\n";
        }
    }

    protected function doHelp() : void
    {
        echo $this->parser->getManual() . "\n";
    }
}
