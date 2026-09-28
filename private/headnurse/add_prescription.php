<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";

try {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (empty($data["patient_id"])) {
        throw new Exception("Patient required.");
    }

    if (empty($data["physician_uid"])) {
        throw new Exception("Physician required.");
    }

    if (empty($data["items"]) || !is_array($data["items"])) {
        throw new Exception("No medications added.");
    }

    // =========================================================
    // LOAD PATIENT
    // =========================================================

    $patientId = $data["patient_id"];

    $patient = $database
        ->getReference("patients/" . $patientId)
        ->getValue();

    if (!$patient) {
        throw new Exception("Patient not found.");
    }


    // =========================================================
    // LOAD EXISTING PRESCRIPTION ITEMS
    // =========================================================

    $existingItemsSnapshot = $database
        ->getReference("prescription_items")
        ->getSnapshot();

    $existingItems = [];

    if ($existingItemsSnapshot->exists()) {

        foreach ($existingItemsSnapshot->getValue() as $itemId => $existingItem) {

            if (!is_array($existingItem)) {
                continue;
            }

            // Only items belonging to the same patient
            if (
                ($existingItem["patient_id"] ?? "") !==
                $patientId
            ) {
                continue;
            }

            $existingItems[] = $existingItem;
        }
    }


    // =========================================================
    // DUPLICATE VALIDATION
    // =========================================================

    foreach ($data["items"] as $newItem) {

        if (empty($newItem["medication_id"])) {
            continue;
        }

        $newMedicationId = $newItem["medication_id"];

        $newDosage = strtolower(
            trim((string)($newItem["dosage"] ?? ""))
        );

        $newRoute = strtolower(
            trim((string)($newItem["route"] ?? ""))
        );

        $newStartDate = $newItem["start_date"] ?? "";
        $newEndDate = $newItem["end_date"] ?? "";

        // -----------------------------------------------------
        // Validate dates
        // -----------------------------------------------------

        if (!$newStartDate || !$newEndDate) {
            throw new Exception(
                "Start date and end date are required for every medication."
            );
        }

        $newStartTimestamp = strtotime(
            $newStartDate . " 00:00:00"
        );

        $newEndTimestamp = strtotime(
            $newEndDate . " 23:59:59"
        );

        if (
            $newStartTimestamp === false ||
            $newEndTimestamp === false
        ) {
            throw new Exception(
                "Invalid prescription date."
            );
        }

        if ($newEndTimestamp < $newStartTimestamp) {
            throw new Exception(
                "End date cannot be earlier than start date."
            );
        }


        // -----------------------------------------------------
        // Compare against existing prescription items
        // -----------------------------------------------------

        foreach ($existingItems as $existingItem) {

            if (
                ($existingItem["medication_id"] ?? "") !==
                $newMedicationId
            ) {
                continue;
            }

            $existingDosage = strtolower(
                trim((string)($existingItem["dosage"] ?? ""))
            );

            $existingRoute = strtolower(
                trim((string)($existingItem["route"] ?? ""))
            );

            // Same dosage
            if ($existingDosage !== $newDosage) {
                continue;
            }

            // Same route
            if ($existingRoute !== $newRoute) {
                continue;
            }

            $existingStartDate =
                $existingItem["start_date"] ?? "";

            $existingEndDate =
                $existingItem["end_date"] ?? "";

            if (
                !$existingStartDate ||
                !$existingEndDate
            ) {
                continue;
            }

            $existingStartTimestamp = strtotime(
                $existingStartDate . " 00:00:00"
            );

            $existingEndTimestamp = strtotime(
                $existingEndDate . " 23:59:59"
            );

            if (
                $existingStartTimestamp === false ||
                $existingEndTimestamp === false
            ) {
                continue;
            }


            // -------------------------------------------------
            // Check date-range overlap
            // -------------------------------------------------

            $datesOverlap =
                $newStartTimestamp <= $existingEndTimestamp &&
                $newEndTimestamp >= $existingStartTimestamp;


            if ($datesOverlap) {

                $medicationName =
                    $existingItem["medication_name"] ??
                    "Unknown medication";

                $existingDosageDisplay =
                    $existingItem["dosage"] ?? "";

                $existingRouteDisplay =
                    $existingItem["route"] ?? "";

                throw new Exception(
                    "Duplicate prescription detected: " .
                    $medicationName .
                    " " .
                    $existingDosageDisplay .
                    " (" .
                    $existingRouteDisplay .
                    ") is already prescribed for this patient " .
                    "from " .
                    $existingStartDate .
                    " to " .
                    $existingEndDate .
                    "."
                );
            }
        }
    }


    // =========================================================
    // CHECK DUPLICATES WITHIN THE CURRENT SUBMISSION
    // =========================================================

    $newItems = $data["items"];

    for ($i = 0; $i < count($newItems); $i++) {

        $itemA = $newItems[$i];

        if (
            empty($itemA["medication_id"]) ||
            empty($itemA["start_date"]) ||
            empty($itemA["end_date"])
        ) {
            continue;
        }

        $aMedicationId =
            $itemA["medication_id"];

        $aDosage = strtolower(
            trim((string)($itemA["dosage"] ?? ""))
        );

        $aRoute = strtolower(
            trim((string)($itemA["route"] ?? ""))
        );

        $aStart = strtotime(
            $itemA["start_date"] . " 00:00:00"
        );

        $aEnd = strtotime(
            $itemA["end_date"] . " 23:59:59"
        );


        for ($j = $i + 1; $j < count($newItems); $j++) {

            $itemB = $newItems[$j];

            if (
                empty($itemB["medication_id"]) ||
                empty($itemB["start_date"]) ||
                empty($itemB["end_date"])
            ) {
                continue;
            }

            if (
                ($itemB["medication_id"] ?? "") !==
                $aMedicationId
            ) {
                continue;
            }

            $bDosage = strtolower(
                trim((string)($itemB["dosage"] ?? ""))
            );

            $bRoute = strtolower(
                trim((string)($itemB["route"] ?? ""))
            );

            if ($aDosage !== $bDosage) {
                continue;
            }

            if ($aRoute !== $bRoute) {
                continue;
            }

            $bStart = strtotime(
                $itemB["start_date"] . " 00:00:00"
            );

            $bEnd = strtotime(
                $itemB["end_date"] . " 23:59:59"
            );

            if (
                $aStart === false ||
                $aEnd === false ||
                $bStart === false ||
                $bEnd === false
            ) {
                continue;
            }

            $datesOverlap =
                $aStart <= $bEnd &&
                $aEnd >= $bStart;

            if ($datesOverlap) {

                throw new Exception(
                    "Duplicate medications detected in this prescription. " .
                    "The same medication, dosage, and route cannot " .
                    "have overlapping prescription dates."
                );
            }
        }
    }


    // =========================================================
    // CREATE PRESCRIPTION
    // =========================================================

    $prescriptionRef = $database
        ->getReference("prescriptions")
        ->push();

    $prescriptionId = $prescriptionRef->getKey();

    $prescriptionRef->set([

        "prescription_id" => $prescriptionId,

        "patient_id" => $patientId,

        "patient_name" =>
            $patient["fullName"] ?? "",

        "ward" =>
            $patient["assignedArea"] ?? "",

        "room" =>
            $patient["room"] ?? "",

        "physician_uid" =>
            $data["physician_uid"],

        "physician_name" =>
            $data["physician_name"] ?? "",

        "status" => "Active",

        "notes" =>
            $data["notes"] ?? "",

        "created_at" => date("c")

    ]);


    // =========================================================
    // CREATE PRESCRIPTION ITEMS
    // =========================================================

    $createdItemCount = 0;

    foreach ($data["items"] as $item) {

        if (empty($item["medication_id"])) {
            continue;
        }

        $med = $database
            ->getReference(
                "medications/" .
                $item["medication_id"]
            )
            ->getValue();

        if (!$med) {
            continue;
        }

        $itemRef = $database
            ->getReference("prescription_items")
            ->push();

        $itemId = $itemRef->getKey();

        $itemRef->set([

            "prescription_item_id" =>
                $itemId,

            "prescription_id" =>
                $prescriptionId,

            "patient_id" =>
                $patientId,

            "patient_name" =>
                $patient["fullName"] ?? "",

            "ward" =>
                $patient["assignedArea"] ?? "",

            "room" =>
                $patient["room"] ?? "",

            "physician_uid" =>
                $data["physician_uid"],

            "physician_name" =>
                $data["physician_name"] ?? "",

            "medication_id" =>
                $item["medication_id"],

            "medication_name" =>
                $med["generic_name"] ?? "",

            "brand_name" =>
                $med["brand_name"] ?? "",

            "form" =>
                $med["form"] ?? "",

            "dosage" =>
                $item["dosage"] ?? "",

            "route" =>
                $item["route"] ?? "",

            "frequency" =>
                $item["frequency"] ?? "",

            "administration_times" =>
                $item["administration_times"] ?? [],

            "start_date" =>
                $item["start_date"] ?? "",

            "end_date" =>
                $item["end_date"] ?? "",

            "status" => "Active",

            "created_at" => date("c")

        ]);

        $createdItemCount++;
    }


    // =========================================================
    // VERIFY AT LEAST ONE ITEM WAS CREATED
    // =========================================================

    if ($createdItemCount === 0) {

        // Remove the empty prescription if no valid
        // medication items were created.
        $database
            ->getReference(
                "prescriptions/" . $prescriptionId
            )
            ->remove();

        throw new Exception(
            "No valid medication items were added."
        );
    }


    // =========================================================
    // SUCCESS
    // =========================================================

    echo json_encode([

        "success" => true,

        "message" =>
            "Prescription saved successfully.",

        "prescription_id" =>
            $prescriptionId

    ]);

} catch (Throwable $e) {

    /*
     * 409 = Conflict
     *
     * This is particularly appropriate for duplicate
     * prescriptions because the submitted resource conflicts
     * with an existing record.
     */

    $message = $e->getMessage();

    if (
        stripos(
            $message,
            "Duplicate prescription"
        ) !== false ||
        stripos(
            $message,
            "Duplicate medications"
        ) !== false
    ) {
        http_response_code(409);
    } else {
        http_response_code(400);
    }

    echo json_encode([

        "success" => false,

        "message" => $message

    ]);
}