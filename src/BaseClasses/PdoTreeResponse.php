<?php
declare(strict_types=1);
namespace BaseClasses;

use PDO;
use PDOException;

/**
 * PDO implementation for TreeResponseInterface
 */
class PdoTreeResponse extends BaseTree implements TreeResponseInterface
{
    /**
     * Table name in database
     */
    const TABLE_NAME = 'TREE';

    /**
     * Connection for working with database
     * @var PDO
     */
    private $conn;

    /**
     * Class constructor. Here a PDO instance is created and the connection to the database
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->conn = null;
    }

    /**
     * @inheritDoc
     */
    public function checkRoot(): array
    {
        try {
            $stmt = $this->conn->prepare(
                "select * from " .
                self::TABLE_NAME .
                " WHERE " . BaseTree::FIELDS['parent_id'] . " is null LIMIT 1"
            );
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['result' => 'success', 'message' => $res];
        }
        catch(PDOException $e)
        {
            return ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * @inheritDoc
     */
    public function load(): array
    {
        try {
            $stmt = $this->conn->prepare("select * from " . self::TABLE_NAME . " order by " .
                BaseTree::FIELDS['parent_id']);
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $res = $this->buildTree($res);
            return ['result' => 'success', 'message' => $res];
        }
        catch(PDOException $e)
        {
            return ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * @inheritDoc
     */
    public function remove(int $id): array
    {
        try {
            $query = $this->conn->prepare("delete from " . self::TABLE_NAME . " where ".
                BaseTree::FIELDS['id'] ."=" . $id);
            $query->execute();
            return['result' => 'success', 'message' => ["rows" => "{$query->rowCount()}"]];
        }
        catch(PDOException $e)
        {
            return['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * @inheritDoc
     */
    public function add($parent_id, string $name): array
    {
        try {
            if (!$parent_id) {
                $parent_id = 'null';
            }
            $name = htmlspecialchars(strip_tags($name));
            $stmt = $this->conn->prepare(
                "insert into " . self::TABLE_NAME . " (" . BaseTree::FIELDS['parent_id'] .
                ", " . BaseTree::FIELDS['name'] . ") values (? , ?)"
            );
            $stmt->execute([$parent_id, $name]);
            $last_id = $this->conn->lastInsertId();
            $name = htmlspecialchars_decode($name);
            return ['result' => 'success', 'message' => ["id" => "$last_id", "name" => "$name"]];
        }
        catch(PDOException $e)
        {
            return['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, string $name): array
    {
        try {
            $name = htmlspecialchars(strip_tags($name));
            $stmt = $this->conn->prepare(
                "update " . self::TABLE_NAME . " set " . BaseTree::FIELDS['name'] .
                "=? where " . BaseTree::FIELDS['id'] . "=" . $id
            );
            $stmt->execute([$name]);
            $name = htmlspecialchars_decode($name);
            return ['result' => 'success', 'message' => ["id" => "$id", "name" => "$name"]];
        }
        catch(PDOException $e)
        {
            return ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): array
    {
        return ['result' => 'success', 'message' => ["text" => "db is available", "color" => "text-success"]];
    }
}
