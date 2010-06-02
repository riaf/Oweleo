<?php
require_once 'Net/IRC/Client.php';
require_once 'OweleoPlugin.php';

/**
 * Oweleo
 **/
class Oweleo extends Net_IRC_Client
{
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

    protected function on_privmsg($m) {
        list($prefix, $message) = $m->params();
        // plugin-manager
        if (strpos($message, '@'. $this->nick) === 0) {
            $this->plugin_manager($prefix, $message);
        }

        // 通常のアクション
        foreach ($this->plugins as $plugin_name => $plugin) {
            if (preg_match($plugin->pattern(), $message, $match)) {
                $this->run_plugin($plugin_name, 'on_privmsg', $m, $match);
            }
        }
    }
    protected function init() {
        // load plugins
        $plugins = new GlobIterator($this->plugins_dir. '/*.php', FilesystemIterator::SKIP_DOTS);
        foreach ($plugins as $plugin) {
            if ($plugin->isFile()) {
                try {
                    // $this->load_plugin($plugin->getBasename('.php'));
                } catch (RuntimeException $e) {
                    $this->on_error($e->getMessage());
                }
            }
        }
    }
    protected function plugin_manager($prefix, $message) {
        $args = array_map('trim', explode(' ', $message));
        $nick = array_shift($args);
        if (empty($args)) {
            return;
        }
        $flag = array_shift($args);
        if ($flag != 'plugin') {
            return;
        }
        $command = strtolower(array_shift($args));
        switch ($command) {
            case 'list':
                $this->notice($prefix, 'Loaded Plugins: '. count($this->plugins));
                foreach ($this->plugins as $plugin_name => $plugin) {
                    usleep(100);
                    $this->notice($prefix, '  '. $plugin_name);
                }
                break;
            case 'list-all':
                $plugins = new GlobIterator($this->plugins_dir. '/*.php', FilesystemIterator::SKIP_DOTS);
                $this->notice($prefix, 'Available Plugins: '. count($plugins));
                foreach ($plugins as $plugin) {
                    usleep(100);
                    if ($plugin->isFile()) {
                        $this->notice($prefix, '  '. $plugin->getBasename('.php'));
                    }
                }
                break;
            case 'load':
                try {
                    $this->load_plugin($args[0]);
                    $this->notice($prefix, $args[0]. ' plugin loaded');
                } catch (Exception $e) {
                    $this->notice($prefix, $e->getMessage());
                }
                break;
            case 'remove':
            case 'rm':
                try {
                    $this->remove_plugin($args[0]);
                } catch (Exception $e) {
                    $this->notice($prefix, $e->getMessage());
                }
                break;
            case 'flush':
                $this->flush_plugins();
                break;
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
                    $action = $this->plugins[$name]->$action($this, $message, $params);
                }
                exit(0);
            }
        }
    }
    protected function load_plugin($name) {
        $this->debug('loading plugin... '. $name);
        $path = $this->plugins_dir. '/'. $name. '.php';
        if (file_exists($path) && is_readable($path)) {
            $this->debug('load plugin:'. $name);
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
        // echo printf('%s / %s', memory_get_usage(), memory_get_peak_usage()), PHP_EOL;
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

