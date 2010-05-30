<?php
require_once 'Net/IRC/Client.php';

/**
 * Oweleo
 **/
class Oweleo extends Net_IRC_Client
{
    protected $stacks = array();
    protected $config = array();
    protected $plugins = array();
    protected $plugins_dir = 'plugins';

    public function start() {
        $this->init();
        $this->connect();
    }
    public function set_config(array $config) {
        $this->config = $config;
    }

    protected function on_message() {
        $this->send_stacks();
    }

    protected function on_privmsg($m) {
        list($prefix, $message) = $m->params;
        foreach ($this->plugins as $plugin_name => $plugin) {
            if (preg_match($plugin->pattern, $message, $match)) {
                $this->run_plugin($plugin_name, 'on_privmsg', $m, $match);
            }
        }
        $this->send_stacks();
    }

    public function send_stacks() {
        if (!empty($this->stacks)) {
            foreach ($this->stacks as $stack) {
                $this->post('NOTICE', $stack['prefix'], ':'. $stack['message']);
            }
            $this->stacks = array();
        }
    }
    protected function init() {
        // load plugins
        $plugins = new GlobIterator($this->plugins_dir. '/*.php', FilesystemIterator::SKIP_DOTS);
        foreach ($plugins as $plugin) {
            if ($plugin->isFile()) {
                try {
                    $this->load_plugin($plugin->getBasename('.php'));
                } catch (RuntimeException $e) {
                    $this->on_error($e->getMessage());
                }
            }
        }
    }
    protected function run_plugin($name, $action, $message, array $params) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new RuntimeException($name. ': fork failed');
        } else if ($pid) {
            // parent proc
            echo '[p] called: '. $name, PHP_EOL;
            pcntl_wait($status);
        } else {
            // child proc
            echo '[c] called: '. $name, PHP_EOL;
            $c_pid = pcntl_fork();
            if ($c_pid == -1) {
                throw new RuntimeException($name);
            } else if ($c_pid) {
                exit(0);
            } else {
                if (isset($this->plugins[$name])) {
                    $message->oweleo = $this;
                    $params = array_merge(array($message, &$this->stacks), $params);
                    call_user_func_array($this->plugins[$name]->$action, $params);
                }
                $this->send_stacks();
                exit(0);
            }
        }
    }
    protected function load_plugin($name) {
        $path = $this->plugins_dir. '/'. $name. '.php';
        if (file_exists($path) && is_readable($path)) {
            $this->plugins[$name] = include($path);
        } else {
            throw new RuntimeException($name. ' plugins is not permitted.');
        }
    }
    protected function remove_plugin($name) {
        if (isset($this->plugins[$name])) {
            $this->plugins[$name] = null;
            unset($this->plugins[$name]);
        }
    }
    protected function flush_plugins() {
        foreach (array_keys($this->plugins) as $plugin) {
            $this->plugins[$plugin] = null;
            unset($this->plugins[$plugin]);
        }
        $this->plugins = array();
    }

    protected function debug($msg) {
        echo trim($msg), PHP_EOL;
    }
}

if (!debug_backtrace()) {
    set_time_limit(0);
    $config_file = isset($argv[1])? $argv[1]: 'config.php';
    if (file_exists($config_file) && is_readable($config_file)) {
        $config = @include $config_file;
    }
    if (!isset($config) || empty($config)) {
        throw new RuntimeException('config file is required');
    }
    $oweleo = new Oweleo($config['server'], $config['port'], $config['params']);
    $oweleo->set_config($config);
    $oweleo->start();
}

