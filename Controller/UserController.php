<?php
date_default_timezone_set("Asia/Bangkok");


class UserController extends Model
{

    public function getUsers()
    {
        try {
            $stmt =  $this->conn->prepare("SELECT ROW_NUMBER() OVER(ORDER BY USER_ID) as [No], u.* FROM TBL_USERS u 
            WHERE u.ROLE != 'Super Admin'
            ORDER BY USER_ID ASC");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($result) {
                echo json_encode(["err" => false, "results" => $result, "status" => "Ok"]);
            } else {
                echo json_encode(["msg" => "User is not found!"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["err" => true, "msg" => $e->getMessage()]);
        }
    }
    public function addUsers($req)
    {
        // เช็คค่าวาง
        if (empty($req->username)  || empty($req->fname || empty($req->lname) || empty($req->department) || empty($req->position) || empty($req->role))) {
            echo json_encode(["err" => true, "msg" => "Please fill in complete information."]);
        } else {
            $username = preg_replace('/\s+/', '', $req->username); //Replace ช่องว่าง
            try {
                $stmt =  $this->conn->prepare("SELECT * FROM TBL_USERS WHERE LOGIN_DOMAIN = ? AND ACTIVE = 'Y'");
                $stmt->execute([$username]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($result) {
                    echo json_encode(["err" => true, "msg" => "Username is duplicated!"]);
                } else {
                    $stmt_insert = $this->conn->prepare("INSERT INTO TBL_USERS (LOGIN_DOMAIN,FULLNAME,CREATED_AT,ROLE,DEPARTNAME,POSITION,ACTIVE) VALUES (?,?,?,?,?,?,?)");
                    $stmt_insert->execute([$username, $req->fname . ' ' . $req->lname, date('Y-m-d H:i:s'), $req->role, $req->department, trim($req->position), 'Y']);
                    echo json_encode(["err" => false, "msg" => "Added!", "status" => "Ok"]);
                }
            } catch (PDOException $e) {
                echo json_encode(["err" => true, "msg" => $e->getMessage()]);
            }
        }
    }
    public function updateUsers($req)
    {
        // เช็คค่าวาง
        if (empty($req->username)  || empty($req->fname || empty($req->lname) || empty($req->department) || empty($req->position) || empty($req->role)) || empty($req->id)) {
            echo json_encode(["err" => true, "msg" => "Please fill in complete information."]);
        } else {
            $username = preg_replace('/\s+/', '', $req->username); //Replace ช่องว่าง
            try {
                $stmt =  $this->conn->prepare("SELECT * FROM TBL_USERS WHERE LOGIN_DOMAIN = ? AND ACTIVE = 'Y'");
                $stmt->execute([$username]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt_check = $this->conn->prepare("SELECT * FROM TBL_USERS WHERE USER_ID = ?");
                $stmt_check->execute([$req->id]);
                $result_check = $stmt_check->fetch(PDO::FETCH_ASSOC);
                $oldLogin = $result_check['LOGIN_DOMAIN'];
                if ($result && $oldLogin !== $username) {
                    echo json_encode(["err" => true, "msg" => "Username is duplicated!"]);
                } else {
                    $stmt_insert = $this->conn->prepare("UPDATE TBL_USERS SET LOGIN_DOMAIN = ?,FULLNAME = ?,UPDATED_AT = ?,ROLE = ?,DEPARTNAME = ?,POSITION = ? WHERE USER_ID = ?");
                    $stmt_insert->execute([$username, $req->fname . ' ' . $req->lname, date('Y-m-d H:i:s'), $req->role, $req->department, trim($req->position),$req->id]);
                    echo json_encode(["err" => false, "msg" => "Updated!", "status" => "Ok"]);
                }
            } catch (PDOException $e) {
                echo json_encode(["err" => true, "msg" => $e->getMessage()]);
            }
        }
    }
    
    public function deleteUser($req)
    {
        // เช็คค่าวาง
        $id = $req->id;
        if (empty($id)) {
            echo json_encode(["err" => true, "msg" => "Opps.."]);
        } else {
            try {
                $stmt_select = $this->conn->prepare("SELECT * FROM TBL_USERS WHERE USER_ID = ?");
                $stmt_select->execute([$id]);
                $result = $stmt_select->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $stmt_delete = $this->conn->prepare("DELETE FROM TBL_USERS WHERE USER_ID = ?");
                    $stmt_delete->execute([$id]);
                    echo json_encode(["err" => false, "msg" => "User Deleted!", "status" => "Ok"]);
                } else {
                    echo json_encode(["err" => true, "msg" => "User not found!"]);
                }
            } catch (PDOException $e) {
                echo json_encode(["err" => true, "msg" => $e->getMessage()]);
            }
        }
    }
}
