<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";

try {

    /* =====================================================
       READ REQUEST
    ===================================================== */

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );


    if (!is_array($data)) {

        throw new Exception(
            "Invalid request data."
        );

    }


    /* =====================================================
       REQUIRED FIELDS
    ===================================================== */

    $required = [

        "prescription_item_id",
        "nurse_uid",
        "scheduled_date",
        "scheduled_time",
        "dosage",
        "route",
        "status"

    ];


    foreach ($required as $field) {

        if (
            !isset($data[$field]) ||
            $data[$field] === "" ||
            $data[$field] === null
        ) {

            throw new Exception(
                "$field is required."
            );

        }

    }


    /* =====================================================
       VALIDATE SCHEDULED DATE
    ===================================================== */

    $scheduledDate =
        trim(
            (string)$data["scheduled_date"]
        );


    /*
        Validate YYYY-MM-DD format.
    */

    $dateObject =
        DateTime::createFromFormat(
            "Y-m-d",
            $scheduledDate
        );


    if (
        !$dateObject ||
        $dateObject->format("Y-m-d") !== $scheduledDate
    ) {

        throw new Exception(
            "Invalid scheduled date: " .
            $scheduledDate
        );

    }


    /* =====================================================
       GET PRESCRIPTION ITEM
    ===================================================== */

    $item =
        $database
            ->getReference(
                "prescription_items/" .
                $data["prescription_item_id"]
            )
            ->getValue();


    if (!$item) {

        throw new Exception(
            "Prescription item not found."
        );

    }


    /* =====================================================
       VALIDATE PRESCRIPTION START DATE
    ===================================================== */

    $prescriptionStartDate =
        $item["start_date"] ?? null;


    if (!$prescriptionStartDate) {

        throw new Exception(
            "Prescription item does not have a valid start date."
        );

    }


    /*
        Validate the prescription start date
        itself before comparing it.
    */

    $startDateObject =
        DateTime::createFromFormat(
            "Y-m-d",
            $prescriptionStartDate
        );


    if (
        !$startDateObject ||
        $startDateObject->format("Y-m-d") !==
            $prescriptionStartDate
    ) {

        throw new Exception(
            "Prescription item has an invalid start date."
        );

    }


    /*
        The scheduled date must match
        the prescription start date.

        There is no longer any end-date
        computation or validation.
    */

    if (
        $scheduledDate !==
        $prescriptionStartDate
    ) {

        throw new Exception(
            "Scheduled date must match the prescription start date."
        );

    }


    /* =====================================================
       GET PARENT PRESCRIPTION
    ===================================================== */

    $prescription =
        $database
            ->getReference(
                "prescriptions/" .
                $item["prescription_id"]
            )
            ->getValue();


    if (!$prescription) {

        throw new Exception(
            "Prescription not found."
        );

    }


    /* =====================================================
       GET NURSE
    ===================================================== */

    $nurse =
        $database
            ->getReference(
                "users/" .
                $data["nurse_uid"]
            )
            ->getValue();


    if (!$nurse) {

        throw new Exception(
            "Nurse not found."
        );

    }


    /* =====================================================
       CREATE SCHEDULE REFERENCE
    ===================================================== */

    $scheduleRef =
        $database
            ->getReference(
                "medication_schedule"
            )
            ->push();


    $scheduleId =
        $scheduleRef->getKey();


    /* =====================================================
       SAVE MEDICATION SCHEDULE
    ===================================================== */

    $scheduleRef->set([

        "schedule_id" =>
            $scheduleId,

        "prescription_id" =>
            $item["prescription_id"],

        "prescription_item_id" =>
            $data["prescription_item_id"],

        "patient_id" =>
            $prescription["patient_id"] ??
            $item["patient_id"] ??
            "",

        "patient_name" =>
            $prescription["patient_name"] ??
            $item["patient_name"] ??
            "",


        /*
            Ward and room
        */

        "assigned_area" =>
            trim(
                $data["assigned_area"] ?? ""
            ),

        "room" =>
            trim(
                $data["room"] ?? ""
            ),


        /*
            Medication
        */

        "medication_id" =>
            $item["medication_id"] ?? "",

        "medication_name" =>
            $item["medication_name"] ?? "",


        /*
            Nurse
        */

        "nurse_uid" =>
            $data["nurse_uid"],

        "nurse_name" =>
            $nurse["fullName"] ?? "",


        /*
            Medication instructions
        */

        "dosage" =>
            $data["dosage"],

        "route" =>
            $data["route"],


        /*
            Single scheduled date
        */

        "scheduled_date" =>
            $scheduledDate,


        /*
            Administration time
        */

        "scheduled_time" =>
            $data["scheduled_time"],


        /*
            Schedule status
        */

        "status" =>
            $data["status"],


        /*
            Creation timestamp
        */

        "created_at" =>
            date("c")

    ]);


    /* =====================================================
       SUCCESS RESPONSE
    ===================================================== */

    echo json_encode([

        "success" =>
            true,

        "message" =>
            "Schedule assigned successfully.",

        "schedule_id" =>
            $scheduleId,

        "scheduled_date" =>
            $scheduledDate

    ]);

}
catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([

        "success" =>
            false,

        "message" =>
            $e->getMessage()

    ]);

}
