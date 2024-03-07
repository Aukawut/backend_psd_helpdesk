<?php
class PartsController extends Model
{

    public function index()
    {
        try {
            $stmt = $this->conn->prepare("SELECT p.* ,i.FILENAME FROM TBL_INSPECTION_PART p LEFT JOIN TBL_IMAGES i ON p.BSNCR_PART_NO = i.BSNCR_PART_NO WHERE ACTIVE = 'Y' ORDER BY Id DESC");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function getPartById($req)
    {
        try {
            if (empty($req->id)) {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            } else {
                $stmt = $this->conn->prepare("SELECT * FROM TBL_INSPECTION_PART WHERE ACTIVE = 'Y' AND Id = ?");
                $stmt->execute([$req->id]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }

    public function addPart($req)
    {
        try {

            if (empty($req->bsthPartNo) || empty($req->partName) || empty($req->customerPartNo) || empty($req->customerName) || empty($req->model) || empty($req->justOn) || empty($req->resultCheck) || empty($req->standardValue)) {
                echo json_encode(["err" => true, "msg" => "Please completed information"]);
            } else {
                // Check Part ซ้ำในระบบ
                $bsthPartNo = $req->bsthPartNo;
                $stmt_check = $this->conn->prepare("SELECT * FROM TBL_INSPECTION_PART WHERE BSNCR_PART_NO = ? AND ACTIVE = 'Y'");
                $stmt_check->execute([preg_replace('/\s+/', '', $bsthPartNo)]);
                $results_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
                if ($results_check) {
                    echo json_encode(["err" => true, "msg" => "Part is duplicated!"]);

                    return;
                } else {
                    $point = count($req->standardValue);
                    $datenow = date('Y-m-d H:i:s');
                    $stmt_insert = $this->conn->prepare("INSERT INTO TBL_INSPECTION_PART (PART_NAME,BSNCR_PART_NO,CUSTOMER_PART_NO,MODEL,CUSTOMER_NAME,MEASURING,AMOUNT_POINT,ID_RESULT,JUST_ON,ACTIVE,CREATED_AT) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $countErrorStandardValue = 0;
                    $countEmptyLsl = 0;
                    $countEmptyUsl = 0;
                    $success = 0;
                    $stmt_insertStd = $this->conn->prepare("INSERT INTO TBL_STANDARD (POINT_NO,PART_NAME,BSNCR_PART_NO,CUSTOMER_PART_NO,LSL,USL,MODEL,CREATED_AT) VALUES (?,?,?,?,?,?,?,?)");
                    //Loop Check เงื่อนไขก่อน
                    foreach ($req->standardValue as $row) {
                        if ((float)$row->lsl >= (float)$row->usl) {
                            $countErrorStandardValue++;
                        }
                        if ($row->lsl == "" || empty($row->lsl)) {
                            $countEmptyLsl++;
                        }
                        if ($row->usl == "" || empty($row->usl)) {
                            $countEmptyUsl++;
                        }
                    }

                    if ($countErrorStandardValue !== 0) {
                        echo json_encode(["err" => true, "msg" => "Error LSL must be less than USL."]);
                        return;
                    } else if ($countEmptyLsl !== 0) {
                        echo json_encode(["err" => true, "msg" => "Please completed LSL!"]);
                        return;
                    } else if ($countEmptyUsl !== 0) {
                        echo json_encode(["err" => true, "msg" => "Please completed USL!"]);
                        return;
                    } else if (
                        $countErrorStandardValue == 0 && $countEmptyLsl == 0 && $countEmptyUsl == 0
                    ) {
                        foreach ($req->standardValue as $row) {
                            $success++;
                            // Loop Insert ไปยัง TBL_STANDARD
                            $stmt_insertStd->execute([$row->point, $req->partName, $bsthPartNo, $req->customerPartNo, $row->lsl, $row->usl, $req->model, $datenow]);
                        }
                        if ($success == count($req->standardValue)) {
                            //Insert ไปยัง TBL_INSPECTION_PART
                            $stmt_insert->execute([$req->partName, $bsthPartNo, $req->customerPartNo, $req->model, $req->customerName, 'Vernier', $point, $req->resultCheck, $req->justOn, 'Y', $datenow]);
                            echo json_encode(["err" => false, "msg" => "Part Added!", "status" => "Ok"]);
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function UpdatePart($req)
    {
        try {

            if (empty($req->bsthPartNo) || empty($req->partName) || empty($req->customerPartNo) || empty($req->customerName) || empty($req->model) || empty($req->justOn) || empty($req->standardValue)) {
                echo json_encode(["err" => true, "msg" => "Please completed information"]);
            } else {
                $bsthPartNo = preg_replace('/\s+/', '', $req->bsthPartNo);
                $point = count($req->standardValue);
                $datenow = date('Y-m-d H:i:s');
                $stmt_insertStd = $this->conn->prepare("INSERT INTO TBL_STANDARD (POINT_NO,PART_NAME,BSNCR_PART_NO,CUSTOMER_PART_NO,LSL,USL,MODEL,CREATED_AT) VALUES (?,?,?,?,?,?,?,?)");
                $countErrorStandardValue = 0;
                $countEmptyLsl = 0;
                $countEmptyUsl = 0;
                //Loop data json เพื่อตรวจสอบค่าด้านใน
                foreach ($req->standardValue as $row) {
                    if ((float)$row->lsl >= (float)$row->usl) {
                        $countErrorStandardValue++; // บวกค่า Error
                    }
                    if ($row->lsl == "" || empty($row->lsl)) {
                        $countEmptyLsl++; // บวกค่า Error
                    }
                    if ($row->usl == "" || empty($row->usl)) {
                        $countEmptyUsl++; // บวกค่า Error
                    }
                }
                //end Loop data json เพื่อตรวจสอบค่าด้านใน
                if ($countErrorStandardValue !== 0) {
                    echo json_encode(["err" => true, "msg" => "Error LSL must be less than USL."]);
                    return;
                } else if ($countEmptyLsl !== 0) {
                    echo json_encode(["err" => true, "msg" => "Please completed LSL!"]);
                    return;
                } else if ($countEmptyUsl !== 0) {
                    echo json_encode(["err" => true, "msg" => "Please completed USL!"]);
                    return;
                } else {
                    //Update Data Part Master
                    $stmt_select = $this->conn->prepare("UPDATE TBL_INSPECTION_PART SET AMOUNT_POINT = ? ,JUST_ON = ?, UPDATED_AT = ? WHERE BSNCR_PART_NO = ? AND PART_NAME = ? AND CUSTOMER_PART_NO = ?");
                    $stmt_select->execute([$point, $req->justOn, date('Y-m-d H:i:s'), $bsthPartNo, $req->partName, $req->customerPartNo]);

                    //Delete ออกจาก Standard
                    $stmt_delete = $this->conn->prepare("DELETE FROM TBL_STANDARD WHERE BSNCR_PART_NO = ? AND PART_NAME = ? AND CUSTOMER_PART_NO = ?");
                    $stmt_delete->execute([$bsthPartNo, $req->partName, $req->customerPartNo]);

                    foreach ($req->standardValue as $row) {
                        //Insert ไปยังค่าใหม่ Standard Part
                        $stmt_insertStd->execute([$row->point, $req->partName, $bsthPartNo, $req->customerPartNo, $row->lsl, $row->usl, $req->model, $datenow]);
                    }
                    echo json_encode(["err" => false, "msg" => "Part Updated!", "status" => "Ok"]);
                }
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function deletePart($req)
    {
        // print_r($req);
        try {
            $stmt_del = $this->conn->prepare("DELETE FROM TBL_INSPECTION_PART WHERE BSNCR_PART_NO = ? AND CUSTOMER_PART_NO = ? AND CUSTOMER_NAME = ?");
            $stmt_del->execute([$req->bsthPartNo, $req->customerPartNo, $req->customerName]);
            $stmt_del_standard = $this->conn->prepare("DELETE FROM TBL_STANDARD WHERE BSNCR_PART_NO = ?");
            $stmt_del_standard->execute([$req->bsthPartNo]);
            $stmt_del_master = $this->conn->prepare("UPDATE TBL_INSPECTION_MASTER SET ACTIVE = ? WHERE BSNCR_PART_NO = ?");
            $stmt_del_master->execute(['N', $req->bsthPartNo]);
            echo json_encode(["err" => false, "msg" => "Part Deleted!", "status" => "Ok"]);
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function getPathByBsthName($req)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM v_InspectionLists WHERE BSNCR_PART_NO = ? AND CUSTOMER_PART_NO = ? AND CUSTOMER_NAME = ?");
            $stmt->execute([$req->bsthPartNo, $req->customerPartNo, $req->customerName]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function getDrawingImagePath($req)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM TBL_IMAGES WHERE BSNCR_PART_NO = ? ");
            $stmt->execute([$req->bsthPartNo]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
}
