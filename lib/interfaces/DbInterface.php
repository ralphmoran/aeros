<?php

namespace Interfaces;

/**
 * @property DbInterface $db
 * @property string $error
 * @property int $errorno
 * @property array $errors
 */
interface DbInterface
{
    public function connect();
    public function query(string $q);
    public function select_db(string $db_name);
    public function multi_query(string $multi_query);
    public function store_result();
    public function error();
    public function next_result();
    public function commit();
    public function escape(string $string);
    public function close();
    public function get_last_insert_id();
}