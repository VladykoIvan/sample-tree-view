<?php
declare(strict_types=1);

require_once '../config/pdo.php';

/**
 * The class that handles all ajax requests
 */
class TreeAjaxResponse {
    /**
     * Table name in database
     */
    const TABLE_NAME = "TREE";
    /**
     * Connection for working with database
     * @var PDO
     */
    protected $conn;
    /**
     * Result data for response
     * @var string[]
     */
    protected $result;

    /**
     * Class constructor. Here a PDO instance is created and the connection to the database is checked
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param string $dbname
     */
    public function __construct(string $dsn, string $username, string $password, string $dbname)
    {
        try {
            $this->conn = new PDO($dsn, $username, $password);
            // set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {
            die ("Could not connect to the database $dbname :" . $e->getMessage());
        }
        $this->result = ['result' => 'fail', 'message' => 'error while accessing the database'];
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->conn = null;
    }

    /**
     * Check root of tree in database
     * @return void
     */
    public function checkRoot() {
        try {
            $stmt = $this->conn->prepare(
                "select * from " .
                self::TABLE_NAME .
                " WHERE parent_id is null LIMIT 1"
            );
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->result = ['result' => 'success', 'message' => $res];
        }
        catch(PDOException $e)
        {
            $this->result = ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * Recursive build tree for all nodes
     * @param array $items
     * @param int $parent
     * @return array
     */
    private function buildTree(array $items, int $parent = 0): array
    {
        $tree = array();
        foreach ($items as $item) {
            if ($item['parent_id'] == $parent) {
                $children = $this->buildTree($items, (int) $item['id']);
                if ($children) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }
        return $tree;
    }

    /**
     * Load tree when we have root
     * @return void
     */
    protected function load () {
        try {
            $stmt = $this->conn->prepare("select * from " . self::TABLE_NAME . " order by parent_id");
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $res = $this->buildTree($res);
            $this->result = ['result' => 'success', 'message' => $res];
        }
        catch(PDOException $e)
        {
            $this->result = ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete node with ID and cascade delete all child nodes (mySql)
     * @param int $id
     * @return void
     */
    protected function remove(int $id) {
        try {
            $query = $this->conn->prepare("delete from " . self::TABLE_NAME . " where id=" . $id);
            $query->execute();
            $this->result = ['result' => 'success', 'message' => ["rows" => "{$query->rowCount()}"]];
        }
        catch(PDOException $e)
        {
            $this->result = ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * Add node with parent_id=$parent_id and name=$name
     * @param $parent_id
     * @param string $name
     * @return void
     */
    public function add($parent_id, string $name) {
        try {
            if (!$parent_id) {
                $parent_id = 'null';
            }
            $stmt = $this->conn->prepare(
                "insert into " . self::TABLE_NAME .
                " (parent_id, name) values (" . $parent_id  . ", '" . $name . "')"
            );
            $stmt->execute();
            $last_id = $this->conn->lastInsertId();
            $this->result = ['result' => 'success', 'message' => ["id" => "$last_id", "name" => "$name"]];
        }
        catch(PDOException $e)
        {
            $this->result = ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    public function update(int $id, string $name) {
        try {
            $stmt = $this->conn->prepare(
                "update " . self::TABLE_NAME . " set name='" . $name . "' where id=" . $id
            );
            $stmt->execute();
            $this->result = ['result' => 'success', 'message' => ["id" => "$id", "name" => "$name"]];
        }
        catch(PDOException $e)
        {
            $this->result = ['result' => 'fail', 'message' => $e->getMessage()];
        }
    }

    /**
     * Response to ajax request
     * @return string[]
     */
    public function response(): array
    {
        if (isset($_POST['action']) && $action = $_POST['action']) {
            // select the necessary action with the database depending on the parameter $action
            switch ($action) {
                case "check" :
                    $this->checkRoot();
                    break;
                case "load" :
                    $this->load();
                    break;
                case "remove" :
                    if (isset($_POST['id']) && $id = $_POST['id']) {
                        $this->remove((int) $id);
                    }
                    break;
                case "add":
                case "update":
                    if (isset($_POST['id']) && isset($_POST['name'])) {
                        $name = $_POST['name'];
                        $id = $_POST['id'];
                        if ($action === 'add') {
                            $this->add((int)$id, $name);
                        } else {
                            $this->update((int)$id, $name);
                        }
                    } else {
                        $this->result = ['result' => 'fail', 'message' => 'not enough parameters'];
                    }
                    break;
                default :
                    $this->result = ['result' => 'fail', 'message' => 'not exist that action'];
            }
        } else {
            $this->result = ['result' => 'fail', 'message' => 'error while accessing the database'];
        }
        return $this->result;
    }
}
/**
 * create instance and send a response to the request
 * @var $dsn
 * @var $username
 * @var $password
 * @var $dbname
 */
$treeAjaxResponse = new TreeAjaxResponse($dsn, $username, $password, $dbname);
echo json_encode($treeAjaxResponse->response());
header('Content-type:application/json;charset=utf-8');
