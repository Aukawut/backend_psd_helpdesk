<?php
require_once("./vendor/autoload.php");
require_once("./Config/connect.php");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
date_default_timezone_set("Asia/Bangkok");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Disposition, Content-Type, Content-Length, Accept-Encoding,Authorization");
header('Content-Type: application/json');
require_once("./Controller/AuthController.php");
require_once("./Controller/UserController.php");

// สร้าง Class API
class API
{
    public $UserController;
    public $AuthController;
    public function __construct()
    {
        $this->AuthController = new AuthController();
        $this->UserController = new UserController();

    }
}

$api = new API(); //สร้าง Initial API

//ตั้งค่า Routes API Endpoint
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $req = (object) json_decode(file_get_contents("php://input"));
    if ($req !== null) {
        //ตรวจสอบ Request Router
        if (isset($req->router)) {
            switch ($req->router) {
                case "allUsers":
                    $api->UserController->getUsers();
                    break;
                default:
                    echo json_encode(["msg" => "Can't Request!"]);
            }
        } else {
            echo json_encode(["msg" => "Router is not provided!"]);
        }
    } else {
        echo json_encode(["msg" => "Error Router!"]);
    }
} else if ($_SERVER["REQUEST_METHOD"] == 'GET') {
    echo json_encode(["msg" => "Can't GET Request."]);
} else {
    echo json_encode(["msg" => "Route is not provided!"]);
}
