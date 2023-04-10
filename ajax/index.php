<?php
declare(strict_types=1);

use BaseClasses\SessionTreeResponse;
use BaseClasses\PdoTreeResponse;
use BaseClasses\TreeResponseInterface;

require_once '../config/pdo.php';
require_once '../vendor/autoload.php';

/**
 * The class that handles all ajax requests
 */
class TreeAjaxResponse {
    /**
     * @var TreeResponseInterface
     */
    private $treeResponse;

    /**
     * Class constructor. Here a PDO instance is created and the connection to the database is checked
     * @param TreeResponseInterface $treeResponse
     */
    public function __construct(TreeResponseInterface $treeResponse)
    {
        $this->treeResponse = $treeResponse;
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
                case 'status':
                    return $this->treeResponse->getStatus();
                case 'check' :
                    return $this->treeResponse->checkRoot();
                case 'load' :
                    return $this->treeResponse->load();
                case 'remove' :
                    if (isset($_POST['id']) && $id = $_POST['id']) {
                        return $this->treeResponse->remove((int) $id);
                    }
                    break;
                case 'add':
                case 'update':
                    if (isset($_POST['id']) && isset($_POST['name'])) {
                        $name = $_POST['name'];
                        $id = $_POST['id'];
                        if ($action === 'add') {
                            return $this->treeResponse->add((int)$id, $name);
                        } else {
                            return $this->treeResponse->update((int)$id, $name);
                        }
                    } else {
                        return ['result' => 'fail', 'message' => 'not enough parameters'];
                    }
                default :
                    return ['result' => 'fail', 'message' => 'not exist that action'];
            }
        } else {
            return ['result' => 'fail', 'message' => 'error while accessing the database'];
        }
        return ['result' => 'fail', 'message' => 'error while accessing the database'];
    }
}
/**
 * create instance and send a response to the request
 * @var $dsn
 * @var $username
 * @var $password
 * @var $dbname
 */
try {
    $conn = new PDO($dsn, $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdoTreeResponse = new PdoTreeResponse($conn);
    $treeAjaxResponse = new TreeAjaxResponse($pdoTreeResponse);
}
catch (PDOException $e) {
    // when database is not available - working with session
    session_start();
    $sessionTreeResponse = new SessionTreeResponse();
    $treeAjaxResponse = new TreeAjaxResponse($sessionTreeResponse);
}
echo json_encode($treeAjaxResponse->response());
header('Content-type:application/json;charset=utf-8');
