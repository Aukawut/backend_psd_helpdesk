<?php

class UploadFileController extends Model
{
    public function uploadImage($post, $files)
    {
        $targetDir = "uploads/drawing/"; // Specify the directory where you want to store uploaded files
        $allowedTypes = array('image/png', 'image/jpeg'); // Allowed file types
        $maxFileSize = 2 * 1024 * 1024; // Maximum file size (2MB)

        // Check if file was uploaded without errors
        // Check file type
        if (!in_array($files["type"], $allowedTypes)) {
            echo json_encode(["err" => true, "msg" => "Sorry, only PNG and JPEG files are allowed."]);
            exit();
        }

        // Check file size
        if ($files["size"] > $maxFileSize) {
            echo json_encode(["err" => true, "msg" => "Sorry, your file is too large. Maximum size allowed is 2MB."]);

            exit();
        }
        $uuid = uniqid(); // Generate a unique identifier (UUID)
        $fileNameNew = $uuid . '-' . $files["name"];
        $targetFile = $targetDir  . $fileNameNew; // Construct the unique filename


        // Attempt to move the uploaded file to the specified directory
        if (move_uploaded_file($files["tmp_name"], $targetFile)) {
            $stmt = $this->conn->prepare("INSERT INTO TBL_IMAGES (BSNCR_PART_NO,FILENAME,CREATED_AT) VALUES (?,?,?)");
            $stmt->execute([$post["bsthPartNo"], $fileNameNew, date("Y-m-d H:i:s")]);
            echo json_encode(["err" => false, "status" => "Ok", "msg" => "uploaded", "filename" => $fileNameNew]);
        } else {
            echo json_encode(["err" => true, "msg" => "Sorry, there was an error uploading your file."]);
        }
    }
}
