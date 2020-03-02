<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET, POST, OPTIONS,PUT, DELETE");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, jwt");
header('Content-Type: application/json');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;

require '../vendor/autoload.php'; 
include 'lib/FileMaker.php';

$app = new \Slim\App;

/**
 * The below endpoint is used to create a new user in the table
 */
$app->post('/users',function(Request $request,Response $response,array $args){

    $fm=new FileMaker('users1.fmp12','172.16.9.184','admin','mindfire');
    
    $parsedBody = $request->getParsedBody();

    $firstname = $parsedBody['firstname'] ?? false;
    $lastname = $parsedBody['lastname'] ?? false;
    $emailid = $parsedBody['emailid'] ?? false;
    $contactno = $parsedBody['contactno'] ?? false;
    $adminemail = $parsedBody['adminemail'] ?? false;
    $password = $parsedBody['password'] ?? false;

    $q = $fm -> newFindCommand('users');
    $q -> addFindCriterion('email_id', '=='.$emailid);
    $result = $q->execute();
    if(!(FileMaker::isError($result))){
        return $response->withJson(array("success" => 0,"message"=>"email already taken"),200);
    }


    $newRecord=$fm->newAddCommand('users');
    $newRecord->setField('first_name',$firstname);
    $newRecord->setField('last_name',$lastname);
    $newRecord->setField('email_id',$emailid);
    $newRecord->setField('contact_no',$contactno);
    $newRecord->setField('created_by',$adminemail);
    $newRecord->setField('pwd',$password);

    $result=$newRecord->execute();

    if(FileMaker::isError($result)){
        $error=$result->getMessage();
        return $response->withJson(array("success" => 0,"message"=>"error occurred"),200);
        // exit();
    } //return id also
    return $response->withJson(array("success" => 1,"message"=>"User created successfuly"),201);

});
/**
 * the below endpoint is hit when user tries to login
 */

$app->post('/user',function(Request $request,Response $response,array $args){

    $fm=new FileMaker('users1.fmp12','172.16.9.184','admin','mindfire');
    
    $parsedBody = $request->getParsedBody();

    $email = $parsedBody['email'] ?? false;
    $password = $parsedBody['password'] ?? false;

    $q = $fm -> newFindCommand('users');
    $q -> addFindCriterion('email_id', '=='.$email);
    $q -> addFindCriterion('pwd', '=='.$password);

    $r = $q->execute();

    // return $response->withJson(array("success" => $email,"message"=>$password));
    // exit();
    if(FileMaker::isError($r)){
        return $response->withJson(array("success" => 0,"message"=>"invalid credentials"),200);
    }

    $account = $r -> getFirstRecord();
    $ID_Account = $account->getField('id');
    $firstname = $account->getField('first_name');
    $lastname = $account->getField('last_name');
    $contactno = $account->getField('contact_no');

    return $response->withJson(array("success" => 1,'ID_Account' => $ID_Account, 'firstname' => $firstname, 'lastname' => $lastname, 'Contactno' => $contactno,'email'=>$email),200);

});

/**
 * the below endpoint is used to get the activities list of a user
 * url is hit when the button will be clicked for the activities table
 */

$app->post('/activities',function(Request $request,Response $response,array $args){

    $fm=new FileMaker('users1.fmp12','172.16.9.184','admin','mindfire');
    
    $parsedBody = $request->getParsedBody();

    $id = $parsedBody['id'] ?? false;

    //pass the userid in ajax onclick
    $q = $fm -> newFindCommand('Activity');
	$layout_object = $fm->getLayout('Activity');
	$field_objects = $layout_object->getFields();
    $q -> addFindCriterion('userid', '=='.$id);
    $r = $q->execute();

    if(FileMaker::isError($r)){
        return $response->withJson(array("success" => 0,",message"=>"can't retrieve the activities for the current userid"),200);
    }
    $record_objects = $r->getRecords();
    $arr=array();
    foreach($record_objects as $record_object) {
        $newArray = array();
        foreach($field_objects as $field_object) {
            $field_name = $field_object->getName();
            $field_val = $record_object->getField($field_name);
            $newArray[$field_name] = $field_val;
        }
        array_push($arr,$newArray);
    }
    $result=$arr;
    return $response->withJson(array("success"=>1,"message"=>"user activities list","activities"=>$result),200);
});

/**
 * the below endpoint is used to edit the clicked activities of a particular user
 */
$app->put('/activity',function(Request $request,Response $response,array $args)  use($app){

    // $parsedBody=$request.getParsedBody();
    $parsedBody = $request->getParsedBody();
    // return $response->withJson(array("success" => 0));

    $fm=new FileMaker('users1.fmp12','172.16.9.184','admin','mindfire');
    
    
    $q = $fm -> newFindCommand('Activity');
    
    //search and match here the past record activity
    //since the unique key is not there

    $q -> addFindCriterion('activity_name', '=='.$parsedBody["activityname"]);//these values will come from the table currently clicked
    $q -> addFindCriterion('start_date', '=='.$parsedBody["startdate"]);
    $q -> addFindCriterion('end_date', '=='.$parsedBody["enddate"]);
    $q -> addFindCriterion('start_time', '=='.$parsedBody["starttime"]);
    $q -> addFindCriterion('end_time', '=='.$parsedBody["endtime"]);
    $q -> addFindCriterion('userid', '=='.$parsedBody["id"]);

    $result=$q->execute();
    if(FileMaker::isError($result)){
        return $response->withJson(array("success" => 0,"message"=>"Activity doesn't exist"),200);
    }

    $records = $result->getRecords();
    $recordId = $records[0]->getRecordId();
    
    //return the recordId from here
    //pass the recordid in the next response

    return $response->withJson(array("success"=>1, "recordid"=>$recordId),200);
});

/**
 * the below endpoint deletes the clicked user activity
 * pass all the the table column values in the ajax call and get the values
 */

$app->delete('/activity',function(Request $request,Response $response,array $args){

    $fm=new FileMaker('users1.fmp12','172.16.9.184','admin','mindfire');
    $q = $fm -> newFindCommand('Activity');
    $parsedBody = $request->getParsedBody();

    $activityname = $parsedBody['activityname'] ?? false;
    $startdate = $parsedBody['startdate'] ?? false;
    $enddate = $parsedBody['enddate'] ?? false;
    $starttime = $parsedBody['starttime'] ?? false;
    $endtime = $parsedBody['endtime'] ?? false;
    $id = $parsedBody['id'] ?? false;

    if(!$id){
        return $response->withJson(array("success" => 0,"message"=>"request body is blank"),200);
    }

    $q -> addFindCriterion('activity_name', '=='.$activityname);
    $q -> addFindCriterion('start_date', '=='.$startdate);
    $q -> addFindCriterion('end_date', '=='.$enddate);
    $q -> addFindCriterion('start_time', '=='.$starttime);
    $q -> addFindCriterion('end_time', '=='.$endtime);
    $q -> addFindCriterion('userid', '=='.$id);

    $result=$q->execute();

    if(FileMaker::isError($result)){
        $error=$result->getMessage();
        return $response->withJson(array("success" => 0,"message"=>"Activity doesn't exist"),200);
        exit();
    }
    $records = $result->getRecords();
    $recordId = $records[0]->getRecordId();
    $del=$fm->newDeleteCommand('Activity',$recordId);

    $r=$del->execute();
    
    return $response->withJson(array("success"=>1,"message"=>"activity deleted"),200);

});
/**
 * The below endpoint is used to create the activity
 */

$app->post('/activity',function(Request $request,Response $response,array $args){
    $parsedBody = $request->getParsedBody();
    
    $fm=new FileMaker('users1.fmp12','172.16.9.184','admin','mindfire');

    //check whether the user is performing edit activity or not
    if(isset($parsedBody["recordid"])){
        $newRecord = $fm->getRecordById('Activity',$parsedBody["recordid"]);
    
        $newRecord->setField('activity_name',$parsedBody["activityname"]);
        $newRecord->setField('start_date',$parsedBody["startdate"]);
        $newRecord->setField('end_date',$parsedBody["enddate"]);
        $newRecord->setField('start_time',$parsedBody["starttime"]);
        $newRecord->setField('end_time',$parsedBody["endtime"]);
        
        if($newRecord->commit()){
            return $response->withJson(array("success"=>1, "message"=>"record updated"),200);
        }
    }

    $activity = $parsedBody['activity'] ?? false;


    $startdate = $parsedBody['startdate'] ?? false;
    $enddate = $parsedBody['enddate'] ?? false;
    $starttime = $parsedBody['starttime'] ?? false;
    $endtime = $parsedBody['endtime'] ?? false;
    $id = $parsedBody['id'] ?? false;

    $newRecord=$fm->newAddCommand('Activity');
    
    $newRecord->setField('activity_name',$activity);
    $newRecord->setField('start_date',$startdate);
    $newRecord->setField('end_date',$enddate);
    $newRecord->setField('start_time',$starttime);
    $newRecord->setField('end_time',$endtime);
    $newRecord->setField('userid',$id);
    $result=$newRecord->execute();
    
    if(FileMaker::isError($result)){
        $error=$result->getMessage();
        // echo $error;
        exit();
    }
    return $response->withJson(array("success"=>1,"message"=> $activity));

});

$app->post('/meth',function(Request $request,Response $response,array $args){

    session_start();

    $name=$_POST["fullname"];
    $useremail=$_POST["useremail"];
    $usercontact=$_POST["usercontact"];
    return $response->withJson(array("success"=>1,"name"=>$name,"email"=>$useremail,"contact"=>$useremail));
});

$app->run();