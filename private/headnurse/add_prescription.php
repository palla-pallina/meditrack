<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";

try{

$data=json_decode(
file_get_contents("php://input"),
true
);

if(empty($data["patient_id"]))
throw new Exception("Patient required.");

if(empty($data["physician"]))
throw new Exception("Physician required.");

if(empty($data["items"]))
throw new Exception("No medications added.");

$patient=

$database
->getReference(
"patients/".$data["patient_id"]
)
->getValue();

if(!$patient){

throw new Exception(
"Patient not found."
);

}

/* Create Prescription */

$prescriptionRef=

$database
->getReference("prescriptions")
->push();

$prescriptionId=

$prescriptionRef->getKey();

$prescriptionRef->set([

"prescription_id"=>$prescriptionId,

"patient_id"=>$data["patient_id"],

"patient_name"=>$patient["fullName"],

"prescribing_physician"=>$data["physician"],

"status"=>"Active",

"notes"=>$data["notes"],

"created_at"=>date("c")

]);

/* Create Prescription Items */

foreach($data["items"] as $item){

$med=

$database
->getReference(
"medications/".$item["medication_id"]
)
->getValue();

if(!$med){

continue;

}

$itemRef=

$database
->getReference("prescription_items")
->push();

$itemRef->set([

"prescription_id"=>$prescriptionId,

"medication_id"=>$item["medication_id"],

"medication_name"=>$med["generic_name"],

"brand_name"=>$med["brand_name"],

"form"=>$med["form"],

"dosage"=>$item["dosage"],

"route"=>$item["route"],

"frequency"=>$item["frequency"],

"administration_times"=>$item["administration_times"],

"start_date"=>$item["start_date"],

"end_date"=>$item["end_date"],

"status"=>"Active"

]);

}

echo json_encode([

"success"=>true,

"message"=>"Prescription saved.",

"prescription_id"=>$prescriptionId

]);

}
catch(Throwable $e){

http_response_code(400);

echo json_encode([

"success"=>false,

"message"=>$e->getMessage()

]);

}