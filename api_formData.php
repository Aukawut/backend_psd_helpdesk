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
require_once("./Controller/UploadFileController.php");
class apiFormData
{
    public $UploadFileController;

    public function __construct()
    {
        $this->UploadFileController = new UploadFileController();
    }
}


$apiFormData = new apiFormData();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["router"]) &&  $_POST["router"] == "uploadFileImage") {
        $apiFormData->UploadFileController->uploadImage($_POST,$_FILES['file']);

    }
}
