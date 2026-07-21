<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";

try{

$data=json_decode(

file_get_contents("php://input"),

true

);

$required=[

"prescription_item_id",

"nurse_uid",

"scheduled_time",

"dosage",

"route",

"status"

];

foreach($required as $field){

if(empty($data[$field])){

throw new Exception("$field is required.");

}

}

$item=

$database

->getReference(

"prescription_items/".$data["prescription_item_id"]

)

->getValue();

if(!$item){

throw new Exception(

"Prescription item not found."

);

}

$prescription=

$database

->getReference(

"prescriptions/".$item["prescription_id"]

)

->getValue();

$nurse=

$database

->getReference(

"users/".$data["nurse_uid"]

)

->getValue();

$scheduleRef=

$database

->getReference(

"medication_schedule"

)

->push();

$scheduleId=

$scheduleRef->getKey();

$scheduleRef->set([

"schedule_id"=>$scheduleId,

"prescription_id"=>$item["prescription_id"],

"prescription_item_id"=>$data["prescription_item_id"],

"patient_id"=>$prescription["patient_id"],

"patient_name"=>$prescription["patient_name"],

"medication_id"=>$item["medication_id"],

"medication_name"=>$item["medication_name"],

"nurse_uid"=>$data["nurse_uid"],

"nurse_name"=>$nurse["fullName"],

"dosage"=>$data["dosage"],

"route"=>$data["route"],

"scheduled_time"=>$data["scheduled_time"],

"status"=>$data["status"],

"created_at"=>date("c")

]);

echo json_encode([

"success"=>true,

"message"=>"Schedule assigned successfully.",

"schedule_id"=>$scheduleId

]);

}
catch(Throwable $e){

http_response_code(400);

echo json_encode([

"success"=>false,

"message"=>$e->getMessage()

]);

}