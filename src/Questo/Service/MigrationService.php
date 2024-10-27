<?php

namespace Questo\Service;

class MigrationService extends BaseDatabaseService
{
    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix . 'adquesto_migration_versions';
        $this->columns = [
            'id int NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name varchar(255) NOT NULL',
            'created_at TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP(6)',
        ];

        add_action('wp_ajax_nopriv_questo_show_migrations', array($this, 'showMigrations'));
        add_action('wp_ajax_questo_show_migrations', array($this, 'showMigrations'));
    }


    public function showMigrations()
    {
        if (!current_user_can('administrator')) {
            return false;
        }

        $this->sendJson($this->findAll());
    }

    /**
     * @param $name
     * @return object
     * @internal param int $uid
     */
    public function findByName($name)
    {
        return $this->wpdb->get_row(sprintf('SELECT * FROM %s WHERE name = \'%s\'', $this->tableName, $name));
    }

    /**
     * @param $name
     * @return object
     * @internal param int $uid
     */
    public function deleteByName($name)
    {
        return $this->wpdb->get_row(sprintf('DELETE FROM %s WHERE name = \'%s\'', $this->tableName, $name));
    }

    /**
     * @param string $name
     * @return object
     * @internal param string $expiredAt
     */
    public function insert($name)
    {
        $this->wpdb->insert($this->tableName, ['name' => $name]);

        return $this->findByName($name);
    }

    public function removeAll()
    {
        $all = $this->wpdb->get_results(sprintf('SELECT * FROM %s ORDER BY created_at desc', $this->tableName));

        $this->transaction();
        foreach ($all as $migration) {
            try {
                if ($this->remove($migration->name)) {
                    $this->deleteByName($migration->name);
                }

            } catch (\Exception $e) {
                $this->rollback();

                throw $e;
            }
        }
        $this->commit();
    }


    /**
     * @return []
     */
    public function findAll()
    {
        return $this->wpdb->get_results(sprintf('SELECT * FROM %s', $this->tableName));
    }

    public function applyAll()
    {
        $all = $this->findAll();

        $migrations = [];
        foreach ($all as $migration) {
            $migrations[$migration->name] = $migration;
        }

        $files = $this->getFilesToApply($migrations);

        $this->transaction();
        foreach ($files as $name) {
            try {
                if ($this->apply($name)) {
                    $this->insert($name);
                }

            } catch (\Exception $e) {
                $this->rollback();

                throw $e;
            }
        }

        $this->commit();
    }


    /**
     * @param array $migrations
     * @return array
     */
    public function getFilesToApply($migrations)
    {
        $toApply = [];

        foreach ($this->getFiles() as $key => $file) {
            if (!isset($migrations[$file])) {
                $toApply[] = $file;
            }
        }

        return $toApply;
    }

    /**
     * @return array
     */
    public function getFiles()
    {
        $path = __DIR__ . '/../Migration';
        $excludedFiles = ['.', '..', 'BaseMigration.php'];

        $files = array_diff(scandir($path), $excludedFiles);
        sort($files);

        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $files[$key]);
        }

        return $files;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function apply($name)
    {
        $migrationClass = $this->getMigrationClassByName($name);

        if ($migrationClass) {
            $this->getMigrationClassByName($name)->apply();

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function remove($name)
    {
        $migrationClass = $this->getMigrationClassByName($name);

        if ($migrationClass) {
            $this->getMigrationClassByName($name)->remove();

            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool|object
     */
    public function getMigrationClassByName($name)
    {
        $migrationClass = 'Questo\\Migration\\' . $name;
        if (!class_exists($migrationClass)) {
            return false;
        }

        return new $migrationClass($this->wpdb);
    }
}