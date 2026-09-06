// ============================================================
// MediTrack Activity Logging Module
// File: activity_log.js
//
// Used for:
// 1. Login
// 2. Medicine Creation
// 3. Inventory
// 4. Dispenser Assignment
// 5. Dispenser Refilling
//
// Firebase:
// - Firebase Authentication
// - Firebase Realtime Database
// ============================================================


import {
    ref,
    get,
    push,
    set,
    serverTimestamp
} from "https://www.gstatic.com/firebasejs/12.16.0/firebase-database.js";


// ============================================================
// Configuration
// ============================================================

const ACTIVITY_LOG_PATH = "activity_logs";


// ============================================================
// Main Activity Logging Function
// ============================================================

/**
 * Saves an activity record to Firebase Realtime Database.
 *
 * Usage:
 *
 * await logActivity(database, auth, {
 *     action: "LOGIN",
 *     description: "User logged into MediTrack",
 *     targetType: "User",
 *     targetId: uid
 * });
 *
 * @param {Object} database
 * Firebase Realtime Database instance
 *
 * @param {Object} auth
 * Firebase Authentication instance
 *
 * @param {Object} options
 * Activity information
 *
 * @returns {Promise<string|null>}
 * Firebase activity log ID, or null if logging failed
 */

export async function logActivity(
    database,
    auth,
    {
        action,
        description,
        targetType = "",
        targetId = "",
        status = "SUCCESS",
        details = {}
    }
) {

    try {

        // --------------------------------------------------------
        // Validate required information
        // --------------------------------------------------------

        if (!database) {
            throw new Error(
                "Firebase database instance is required."
            );
        }

        if (!auth) {
            throw new Error(
                "Firebase auth instance is required."
            );
        }

        if (!action) {
            throw new Error(
                "Activity action is required."
            );
        }

        if (!description) {
            throw new Error(
                "Activity description is required."
            );
        }


        // --------------------------------------------------------
        // Get currently authenticated user
        // --------------------------------------------------------

        const currentUser = auth.currentUser;


        if (!currentUser) {

            console.warn(
                "Activity log not recorded: no authenticated user."
            );

            return null;
        }


        const uid = currentUser.uid;


        // --------------------------------------------------------
        // Get user's MediTrack profile
        // --------------------------------------------------------

        let userName = "";
        let userType = "";

        const userSnapshot = await get(
            ref(
                database,
                `users/${uid}`
            )
        );


        if (userSnapshot.exists()) {

            const userData =
                userSnapshot.val();

            userType =
                userData.userType ||
                "";

            userName =
                userData.fullName ||
                buildFullName(userData);
        }


        // --------------------------------------------------------
        // Create new activity log reference
        // --------------------------------------------------------

        const activityRef = push(
            ref(
                database,
                ACTIVITY_LOG_PATH
            )
        );


        // --------------------------------------------------------
        // Activity record
        // --------------------------------------------------------

        const activityData = {

            logId: activityRef.key,

            // User information
            uid: uid,
            userName: userName,
            userType: userType,

            // Activity information
            action: action,
            description: description,

            // Target information
            targetType: targetType,
            targetId: targetId,

            // Result
            status: status,

            // Additional information
            details: details || {},

            // Firebase server timestamp
            timestamp: serverTimestamp()
        };


        // --------------------------------------------------------
        // Save activity
        // --------------------------------------------------------

        await set(
            activityRef,
            activityData
        );


        console.log(
            "Activity logged successfully:",
            action
        );


        return activityRef.key;

    }
    catch (error) {

        console.error(
            "Activity logging failed:",
            error
        );

        return null;
    }
}


// ============================================================
// Build User Full Name
// ============================================================

function buildFullName(userData) {

    const nameParts = [

        userData.fname,
        userData.mname,
        userData.lname,
        userData.suffix

    ];


    return nameParts
        .filter(
            value =>
                value &&
                String(value).trim() !== ""
        )
        .map(
            value =>
                String(value).trim()
        )
        .join(" ");
}


// ============================================================
// LOGIN
// ============================================================

/**
 * Records a successful login.
 */

export async function logLogin(
    database,
    auth
) {

    const user = auth.currentUser;

    if (!user) {
        return null;
    }


    return await logActivity(
        database,
        auth,
        {

            action: "LOGIN",

            description:
                "User logged into MediTrack",

            targetType:
                "User",

            targetId:
                user.uid,

            status:
                "SUCCESS",

            details: {

                email:
                    user.email || ""

            }
        }
    );
}


// ============================================================
// MEDICINE CREATION
// ============================================================

/**
 * Records creation of a new medicine.
 */

export async function logMedicineCreated(
    database,
    auth,
    {
        medicationId,
        genericName,
        brandName = "",
        drugClass = "",
        dosageForm
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "MEDICINE_CREATED",

            description:
                `Created medication: ${genericName}`,

            targetType:
                "Medication",

            targetId:
                medicationId,

            status:
                "SUCCESS",

            details: {

                medicationId:
                    medicationId,

                genericName:
                    genericName,

                brandName:
                    brandName,

                drugClass:
                    drugClass,

                dosageForm:
                    dosageForm

            }
        }
    );
}

// ============================================================
// PRESCRIPTION CREATION
// ============================================================

export async function logPrescriptionCreated(
    database,
    auth,
    {
        prescriptionId,
        patientId,
        patientName,
        physicianUid,
        physicianName,
        medications = []
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "PRESCRIPTION_CREATED",

            description:
                `Created prescription for patient ${patientName}`,

            targetType:
                "Prescription",

            targetId:
                prescriptionId,

            status:
                "SUCCESS",

            details: {

                prescriptionId:
                    prescriptionId,

                patientId:
                    patientId,

                patientName:
                    patientName,

                physicianUid:
                    physicianUid,

                physicianName:
                    physicianName,

                medicationCount:
                    medications.length,

                medications:
                    medications

            }
        }
    );
}

// ============================================================
// INVENTORY
// ============================================================

/**
 * Records adding stock to inventory.
 */

export async function logInventoryAdded(
    database,
    auth,
    {
        inventoryId,
        medicationId,
        medicineName,
        quantity,
        batchNumber = "",
        lotNumber = ""
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "INVENTORY_ADDED",

            description:
                `Added ${quantity} unit(s) of ${medicineName} to inventory`,

            targetType:
                "Inventory",

            targetId:
                inventoryId,

            status:
                "SUCCESS",

            details: {

                inventoryId:
                    inventoryId,

                medicationId:
                    medicationId,

                medicineName:
                    medicineName,

                quantity:
                    quantity,

                batchNumber:
                    batchNumber,

                lotNumber:
                    lotNumber

            }

        }
    );
}


/**
 * Records an inventory deduction.
 */

export async function logInventoryDeducted(
    database,
    auth,
    {
        inventoryId,
        medicineId,
        medicineName,
        quantity,
        remainingQuantity,
        reason = ""
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "INVENTORY_DEDUCTED",

            description:
                `Deducted ${quantity} unit(s) of ${medicineName} from inventory`,

            targetType:
                "Inventory",

            targetId:
                inventoryId,

            status:
                "SUCCESS",

            details: {

                medicineId:
                    medicineId,

                medicineName:
                    medicineName,

                quantity:
                    quantity,

                remainingQuantity:
                    remainingQuantity,

                reason:
                    reason

            }
        }
    );
}


/**
 * Records an inventory quantity adjustment.
 */

export async function logInventoryAdjusted(
    database,
    auth,
    {
        inventoryId,
        medicineId,
        medicineName,
        oldQuantity,
        newQuantity,
        reason = ""
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "INVENTORY_ADJUSTED",

            description:
                `Adjusted inventory quantity for ${medicineName}`,

            targetType:
                "Inventory",

            targetId:
                inventoryId,

            status:
                "SUCCESS",

            details: {

                medicineId:
                    medicineId,

                medicineName:
                    medicineName,

                oldQuantity:
                    oldQuantity,

                newQuantity:
                    newQuantity,

                reason:
                    reason

            }
        }
    );
}


// ============================================================
// DISPENSER ASSIGNMENT
// ============================================================

/**
 * Records assigning a dispenser carousel to a patient.
 */

// ============================================================
// DISPENSER ASSIGNMENT
// ============================================================

export async function logDispenserAssigned(
    database,
    auth,
    {
        dispenserId,
        dispenserName,
        patientId,
        patientName,
        ward = "",
        room = "",
        requiredSlots = 0,
        slotsUsed = 0,
        refillRequired = false,
        refillIntervalDays = 0,
        prescriptionCount = 0
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "DISPENSER_ASSIGNED",

            description:
                `Assigned ${dispenserName} to patient ${patientName}`,

            targetType:
                "Dispenser",

            targetId:
                dispenserId,

            status:
                "SUCCESS",

            details: {

                dispenserId:
                    dispenserId,

                dispenserName:
                    dispenserName,

                patientId:
                    patientId,

                patientName:
                    patientName,

                ward:
                    ward,

                room:
                    room,

                requiredSlots:
                    requiredSlots,

                slotsUsed:
                    slotsUsed,

                refillRequired:
                    refillRequired,

                refillIntervalDays:
                    refillIntervalDays,

                prescriptionCount:
                    prescriptionCount

            }

        }
    );
}

// ============================================================
// DISPENSER REFILL
// ============================================================

export async function logDispenserRefilled(
    database,
    auth,
    {
        dispenserId,
        dispenserName,
        cycle,
        slotsLoaded,
        patientId = "",
        patientName = "",
        ward = "",
        room = "",
        nextRefillAt = "",
        refillIntervalDays = 0
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                "DISPENSER_REFILLED",

            description:
                `Refilled ${dispenserName} for patient ${patientName}`,

            targetType:
                "Dispenser",

            targetId:
                dispenserId,

            status:
                "SUCCESS",

            details: {

                dispenserId:
                    dispenserId,

                dispenserName:
                    dispenserName,

                cycle:
                    cycle,

                slotsLoaded:
                    slotsLoaded,

                patientId:
                    patientId,

                patientName:
                    patientName,

                ward:
                    ward,

                room:
                    room,

                refillIntervalDays:
                    refillIntervalDays,

                nextRefillAt:
                    nextRefillAt

            }

        }
    );
}

// ============================================================
// OPTIONAL GENERIC FAILURE LOGGER
// ============================================================

/**
 * Records a failed operation.
 *
 * This is useful when a medicine creation, inventory update,
 * dispenser assignment, or refill operation fails AFTER
 * authentication has already succeeded.
 */

export async function logFailure(
    database,
    auth,
    {
        action,
        description,
        targetType = "",
        targetId = "",
        error = ""
    }
) {

    return await logActivity(
        database,
        auth,
        {

            action:
                action,

            description:
                description,

            targetType:
                targetType,

            targetId:
                targetId,

            status:
                "FAILED",

            details: {

                error:
                    error

            }
        }
    );
}