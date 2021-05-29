<?php

include_once __DIR__."/../abstract/module.php";

use \server\abstracts\module;

class managelinks extends module{

    public function __construct(){
        parent::__construct();
        $this->queryErrMsg = "Sorry, you have to be"
            ." logged in to perform this action";
        $this->repFailTemplate["errors"]['errMsg'] = "something went wrong,"
            ." please contact site admin";
    }

    public function process(){
        $limit = $this->inputs['limit'];
        if (!empty($limit))
            return $this->queryFiles(($limit));
        $id = $this->inputs['id'];
        $status = $this->inputs['update'];
        // restricted to only two values
        if(!empty($id) && !empty($status) && in_array($status,['Y','N'])){
            return $this->updateDownload($id,$status);
        }
        return $this;

    }

    private function queryFiles($limit){
        // validate limit from client side
        if($limit >= 10000) {
            $this->repFailTemplate["errors"]['errMsg']
                = "sorry, you are only allowed to query untill 10000";
            $this->response = $this->repFailTemplate;
            return $this;
        }

        // only allow 10 to be queried each time
        $limitCondition = " " . ($limit - 10) . ", 10";
        $prepedSql = $this->database->prepare(
            "SELECT 
                download_name,path_of_file,status, download_id as id
            FROM 
                download_details
            ORDER BY 
                download_id ASC LIMIT " 
            . $limitCondition
        );
       
        $prepedSql->execute();
        if ($prepedSql->rowCount() >  0){
            $linksSelect = $prepedSql->fetchAll(PDO::FETCH_ASSOC);
            $this->respSuccessTemplate["content"]["list"] = 
                json_encode($linksSelect);
            // total limit of the table
            $rows = $this->database->query("SELECT COUNT(*) FROM download_details")->fetchColumn();
            $this->respSuccessTemplate["content"]["limit"] = max(0, $rows);
            $this->response = $this->respSuccessTemplate;
        } else {
            $this->repFailTemplate["errors"]['errMsg']
            = "sorry, End of the list reached";
            $this->response = $this->repFailTemplate;
        }


        return $this;
    }

    private function updateDownload($id,$status){
        $preparedSql = $this->database->prepare("
            UPDATE 
                download_details
            SET
                status = :status
            WHERE 
                download_id = :id 
        ");
        if($preparedSql->execute(['status'=>$status, 'id'=>$id])){
            $this->respSuccessTemplate["content"]["status"]
                 = "item updated";
            $this->response = $this->respSuccessTemplate;
        } else {
            $this->repFailTemplate["errors"]['errMsg']
            = "sorry, can not update the item";
            $this->response = $this->repFailTemplate;
        }
        return $this;

    }

    public function getResponse(){
        echo json_encode($this->response);
    }
}

?>