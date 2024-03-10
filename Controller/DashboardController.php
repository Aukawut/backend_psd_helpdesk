<?php
class DashboardController extends Model
{
    public function index()
    {
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as [ALL_USERS],(SELECT COUNT(*) FROM [dbo].[TBL_INSPECTION_MASTER]) as [All_Data]
            ,(SELECT COUNT(*) FROM [dbo].[TBL_INSPECTION_PART] a  WHERE a.ACTIVE = 'Y') as [PART_ALL] FROM [dbo].[TBL_USERS]");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["err" => false, "results" => $results]);
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function getDataXBarChart($req)
    {
        try {
            $stmt = $this->conn->prepare("SELECT TOP(30) x.[BSNCR_PART_NO]
            ,CONCAT(CONVERT(VARCHAR,[DATE_INSPECTION] ,106),' ',FORMAT([DATE_INSPECTION], 'HH:mm')) AS DATE
            ,[LOT_NO]
            ,[count]
            ,[AVG_BYTIME]
            ,[XBAR],
            [RBAR]
            ,x.[POINT_NO],
            x.MOLD_NO,
            s.LSL,
            s.USL
        FROM [dbo].[v_XBarChart] x LEFT JOIN [dbo].[TBL_STANDARD]s ON x.BSNCR_PART_NO = s.BSNCR_PART_NO 
        AND x.POINT_NO = s.POINT_NO  
		WHERE x.BSNCR_PART_NO = ?
		AND x.MOLD_NO = ? 
		AND x.DATE_INSPECTION <= ?
        ORDER BY x.BSNCR_PART_NO,x.DATE_INSPECTION DESC");
            $stmt->execute([$req->psthPartNo,$req->moldNo,$req->dateInspection]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "status" => "Ok", "result" => $result]);
            } else {
                echo json_encode(["err" => true, "status" => "Not Found", "result" => [], "msg" => "Not Found"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
  
    public function getXBarUCL($req)
    {
        try {
            
            $stmt_formula = $this->conn->prepare("SELECT * FROM TBL_FORMULA_STANDARD WHERE n = ?");
            $stmt_formula->execute([$req->n]);
            $result_formula = $stmt_formula->fetch(PDO::FETCH_ASSOC);
            $A2 = $result_formula['A2'];
            $D4 = $result_formula['D4'];
            $D3 = $result_formula['D3'];
            $sql = "{CALL dbo.sProcGetXBarUCL3 (?, ?, ?, ?, ?,?,?)}";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$req->psthPartNo,$req->dateInspection,$req->MoldNo,$req->top,$A2,$D4,$D3]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false,"status" => "Ok","result" => $result]);
            } else {
                echo json_encode(["err" => true, "msg" => "Not Found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    
}
