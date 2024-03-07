<?php

class InspectionController extends Model
{


    public function index($req)
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM v_InspectionLists WHERE [BSNCR_PART_NO] = ? ORDER BY BSNCR_PART_NO,POINT_NO,NAME_RESULT");
            $stmt->execute([$req->bsthPartNo]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" =>  $result, "status" => "Ok"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function getStandardByPart($req)
    {
        try {
            $stmt = $this->conn->prepare("SELECT s.*,i.FILENAME ,p.[JUST_ON]FROM TBL_STANDARD s 
            LEFT JOIN [dbo].[TBL_IMAGES] i ON i.[BSNCR_PART_NO] = s.BSNCR_PART_NO
			LEFT JOIN [dbo].[TBL_INSPECTION_PART]p ON s.BSNCR_PART_NO = p.BSNCR_PART_NO
            WHERE s.[BSNCR_PART_NO] =  ?
            ORDER BY POINT_NO");
            $stmt->execute([$req->bsthPartNo]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" =>  $result, "status" => "Ok"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function checkNg($psthPartNo, $mold, $date, $time, $inspector)
    {

        $stmt = $this->conn->prepare("SELECT a.* 
        FROM (
            SELECT ROW_NUMBER() OVER(ORDER BY c.[BSNCR_PART_NO] ASC) AS NO,
                   COUNT([Id]) AS COUNT,
                   SUM(CASE WHEN STATUS_PART = 'FG' THEN 1 ELSE 0 END) AS FG,
                   SUM(CASE WHEN STATUS_PART = 'NG' THEN 1 ELSE 0 END) AS NG,
                   c.[BSNCR_PART_NO],
                   c.LOT_NO,
                   c.DATE,
                   c.TIME,
                   c.MOLD_NO,
                   c.JUDGEMENT,
                   c.INSPECTOR,
                   c.APPROVE,
                   c.[WAIT_REINSPECTION],
                   CASE WHEN c.APPROVE = 'Y' AND c.WAIT_REINSPECTION = 'Y' THEN 'Waiting User Re-check' ELSE 'Waiting Approve' END AS APPROVAL_STATUS
            FROM [QC_INSPECTION].[dbo].[v_FinalStatusQC_Checked] c
            GROUP BY c.[BSNCR_PART_NO], c.DATE, c.TIME, c.INSPECTOR, c.MOLD_NO, c.JUDGEMENT, c.LOT_NO, c.APPROVE, c.[WAIT_REINSPECTION]
        ) a WHERE a.BSNCR_PART_NO = ? AND a.DATE = ? AND a.TIME = ? AND a.MOLD_NO = ? AND a.NG > 0
        
        ORDER BY a.[BSNCR_PART_NO], a.DATE, a.TIME DESC");
        $stmt->execute([$psthPartNo, $date, $time, $mold]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->LineNotifyController->alertNotify($psthPartNo, $date, $time, $inspector, $mold);
        }
    }
    public function Inspection($req)
    {
        // print_r(($req));
        if (empty($req->bsthPartNo) || empty($req->moldNo) || empty($req->inspector) || empty($req->lotNo)) {
            echo json_encode(["err" => true, "msg" => "Data is Empty!"]);
        } else {
            $psthPartNo = $req->bsthPartNo;
            $moldNo = $req->moldNo;
            $inspector = $req->inspector;
            $date = date("Y-m-d") . ' ' . $req->time;
            $lotNo = $req->lotNo;
            $datenow = date("Y-m-d H:i:s");
            $approve = 'N';
            $stmt_check = $this->conn->prepare("SELECT m.* FROM TBL_INSPECTION_MASTER m 
            WHERE m.BSNCR_PART_NO = ? AND m.MOLD_NO = ? 
            AND TRY_CONVERT(VARCHAR(10),m.DATE_INSPECTION,120) = ? 
            AND TRY_CONVERT(VARCHAR(5),m.DATE_INSPECTION,108) = ?");
            $stmt_check->execute([$psthPartNo, $moldNo, date("Y-m-d"), $req->time]);
            $result_check = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
            if ($result_check) {
                echo json_encode(["err" => true,"msg" => "Cannot check again."]);
            } else {
                if (count($req->value) === 2) {
                    try {
                        $stmt_insert = $this->conn->prepare("INSERT INTO TBL_INSPECTION_MASTER (BSNCR_PART_NO,LOT_NO,CAVITY_NO,INSPECTION_VALUE,SIMPLE,ID_RESULT,INSPECTOR,DATE_INSPECTION,MOLD_NO,POINT_NO,TIMESTAMP,APPROVE,WAIT_REINSPECTION) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $arraySample1 = $req->value[0]; // array ที่ส่งมาจากหน้าบ้าน (Sample 1)
                        $arraySample2 = $req->value[1]; // array ที่ส่งมาจากหน้าบ้าน (Sample 2)

                        // บันทึกทีละ Sample
                        for ($i = 0; $i < count($arraySample1->valueCheck); $i++) {
                            //Loop Insert Sample 1
                            $pointSample1 = $i + 1;
                            $simple = 1;
                            foreach ($arraySample1->valueCheck[$i]->valueCheck as $key) {
                                $stmt_insert->execute([$psthPartNo, $lotNo, $arraySample1->cavity, $key->value, $simple, $key->id_result, $inspector, $date, $moldNo, $pointSample1, $datenow, $approve, 'N']);
                            }
                        }
                        for ($j = 0; $j < count($arraySample2->valueCheck); $j++) {
                            //Loop Insert Sample 2
                            $pointSample2 = $j + 1;
                            $simple = 2;
                            foreach ($arraySample2->valueCheck[$j]->valueCheck as $key) {
                                $stmt_insert->execute([$psthPartNo, $lotNo, $arraySample2->cavity, $key->value2, $simple, $key->id_result, $inspector, $date, $moldNo, $pointSample2, $datenow, $approve, 'N']);
                            }
                        }
                        echo json_encode(["err" => false, "msg" => "Success!", "status" => "Ok"]);

                        $this->checkNg($psthPartNo, $moldNo, date("Y-m-d"), $req->time, $inspector); //Check Ng
                    } catch (PDOException $e) {
                        echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
                    }
                } else {
                    echo json_encode(["err" => true, "msg" =>  "Something went wrong!"]);
                }
            }
        }
    }
    public function UpdateInspection($req)
    {
        // print_r(($req));
        if (empty($req->bsthPartNo) || empty($req->moldNo) || empty($req->inspector) || empty($req->lotNo)) {
            echo json_encode(["err" => true, "msg" => "Data is Empty!"]);
        } else {
            $psthPartNo = $req->bsthPartNo;
            $moldNo = $req->moldNo;
            $inspector = $req->inspector;
            $dateOld = $req->date;
            // $date = date("Y-m-d") . ' ' . $req->time;
            $lotNo = $req->lotNo;
            $datenow = date("Y-m-d H:i:s");
            $approve = 'N';
            $wait = 'N';

            if (count($req->value) === 2) {
                try {

                    $stmt_update = $this->conn->prepare("UPDATE [dbo].[TBL_INSPECTION_MASTER] 
                    SET [INSPECTION_VALUE] = ? ,[APPROVE] = ?,[WAIT_REINSPECTION] = ?,[UPDATED_AT] = ?
                    WHERE BSNCR_PART_NO = ? 
                    AND MOLD_NO = ? 
                    AND TRY_CONVERT(VARCHAR(10),DATE_INSPECTION,120) = ? 
                    AND TRY_CONVERT(VARCHAR(5),DATE_INSPECTION,108) = ? 
                    AND ID_RESULT = ? AND SIMPLE = ? AND POINT_NO = ? AND [LOT_NO] = ?");

                    $arraySample1 = $req->value[0]; // array ที่ส่งมาจากหน้าบ้าน (Sample 1)
                    $arraySample2 = $req->value[1]; // array ที่ส่งมาจากหน้าบ้าน (Sample 2)
                    $stmt_select = $this->conn->prepare("SELECT m.* FROM TBL_INSPECTION_MASTER m 
                    WHERE m.BSNCR_PART_NO = ? AND m.MOLD_NO = ? 
                    AND TRY_CONVERT(VARCHAR(10),m.DATE_INSPECTION,120) = ? 
                    AND TRY_CONVERT(VARCHAR(5),m.DATE_INSPECTION,108) = ?");
                    $stmt_select->execute([$psthPartNo, $moldNo, $dateOld, $req->time]);
                    $result_select = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
                    if ((count($arraySample1->valueCheck[0]->valueCheck) * 2) == count($result_select)) {
                        // บันทึกทีละ Sample
                        for ($i = 0; $i < count($arraySample1->valueCheck); $i++) {
                            //Loop Insert Sample 1
                            $pointSample1 = $i + 1;
                            $simple = 1;
                            foreach ($arraySample1->valueCheck[$i]->valueCheck as $key) {
                                $stmt_update->execute([$key->value, $approve, $wait, $datenow, $psthPartNo, $moldNo, $dateOld, $req->time, $key->id_result, $simple, $pointSample1, $lotNo]);
                            }
                        }
                        for ($j = 0; $j < count($arraySample2->valueCheck); $j++) {
                            //Loop Insert Sample 2
                            $pointSample2 = $j + 1;
                            $simple = 2;
                            foreach ($arraySample2->valueCheck[$j]->valueCheck as $key) {
                                $stmt_update->execute([$key->value2, $approve, $wait, $datenow, $psthPartNo, $moldNo, $dateOld, $req->time, $key->id_result, $simple, $pointSample2, $lotNo]);
                            }
                        }
                        echo json_encode(["err" => false, "msg" => "Updated!", "status" => "Ok"]);

                        $this->checkNg($psthPartNo, $moldNo, $dateOld, $req->time, $inspector); //Check Ng
                    } else {
                        echo json_encode(["err" => true, "msg" => "Master Record Error!"]);
                    }
                } catch (PDOException $e) {
                    echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
                }
            } else {
                echo json_encode(["err" => true, "msg" =>  "Something went wrong!"]);
            }
        }
    }
    public function countMoldOfPsthPart($req)
    {
        try {
            $stmt =   $this->conn->prepare("SELECT 
            COUNT(*) AS COUNT,BSNCR_PART_NO,MOLD_NO FROM [dbo].[TBL_INSPECTION_MASTER]  WHERE BSNCR_PART_NO = ?
            GROUP BY MOLD_NO,BSNCR_PART_NO");
            $stmt->execute([$req->psthPartNo]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function historyInspection()
    {
        try {
            $stmt =   $this->conn->prepare("SELECT ROW_NUMBER() OVER(ORDER BY c.[BSNCR_PART_NO] ASC) AS NO,COUNT([Id]) AS COUNT,
            SUM(CASE WHEN STATUS_PART = 'FG'THEN 1 ELSE 0  END) AS FG,
            SUM(CASE WHEN STATUS_PART = 'NG'THEN 1 ELSE 0  END) AS NG,
                  c.[BSNCR_PART_NO],c.LOT_NO,c.DATE,c.TIME,c.MOLD_NO,c.JUDGEMENT,c.INSPECTOR,c.[LOT_NO],c.APPROVE,c.[WAIT_REINSPECTION]
              FROM [QC_INSPECTION].[dbo].[v_FinalStatusQC_Checked]c
              GROUP BY  c.[BSNCR_PART_NO],c.DATE,c.TIME,c.INSPECTOR,c.MOLD_NO  ,c.JUDGEMENT,c.[LOT_NO],c.APPROVE,c.[WAIT_REINSPECTION]
              ORDER BY c.[BSNCR_PART_NO],c.DATE,c.TIME DESC");
            $stmt->execute([]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function getInspectionNG()
    {
        try {
            $stmt =   $this->conn->prepare("SELECT a.* 
            FROM (
                SELECT ROW_NUMBER() OVER(ORDER BY c.[BSNCR_PART_NO] ASC) AS NO,
                       COUNT([Id]) AS COUNT,
                       SUM(CASE WHEN STATUS_PART = 'FG' THEN 1 ELSE 0 END) AS FG,
                       SUM(CASE WHEN STATUS_PART = 'NG' THEN 1 ELSE 0 END) AS NG,
                       c.[BSNCR_PART_NO],
                       c.LOT_NO,
                       c.DATE,
                       c.TIME,
                       c.MOLD_NO,
                       c.JUDGEMENT,
                       c.INSPECTOR,
                       c.APPROVE,
                       c.[WAIT_REINSPECTION],
                       CASE WHEN c.APPROVE = 'Y' AND c.WAIT_REINSPECTION = 'Y' THEN 'Waiting User Re-check' ELSE 'Waiting Approve' END AS APPROVAL_STATUS
                FROM [QC_INSPECTION].[dbo].[v_FinalStatusQC_Checked] c
                GROUP BY c.[BSNCR_PART_NO], c.DATE, c.TIME, c.INSPECTOR, c.MOLD_NO, c.JUDGEMENT, c.LOT_NO, c.APPROVE, c.[WAIT_REINSPECTION]
            ) a 
            WHERE a.NG > 0
            ORDER BY a.[BSNCR_PART_NO], a.DATE, a.TIME DESC");
            $stmt->execute([]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function getInspectionFG()
    {
        try {
            $stmt =   $this->conn->prepare("SELECT a.* 
            FROM (
                SELECT ROW_NUMBER() OVER(ORDER BY c.[BSNCR_PART_NO] ASC) AS NO,
                       COUNT([Id]) AS COUNT,
                       SUM(CASE WHEN STATUS_PART = 'FG' THEN 1 ELSE 0 END) AS FG,
                       SUM(CASE WHEN STATUS_PART = 'NG' THEN 1 ELSE 0 END) AS NG,
                       c.[BSNCR_PART_NO],
                       c.LOT_NO,
                       c.DATE,
                       c.TIME,
                       c.MOLD_NO,
                       c.JUDGEMENT,
                       c.INSPECTOR,
                       c.APPROVE,
                       c.[WAIT_REINSPECTION],
                       CASE WHEN c.APPROVE = 'Y' AND c.WAIT_REINSPECTION = 'Y' THEN 'Waiting User Re-check' ELSE 'Waiting Approve' END AS APPROVAL_STATUS
                FROM [QC_INSPECTION].[dbo].[v_FinalStatusQC_Checked] c
                GROUP BY c.[BSNCR_PART_NO], c.DATE, c.TIME, c.INSPECTOR, c.MOLD_NO, c.JUDGEMENT, c.LOT_NO, c.APPROVE, c.[WAIT_REINSPECTION]
            ) a 
            WHERE a.NG = 0
            ORDER BY a.[BSNCR_PART_NO], a.DATE, a.TIME DESC");
            $stmt->execute([]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function getInspectionPartByDatetime($req)
    {
        try {

            $stmt =   $this->conn->prepare("SELECT f.*
            FROM [dbo].[v_FinalStatusQC_Checked] f 
            WHERE  f.BSNCR_PART_NO = ? 
            AND f.DATE = ? AND f.TIME = ? AND f.MOLD_NO = ? ORDER BY POINT_NO,SIMPLE");
            $stmt->execute([$req->psthPartNo, $req->date, $req->time, $req->mold]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function getListCavity($req)
    {
        try {
            $stmt = $this->conn->prepare("SELECT TOP 2 COUNT(m.SIMPLE) AS COUNT, m.SIMPLE,m.CAVITY_NO FROM TBL_INSPECTION_MASTER m 
            WHERE m.BSNCR_PART_NO = ? AND m.MOLD_NO = ? 
            AND TRY_CONVERT(VARCHAR(10),m.DATE_INSPECTION,120) = ?
            AND TRY_CONVERT(VARCHAR(5),m.DATE_INSPECTION,108) = ?
			GROUP BY m.SIMPLE,m.CAVITY_NO
			ORDER BY m.CAVITY_NO");
            $stmt->execute([$req->psthPartNo, $req->mold, $req->date, $req->time]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function approveRecheck($req)
    {
        try {
            $stmt_select  = $this->conn->prepare("SELECT m.* FROM TBL_INSPECTION_MASTER m 
            WHERE m.BSNCR_PART_NO = ? AND m.MOLD_NO = ? 
            AND TRY_CONVERT(VARCHAR(10),m.DATE_INSPECTION,120) = ? 
            AND TRY_CONVERT(VARCHAR(5),m.DATE_INSPECTION,108) = ?
            ");
            $stmt_select->execute([$req->psthPartNo, $req->mold, $req->date, $req->time]);
            $result_select = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
            if ($result_select) {
                $stmt_update = $this->conn->prepare("UPDATE TBL_INSPECTION_MASTER 
                SET APPROVE = ? , WAIT_REINSPECTION = ? ,SUPERVISOR = ?
                WHERE BSNCR_PART_NO = ? 
                AND TRY_CONVERT(VARCHAR(10),DATE_INSPECTION,120) = ? 
                AND TRY_CONVERT(VARCHAR(5),DATE_INSPECTION,108) = ? 
                AND MOLD_NO = ?");
                $stmt_update->execute(['Y', 'Y', $req->approver, $req->psthPartNo, $req->date, $req->time, $req->mold]);
                echo json_encode(["err" => false, "msg" => "Updated", "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Something went wrong!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function unApproveRecheck($req)
    {
        try {
            $stmt_select  = $this->conn->prepare("SELECT m.* FROM TBL_INSPECTION_MASTER m 
            WHERE m.BSNCR_PART_NO = ? AND m.MOLD_NO = ? 
            AND TRY_CONVERT(VARCHAR(10),m.DATE_INSPECTION,120) = ? 
            AND TRY_CONVERT(VARCHAR(5),m.DATE_INSPECTION,108) = ?
            ");
            $stmt_select->execute([$req->psthPartNo, $req->mold, $req->date, $req->time]);
            $result_select = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
            if ($result_select) {
                $stmt_update = $this->conn->prepare("UPDATE TBL_INSPECTION_MASTER 
                SET APPROVE = ? , WAIT_REINSPECTION = ? ,SUPERVISOR = ?
                WHERE BSNCR_PART_NO = ? 
                AND TRY_CONVERT(VARCHAR(10),DATE_INSPECTION,120) = ? 
                AND TRY_CONVERT(VARCHAR(5),DATE_INSPECTION,108) = ? 
                AND MOLD_NO = ?");
                $stmt_update->execute(['N', 'N', $req->approver, $req->psthPartNo, $req->date, $req->time, $req->mold]);
                echo json_encode(["err" => false, "msg" => "Updated", "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Something went wrong!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
    public function CheckInspectionNGStatus($req)
    {
        try {
            $stmt =   $this->conn->prepare("WITH CTE AS (
                SELECT a.* 
                            FROM (
                                SELECT ROW_NUMBER() OVER(ORDER BY c.[BSNCR_PART_NO] ASC) AS NO,
                                       COUNT([Id]) AS COUNT,
                                       SUM(CASE WHEN STATUS_PART = 'FG' THEN 1 ELSE 0 END) AS FG,
                                       SUM(CASE WHEN STATUS_PART = 'NG' THEN 1 ELSE 0 END) AS NG,
                                       c.[BSNCR_PART_NO],
                                       c.LOT_NO,
                                       c.DATE,
                                       c.TIME,
                                       c.MOLD_NO,
                                       c.JUDGEMENT,
                                       c.INSPECTOR,
                                       c.APPROVE,
                                       c.[WAIT_REINSPECTION],
                                       CASE WHEN c.APPROVE = 'Y' 
                                       AND c.WAIT_REINSPECTION = 'Y' 
                                       THEN 'Waiting User Re-check' 
                                       ELSE 'Waiting Approve' END AS APPROVAL_STATUS
                                FROM [QC_INSPECTION].[dbo].[v_FinalStatusQC_Checked] c
                                GROUP BY c.[BSNCR_PART_NO], c.DATE, c.TIME, c.INSPECTOR, c.MOLD_NO, c.JUDGEMENT, c.LOT_NO, c.APPROVE, c.[WAIT_REINSPECTION]
                            ) a 
                           WHERE a.NG > 0
                            ) 
                            SELECT j.* from CTE j 
                            WHERE j.BSNCR_PART_NO = ? AND j.DATE = ? AND j.TIME = ? AND j.MOLD_NO = ?
                            ORDER BY j.[BSNCR_PART_NO], j.DATE, j.TIME DESC");
            $stmt->execute([$req->psthPartNo, $req->date, $req->time, $req->mold]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "result" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" =>  $e->getMessage()]);
        }
    }
}
