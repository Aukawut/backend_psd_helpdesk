<?php
date_default_timezone_set("Asia/Bangkok");

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Model
{
    public function GenarateToken($payload)
    {
        $key = $_ENV["JWT_SECRET"];
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }
    public function DecodeJWTToken($token)
    {
        try {
            $key = $_ENV["JWT_SECRET"];
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return ["err" => false, "decoded" => $decoded];
        } catch (Exception $e) {
            return ["err" => true, "msg" => $e->getMessage()];
        }
    }
    public function AuthToken($req)
    {

        if (empty($req->token)) {
            echo json_encode(["err" => true, "msg" => "Token is empty!"]);
        } else {

            $decoded = $this->DecodeJWTToken($req->token);
            if ($decoded["err"] !== false) {
                echo json_encode(["err" => true, "msg" => $decoded["msg"]]);
            } else {
                echo json_encode(["err" => false, "msg" => "Authen success!", "info" => $decoded]);
            }
        }
    }
    public function Login($req)
    {
        if (empty($req->username) || empty($req->password)) {
            echo json_encode(["err" => true, "msg" => "Username or Password Invalid!"]);
        } else {
            try {
                $username = $req->username;
                $password = $req->password;
                $server = "10.144.1.1";
                $ldap_connection = ldap_connect($server);

                if ($ldap_connection === FALSE) {
                    die("Connect not connect to " . $server);
                    echo json_encode(["err" => true, "msg" => "ไม่สามารถติดต่อ server ได้"]);
                    exit();
                } else {
                    $ldap_username = $username . "@BSNCR.COM";
                    $ldap_password  = $password;
                    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0);
                    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
                    if (@ldap_bind($ldap_connection, $ldap_username, $ldap_password) === FALSE) {
                        echo json_encode(["err" => true, "msg" => "Password invalid!"]);
                    } else if (ldap_bind($ldap_connection, $ldap_username, $ldap_password) === TRUE) {
                        $ldap_base_dn = 'DC=BSNCR,DC=com';
                        //$search_filter = '(|(objectCategory=person)(objectCategory=contact))';
                        $search_filter = "(|(samaccountname=$username))";
                        $result = ldap_search($ldap_connection, $ldap_base_dn, $search_filter);
                        if ($result !== FALSE) {
                            $entries = ldap_get_entries($ldap_connection, $result);

                            for ($x = 0; $x < $entries['count']; $x++) {

                                //Windows Usernaame
                                $LDAP_samaccountname = "";

                                if (!empty($entries[$x]['samaccountname'][0])) {
                                    $LDAP_samaccountname = $entries[$x]['samaccountname'][0];
                                    if ($LDAP_samaccountname == "NULL") {
                                        $LDAP_samaccountname = "";
                                    }
                                } else {
                                    //#There is no samaccountname s0 assume this is an AD contact record so generate a unique username

                                    $LDAP_uSNCreated = $entries[$x]['usncreated'][0];
                                    $LDAP_samaccountname = "CONTACT_" . $LDAP_uSNCreated;
                                }
                                //DisplayName
                                $LDAP_DisplayName = "";
                                if (!empty($entries[$x]["displayname"][0])) {
                                    $LDAP_DisplayName = $entries[$x]["displayname"][0];
                                    if ($LDAP_DisplayName == "NULL") {
                                        $LDAP_DisplayName = "";
                                    }
                                }
                                //Description
                                $LDAP_Description = "";

                                if (!empty($entries[$x]['description'][0])) {
                                    $LDAP_Description = $entries[$x]['description'][0];
                                    if ($LDAP_Description == "NULL") {
                                        $LDAP_Description = "";
                                    }
                                }

                                //First Name
                                $LDAP_FirstName = "";

                                if (!empty($entries[$x]['givenname'][0])) {
                                    $LDAP_FirstName = $entries[$x]['givenname'][0];
                                    if ($LDAP_FirstName == "NULL") {
                                        $LDAP_FirstName = "";
                                    }
                                }

                                //Last Name
                                $LDAP_LastName = "";

                                if (!empty($entries[$x]['sn'][0])) {
                                    $LDAP_LastName = $entries[$x]['sn'][0];
                                    if ($LDAP_LastName == "NULL") {
                                        $LDAP_LastName = "";
                                    }
                                }

                                //P.O. Box
                                $LDAP_POBox = "";

                                if (!empty($entries[$x]['postofficebox'][0])) {
                                    $LDAP_POBox = $entries[$x]['postofficebox'][0];
                                    if ($LDAP_POBox == "NULL") {
                                        $LDAP_POBox = "";
                                    }
                                }

                                //Company
                                $LDAP_CompanyName = "";

                                if (!empty($entries[$x]['company'][0])) {
                                    $LDAP_CompanyName = $entries[$x]['company'][0];
                                    if ($LDAP_CompanyName == "NULL") {
                                        $LDAP_CompanyName = "";
                                    }
                                }

                                //Department
                                $LDAP_Department = "";

                                if (!empty($entries[$x]['department'][0])) {
                                    $LDAP_Department = $entries[$x]['department'][0];
                                    if ($LDAP_Department == "NULL") {
                                        $LDAP_Department = "";
                                    }
                                }

                                //Job Title
                                $LDAP_JobTitle = "";

                                if (!empty($entries[$x]['title'][0])) {
                                    $LDAP_JobTitle = $entries[$x]['title'][0];
                                    if ($LDAP_JobTitle == "NULL") {
                                        $LDAP_JobTitle = "";
                                    }
                                }

                                //Email address
                                $LDAP_InternetAddress = "";

                                if (!empty($entries[$x]['mail'][0])) {
                                    $LDAP_InternetAddress = $entries[$x]['mail'][0];
                                    if ($LDAP_InternetAddress == "NULL") {
                                        $LDAP_InternetAddress = "";
                                    }
                                }

                                //Status
                                $LDAP_Useraccountcontrol = "";

                                if (!empty($entries[$x]['pwdlastset'][0])) {
                                    $LDAP_Useraccountcontrol = $entries[$x]['pwdlastset'][0];
                                    if ($LDAP_Useraccountcontrol == "NULL") {
                                        $LDAP_Useraccountcontrol = "";
                                    }
                                }
                                $stmt_user = $this->conn->prepare("SELECT * FROM TBL_USERS WHERE LOGIN_DOMAIN = ? AND ACTIVE = 'Y'");
                                $stmt_user->execute([$username]);
                                $result_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
                                if ($result_user) {
                                    $payload = [
                                        "LDAP_samaccountname" => $LDAP_samaccountname,
                                        "LDAP_DisplayName" => $LDAP_DisplayName,
                                        "LDAP_Description" => $LDAP_Description,
                                        "LDAP_FirstName" => $LDAP_FirstName,
                                        "LDAP_LastName" => $LDAP_LastName,
                                        "LDAP_POBox" => $LDAP_POBox,
                                        "LDAP_CompanyName" => $LDAP_CompanyName,
                                        "LDAP_Department" => $LDAP_Department,
                                        "LDAP_InternetAddress" => $LDAP_InternetAddress,
                                        "ROLE" => $result_user['ROLE'],
                                        "FULLNAME" => $result_user['FULLNAME'],
                                        "POSITION" => $result_user['POSITION'],
                                        "DEPARTNAME" => $result_user['DEPARTNAME'],
                                        "iat" => time(),
                                        "exp" => time() + (180 * 60) //3 ชั่วโมง

                                    ];
                                    //สร้าง JWT Token
                                    $token = $this->GenarateToken($payload);
                                    echo json_encode([
                                        "err" => false,
                                        "infomationLdap" => $payload,
                                        "msg" => "Login success!",
                                        "token" => $token,
                                        "status" => "Ok"
                                    ]);
                                } else {
                                    echo json_encode(["err" => true, "msg" => "Sorry! you don't have permission!"]);
                                }
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                echo json_encode($e->getMessage());
            }
        }
    }
}
