<?php

namespace Test\vdeApps\phpCore\DoctrineModel;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Exception;
use PHPUnit\Framework\TestCase;
use vdeApps\phpCore\ChainedArray;
use vdeApps\phpCore\DoctrineModel\DoctrineModelAbstract;
use vdeApps\phpCore\DoctrineModel\DoctrineModelSqlite;
use vdeApps\phpCore\DoctrineModel\QBop;
use vdeApps\phpCore\Helper;

class TableTest extends DoctrineModelSqlite
{
    
    public $settings = [
        'tablename' => 'people',
        'viewname'  => 'v_people',
        'pk'        => 'pk_people',
        'sequence'  => null,
        'fields'    => [
            'pk_people'     => Type::INTEGER,
            'nom'           => Type::STRING,
            'prenom'        => Type::STRING,
            'adresse'       => Type::STRING,
            'code_postal'   => Type::STRING,
            'ville'         => Type::STRING,
            'telephone'     => Type::STRING,
            'portable'      => Type::STRING,
            'fax'           => Type::STRING,
            'email'         => Type::STRING,
            'fk_entreprise' => Type::INTEGER,
            'actif'         => Type::BOOLEAN,
            'adresse_2'     => Type::STRING,
            'adresse_3'     => Type::STRING,
            'horo'          => Type::DATETIME,
        ],
        'vfields'   => [
            'pk_people'     => Type::INTEGER,
            'nom'           => Type::STRING,
            'prenom'        => Type::STRING,
            'adresse'       => Type::STRING,
            'code_postal'   => Type::STRING,
            'ville'         => Type::STRING,
            'telephone'     => Type::STRING,
            'portable'      => Type::STRING,
            'fax'           => Type::STRING,
            'email'         => Type::STRING,
            'fk_entreprise' => Type::INTEGER,
            'actif'         => Type::BOOLEAN,
            'adresse_2'     => Type::STRING,
            'adresse_3'     => Type::STRING,
            'horo'          => Type::DATETIME,
        ],
    ];
    
}

class ModelTest extends TestCase
{
    
    /** @var Connection */
    protected $conn = false;
    
    protected $table = false;
    
    protected $queries = false;
    
    public function testModel()
    {
        $this->createConn();
        $this->createTables();
        
        $this->table = new TableTest($this->conn);
        
        try {
            $rows = $this->table->getAll();
            $this->assertEquals(2, count($rows));
    
            $this->table->setLimit(1);
            $rows = $this->table->read();
            $this->assertEquals(1, count($rows));
    
            $this->table->setLimit(1, 1);
            $rows = $this->table->read();
            $this->assertEquals(1, count($rows));
            
            $rows = $this->table->getAll(['pk_people' => 2]);
            $this->assertEquals(1, count($rows));
            
            $rows = $this->table->getAll(['nom' => 'zerzer']);
            $this->assertEquals(0, count($rows));
            
            $rows = $this->table->getAll([
                'pk_people' => [2, 1],
            ]);
            $this->assertEquals(2, count($rows));
            
            $rows = $this->table->getAll([
                'pk_people' => [QBop::LT => 2],
            ]);
            $this->assertEquals(1, count($rows));
            
            $rows = $this->table->getAll([
                'pk_people' => [QBop::GE => 1],
            ]);
            $this->assertEquals(2, count($rows));
            
        }
        catch (Exception $ex) {
            echo $ex->getMessage();
        }
        
        //        echo Helper::pre($rows);
    }
    
    /**
     * @return bool|\Doctrine\DBAL\Connection
     * @throws Exception
     */
    private function createConn()
    {
        $user = 'vdeapps';
        $pass = 'vdeapps';
        $path = __DIR__ . '/test.db';
        $memory = false;
        
        $this->queries = ChainedArray::getInstance(require_once __DIR__ . '/queries.php');
        
        $config = new \Doctrine\DBAL\Configuration();
        
        try {
            $connectionParams = [
                'driver' => 'pdo_sqlite',
                'user'   => $user,
                'pass'   => $pass,
                'path'   => $path,
                'memory' => $memory,
            ];
            $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
        }
        catch (Exception $ex) {
            $this->conn = false;
            throw new Exception("Failed to create connection", 10);
        }
        
        return $this->conn;
    }
    
    private function createTables()
    {
        try {
            $this->conn->exec($this->queries->createTablePeople);
            $this->conn->exec($this->queries->createViewPeople);
        }
        catch (Exception $ex) {
            echo $ex->getMessage();
            throw $ex;
        }
        
        return $this;
    }
    
}
