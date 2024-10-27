<?php

namespace Questo\Service;

class BaseDatabaseService extends BaseService
{
    protected $wpdb;
    protected $tableName;
    protected $columns;
    protected $inTransaction = False;

    /**
     * @return string
     */
    public function getCharsetCollate()
    {
        if (method_exists($this->wpdb, 'get_charset_collate')) {
            return $this->wpdb->get_charset_collate();
        }

        $charset_collate = '';

        if (!empty($this->wpdb->charset)) {
            $charset_collate = 'DEFAULT CHARACTER SET ' . $this->wpdb->charset;
        }
        if (!empty($this->wpdb->collate)) {
            $charset_collate .= ' COLLATE ' . $this->wpdb->collate;
        }

        return $charset_collate;
    }

    public function createTable()
    {
        if (!$this->tableExists()) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $this->wpdb->query(sprintf(
                'CREATE TABLE %s (%s) %s;',
                $this->tableName,
                implode(',', $this->columns),
                $this->getCharsetCollate()
            ));
        }
    }

    public function dropTable()
    {
        if ($this->tableExists()) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $this->wpdb->query(sprintf(
                'DROP TABLE %s',
                $this->tableName,
                $this->getCharsetCollate()
            ));
        }
    }

    /**
     * @return bool
     */
    public function tableExists()
    {
        $query = sprintf('SHOW TABLES LIKE \'%s\'', $this->tableName);

        return $this->wpdb->get_var($query) == $this->tableName;
    }

    public function transaction()
    {
        if (!$this->inTransaction) {
            $this->wpdb->query('START TRANSACTION');
            $this->inTransaction = True;
        }
    }

    public function commit()
    {
        if (!$this->inTransaction) {
            throw new \Exception('Start transaction before.');
        }
        $this->wpdb->query('COMMIT');
        $this->inTransaction = False;
    }

    public function rollback()
    {
        if (!$this->inTransaction) {
            throw new \Exception('Start transaction before.');
        }
        $this->wpdb->query('ROLLBACK');
        $this->inTransaction = False;
    }
}