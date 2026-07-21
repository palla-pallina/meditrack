<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";

try{

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    $required = [

        "generic_name",
        "form"

    ];

    foreach($required as $field){

        if(empty($data[$field])){

            throw new Exception("$field is required.");

        }

    }

    $allowedForms = [

        "tablet",
        "capsule",
        "injection",
        "syrup",
        "cream",
        "inhaler",
        "other"

    ];

    if(!in_array($data["form"], $allowedForms)){

        throw new Exception("Invalid dosage form.");

    }

    // Check for duplicate medication

    $existing = $database
        ->getReference("medications")
        ->getValue();

    if($existing){

        foreach($existing as $med){

            if(

                strtolower($med["generic_name"])
                ===
                strtolower($data["generic_name"])

                &&

                strtolower($med["form"])
                ===
                strtolower($data["form"])

            ){

                throw new Exception(
                    "Medication already exists."
                );

            }

        }

    }

    // Generate new medication ID

    $reference = $database
        ->getReference("medications")
        ->push();

    $medicationId = $reference->getKey();

    $reference->set([

        "medication_id" => $medicationId,

        "generic_name" => trim($data["generic_name"]),

        "brand_name" => trim($data["brand_name"] ?? ""),

        "drug_class" => trim($data["drug_class"] ?? ""),

        "form" => strtolower($data["form"]),

        "created_at" => date("c")

    ]);

    echo json_encode([

        "success" => true,

        "message" => "Medication added successfully.",

        "medication_id" => $medicationId

    ]);

}
catch(Throwable $e){

    http_response_code(400);

    echo json_encode([

        "success" => false,

        "message" => $e->getMessage()

    ]);

}