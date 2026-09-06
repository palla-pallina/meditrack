<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";


try {

    // =========================================================
    // Read JSON input
    // =========================================================

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (!is_array($data)) {

        throw new Exception(
            "Invalid request data."
        );

    }


    // =========================================================
    // Required fields
    // =========================================================

    $required = [

        "generic_name",
        "form"

    ];


    foreach ($required as $field) {

        if (
            !isset($data[$field]) ||
            trim((string)$data[$field]) === ""
        ) {

            throw new Exception(
                "$field is required."
            );

        }

    }


    // =========================================================
    // Clean input
    // =========================================================

    $genericName =
        trim((string)$data["generic_name"]);

    $brandName =
        trim((string)($data["brand_name"] ?? ""));

    $drugClass =
        trim((string)($data["drug_class"] ?? ""));

    $form =
        strtolower(
            trim((string)$data["form"])
        );


    // =========================================================
    // Allowed dosage forms
    // =========================================================

    $allowedForms = [

        "tablet",
        "capsule",
        "injection",
        "syrup",
        "cream",
        "inhaler",
        "other"

    ];


    if (!in_array($form, $allowedForms, true)) {

        throw new Exception(
            "Invalid dosage form."
        );

    }


    // =========================================================
    // Check duplicate medication
    // =========================================================

    $existing =
        $database
            ->getReference("medications")
            ->getValue();


    if (is_array($existing)) {

        foreach ($existing as $med) {

            if (!is_array($med)) {
                continue;
            }


            $existingGeneric =
                strtolower(
                    trim(
                        (string)(
                            $med["generic_name"] ?? ""
                        )
                    )
                );


            $existingForm =
                strtolower(
                    trim(
                        (string)(
                            $med["form"] ?? ""
                        )
                    )
                );


            if (
                $existingGeneric ===
                    strtolower($genericName)

                &&

                $existingForm ===
                    $form
            ) {

                throw new Exception(
                    "Medication already exists."
                );

            }

        }

    }


    // =========================================================
    // Generate medication ID
    // =========================================================

    $reference =
        $database
            ->getReference("medications")
            ->push();


    $medicationId =
        $reference->getKey();


    // =========================================================
    // Save medication
    // =========================================================

    $reference->set([

        "medication_id" =>
            $medicationId,

        "generic_name" =>
            $genericName,

        "brand_name" =>
            $brandName,

        "drug_class" =>
            $drugClass,

        "form" =>
            $form,

        "created_at" =>
            date("c")

    ]);


    // =========================================================
    // Success response
    // =========================================================

    echo json_encode([

        "success" => true,

        "message" =>
            "Medication added successfully.",

        "medication_id" =>
            $medicationId

    ]);

}
catch (Throwable $e) {

    http_response_code(400);


    echo json_encode([

        "success" => false,

        "message" =>
            $e->getMessage()

    ]);

}