<?php

namespace block_extensao\Service;

use PDO;

class USPDatabase
{
    private static $instance;
    /**
     * Singleton sybase connection
     */
    public static function getInstance(){
        global $CFG;

        $host = get_config('block_extensao','host');
        $port = get_config('block_extensao','port');
        $db   = get_config('block_extensao','database');
        $user = get_config('block_extensao','user');
        $pass = get_config('block_extensao','password');

        if (empty($host) or empty($port) or empty($db) or empty($user) or empty($pass)) {
            echo "ERRO: As credenciais de conexão estão vazias." . PHP_EOL;
            die();
        } 
        if (!self::$instance) {
            try {
                $dsn = "dblib:host={$host}:{$port};dbname={$db};charset=UTF-8";
                self::$instance = new PDO($dsn,$user,$pass);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            } catch (\Throwable $t) {
                // print_r($t->getMessage()) . '<br>';
                echo "Erro na conexão com o database do replicado! Contate o suporte" . PHP_EOL;
                die();
            }
        }
        return self::$instance;
    }

    public static function utf8_converter($array)
    {
        array_walk_recursive($array, function (&$item, $key) {
            if (!mb_detect_encoding($item, 'utf-8', true)) {
                $item = utf8_encode($item);
            }
        });
        return $array;
    }

    // overhide fetch
    public static function fetch(string $query, array $param = null)
    {
        try {
            $stmt = self::getInstance()->prepare($query);
            if (!is_null($param)) {
                foreach ($param as $campo => $valor) {
                    $valor = utf8_decode($valor);
                    $stmt->bindValue(":$campo", $valor);
                }
            }
            $stmt->execute();
        } catch (\Throwable $t) {
            #print_r($t->getMessage());
            echo "Erro Interno no replicado: contate o suporte!";
            return false;
        }

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            $result = self::utf8_converter($result);
        }
        return $result;
    }

    public static function fetchAll(string $query, array $param = null)
    {
        try {
            $stmt = self::getInstance()->prepare($query);
            if (!is_null($param)) {
                foreach ($param as $campo => $valor) {
                    $valor = utf8_decode($valor);
                    $stmt->bindValue(":$campo", $valor);
                }
            }
            $stmt->execute();
        } catch (\Throwable $t) {
            // print_r($t->getMessage());
            echo "Erro Interno no replicado: contate o suporte!";
            return false;
        }

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($result)) {
            $result = self::utf8_converter($result);
        }
        return $result;
    }

}
