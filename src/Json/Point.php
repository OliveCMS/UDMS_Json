<?php
namespace Olive\UDMS\Addon\Json;

use Olive\Tools;
use Olive\UDMS\Common as Common;
use Olive\UDMS\Model\Addon as Addon;
class Point implements Addon
{
    use Common;

    private $cacheDB;

    private $cacheDBC;

    public function createDatabase($name, $options)
    {
        $dir = $this->getCore->getUCPath($name . '/json/');
        if (! is_dir($dir)) {
            mkdir($dir);
        }
        $this->update_db($name);
        $this->update_dbc($name);
    }

    public function dropDatabase($name)
    {
        Tools::rmDir($this->getCore->getUCPath($name . '/json/'));
    }

    public function existsDatabase($name)
    {
        if (file_exists($this->getCore->getUCPath($name . '/json/db.json'))) {
            return true;
        } else {
            return false;
        }
    }

    public function listDatabases()
    {
        $return = [];
        $dbdirs = Tools::getDirList($this->getCore->getUCPath());
        foreach ($dbdirs as $db) {
            if (file_exists($this->getCore->getUCPath($db . '/json/db.json'))) {
                $return[] = $db;
            }
        }

        return $return;
    }

    public function renameDatabase($name, $to)
    {
    }

    private function get_db($name)
    {
        if (is_null($this->cacheDB)) {
            $this->cacheDB = Tools::getJsonFile($this->getCore->getUCPath($name . '/json/db.json'));
        }

        return $this->cacheDB;
    }

    private function update_db($name, $data = [])
    {
        Tools::file($this->getCore->getUCPath($name . '/json/db.json'), Tools::jsonEncode($data));
        $this->cacheDB = $data;
    }

    private function get_dbc($name)
    {
        if (is_null($this->cacheDBC)) {
            $this->cacheDBC = Tools::getJsonFile($this->getCore->getUCPath($name . '/json/config.json'));
        }

        return $this->cacheDBC;
    }

    private function update_dbc($name, $data = [])
    {
        Tools::file($this->getCore->getUCPath($name . '/json/config.json'), Tools::jsonEncode($data));
        $this->cacheDBC = $data;
    }

    public function createTable($db, $name, $options)
    {
        $gdbc = $this->get_dbc($db);
        $gdbc[$name] = [];
        $this->update_dbc($db, $gdbc);
        $this->createColumn($db, $name, '__udms_id', []);
    }

    public function dropTable($db, $name)
    {
        $gdbc = $this->get_dbc($db);
        unset($gdbc[$name]);
        $this->update_dbc($db, $gdbc);
    }

    public function existsTable($db, $name)
    {
        $gdbc = $this->get_dbc($db);
        if (isset($gdbc[$name])) {
            return true;
        } else {
            return false;
        }
    }

    public function listTables($db)
    {
        $gdbc = $this->get_dbc($db);

        return array_keys($gdbc);
    }

    public function renameTable($db, $name, $to)
    {
        $gdbc = $this->get_dbc($db);
        $gdbc[$to] = $gdbc[$name];
        unset($gdbc[$name]);
        $this->update_dbc($db, $gdbc);
    }

    public function createColumn($db, $table, $name, $options)
    {
        $gdbc = $this->get_dbc($db);
        $o = [];
        if (isset($options['__udms_config']['json'])) {
            $o = $options['__udms_config']['json'];
        }
        $gdbc[$table][$name] = $o;
        $this->update_dbc($db, $gdbc);
    }

    public function existsColumn($db, $table, $name)
    {
        $gdbc = $this->get_dbc($db);
        if (isset($gdbc[$table][$name])) {
            return true;
        } else {
            return false;
        }
    }

    public function listColumns($db, $table)
    {
        $gdbc = $this->get_dbc($db);

        return array_keys($gdbc[$table]);
    }

    public function dropColumn($db, $table, $name)
    {
        $gdb = $this->get_db($db);
        $gdbc = $this->get_dbc($db);
        unset($gdbc[$table][$name]);
        if (isset($gdb[$table])) {
            foreach ($gdb[$table] as $key => $value) {
                if (isset($value[$name])) {
                    unset($gdb[$table][$key][$name]);
                }
            }
        }
        $this->update_db($db, $gdb);
        $this->update_dbc($db, $gdbc);
    }

    public function insert($db, $table, $data)
    {
        $gdb = $this->get_db($db);
        if (isset($gdb[$table])) {
            $key = count($gdb[$table]);
        } else {
            $key = 0;
        }
        $gdb[$table][$key] = $data;
        $this->update_db($db, $gdb);
    }

    public function update($db, $table, $uid, $data)
    {
        $gdb = $this->get_db($db);
        foreach ($gdb[$table] as $key => $value) {
            if ($value['__udms_id'] == $uid) {
                foreach ($data as $dkey => $dvalue) {
                    $gdb[$table][$key][$dkey] = $dvalue;
                }

                break;
            }
        }
        $this->update_db($db, $gdb);
    }

    public function delete($db, $table, $uid)
    {
        $gdb = $this->get_db($db);
        foreach ($gdb[$table] as $key => $value) {
            if ($value['__udms_id'] == $uid) {
                unset($gdb[$table][$key]);

                break;
            }
        }
        $this->update_db($db, $gdb);
    }

    public function cleanTable($db, $table)
    {
        $gdb = $this->get_db($db);
        unset($gdb[$table]);
        $gdb[$table] = [];
        $this->update_db($db, $gdb);
    }

    public function get($db, $table)
    {
        $gdb = $this->get_db($db);
        if (isset($gdb[$table])) {
            return $gdb[$table];
        } else {
            return [];
        }
    }

    public function __construct($point, $option = [])
    {
        $this->getCore = $point;
    }
}
