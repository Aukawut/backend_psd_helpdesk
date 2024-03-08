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
require_once("./Controller/PartsController.php");
require_once("./Controller/InspectionController.php");
require_once("./Controller/ResultController.php");
require_once("./Controller/UserController.php");
require_once("./Controller/DashboardController.php");
require_once("./Controller/LineNotifyController.php");

// สร้าง Class API
class API
{
    public $PartsController;
    public $UserController;
    public $AuthController;
    public $InspectionController;
    public $ResultController;
    public $DashboardController;
    public function __construct()
    {
        $this->AuthController = new AuthController();
        $this->PartsController = new PartsController();
        $this->InspectionController = new InspectionController();
        $this->ResultController = new ResultController();
        $this->UserController = new UserController();
        $this->DashboardController = new DashboardController();
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
                case "addUser":
                    $api->UserController->addUsers($req);
                    break;
                case "updateUser":
                    $api->UserController->updateUsers($req);
                    break;
                case "deleteUser":
                    $api->UserController->deleteUser($req);
                    break;
                case "login":
                    $api->AuthController->Login($req); //Method
                    break;
                case "auth":
                    $api->AuthController->AuthToken($req); //Method
                    break;
                case "getAllParts":
                    $api->PartsController->index(); //Method
                    break;
                case "getPartById":
                    $api->PartsController->getPartById($req);
                    break;
                case "addPart":
                    $api->PartsController->addPart($req);
                    break;
                case "getPathByBsthName":
                    $api->PartsController->getPathByBsthName($req);
                    break;
                case "getDrawingImagePath":
                    $api->PartsController->getDrawingImagePath($req);
                    break;
                case "updatePart":
                    $api->PartsController->UpdatePart($req);
                    break;
                case "deletePart":
                    $api->PartsController->deletePart($req);
                    break;
                case "inspection":
                    $api->InspectionController->Inspection($req);
                    break;
                case "getRawDataByPartNo":
                    $api->InspectionController->getRawDataByPartNo($req);
                    break;
                case "UpdateInspection":
                    $api->InspectionController->UpdateInspection($req);
                    break;
                case "historyInspection":
                    $api->InspectionController->historyInspection();
                    break;
                case "getInspectionNG":
                    $api->InspectionController->getInspectionNG();
                    break;
                case "getInspectionFG":
                    $api->InspectionController->getInspectionFG();
                    break;
                case "approveRecheck":
                    $api->InspectionController->approveRecheck($req);
                    break;
                case "acceptNG":
                    $api->InspectionController->acceptNG($req);
                    break;
                case "unApproveRecheck":
                    $api->InspectionController->unApproveRecheck($req);
                    break;
                case "getInspectionPartByDatetime":
                    $api->InspectionController->getInspectionPartByDatetime($req);
                    break;
                case "CheckInspectionNGStatus":
                    $api->InspectionController->CheckInspectionNGStatus($req);
                    break;
                case "countMoldOfPsthPart":
                    $api->InspectionController->countMoldOfPsthPart($req);
                    break;
                case "getListCavity":
                    $api->InspectionController->getListCavity($req);
                    break;
                case "getStandardInspection":
                    $api->InspectionController->index($req);
                    break;
                case "getStandardByPart":
                    $api->InspectionController->getStandardByPart($req);
                    break;
                case "getAllResult":
                    $api->ResultController->index();
                    break;
                case "getAmountInSystem":
                    $api->DashboardController->index();
                    break;
                case "getDataXBarChart":
                    $api->DashboardController->getDataXBarChart($req);
                    break;
                case "getXBarUCL":
                    $api->DashboardController->getXBarUCL($req);
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
