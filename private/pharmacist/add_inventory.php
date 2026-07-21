<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";

try{

    $data=json_decode(
        file_get_contents("php://input"),
        true
    );

    $required=[

        "medication_id",
        "lot_number",
        "expiration_date",
        "quantity_in_stock"

    ];

    foreach($required as $field){

        if(empty($data[$field])){

            throw new Exception(
                "$field is required."
            );

        }

    }

    // Verify medication exists

    $medication=$database

        ->getReference(

            "medications/".$data["medication_id"]

        )

        ->getValue();

    if(!$medication){

        throw new Exception(

            "Medication not found."

        );

    }

    // Create inventory record

    $reference=$database

        ->getReference("inventory")

        ->push();

    $inventoryId=
    $reference->getKey();

    $reference->set([

        "inventory_id"=>$inventoryId,

        "medication_id"=>$data["medication_id"],

        "generic_name"=>

            $medication["generic_name"],

        "brand_name"=>

            $medication["brand_name"],

        "form"=>

            $medication["form"],

        "lot_number"=>

            trim($data["lot_number"]),

        "batch_number"=>

            trim($data["batch_number"] ?? ""),

        "manufacturer"=>

            trim($data["manufacturer"] ?? ""),

        "expiration_date"=>

            $data["expiration_date"],

        "purchase_date"=>

            $data["purchase_date"] ?? "",

        "unit_cost"=>

            (float)($data["unit_cost"] ?? 0),

        "selling_price"=>

            (float)($data["selling_price"] ?? 0),

        "quantity_in_stock"=>

            (int)$data["quantity_in_stock"],

        "reorder_level"=>

            (int)($data["reorder_level"] ?? 10),

        "storage_location"=>

            trim($data["storage_location"] ?? ""),

        "created_at"=>

            date("c")

    ]);

    echo json_encode([

        "success"=>true,

        "inventory_id"=>$inventoryId,

        "message"=>

            "Inventory recorded successfully."

    ]);

}
catch(Throwable $e){

    http_response_code(400);

    echo json_encode([

        "success"=>false,

        "message"=>$e->getMessage()

    ]);

}