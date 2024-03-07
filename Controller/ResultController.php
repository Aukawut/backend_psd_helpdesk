<?php

class ResultController extends Model {

    public function index(){
        try{
            $stmt = $this->conn->prepare("SELECT * FROM TBL_RESULTS ORDER BY ID_RESULT ASC");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($result){
                echo json_encode(["err" => false,"status" => "Ok","result" => $result]);
            }
           
        }catch(PDOException $e){
            echo json_encode(["err" => true,"msg" => $e->getMessage()]);
        }
    }
}
?>