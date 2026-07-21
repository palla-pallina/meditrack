<?php

header("Content-Type: application/json");

require_once "../../firebase/firebase.php";
require_once "../../mail/send_mail.php";

try{

    $data=json_decode(file_get_contents("php://input"),true);

    $required=[

        "userType",
        "firstName",
        "lastName",
        "email"

    ];

    foreach($required as $field){

        if(empty($data[$field])){

            throw new Exception("$field is required.");

        }

    }

    $allowed=[

        "Admin",
        "Clerk",
        "Doctor",
        "Head Nurse",
        "Nurse",
        "Pharmacist"

    ];

    if(!in_array($data["userType"],$allowed)){

        throw new Exception("Invalid role.");

    }

    $temporaryPassword=

        bin2hex(random_bytes(8));

    $fullName=trim(

        $data["firstName"]." ".
        ($data["middleName"] ?? "")." ".
        $data["lastName"]." ".
        ($data["suffix"] ?? "")

    );

    $user=$auth->createUser([

        "email"=>$data["email"],

        "password"=>$temporaryPassword,

        "displayName"=>$fullName

    ]);

    $uid=$user->uid;

    $database
        ->getReference("users/$uid")
        ->set([

            "uid"=>$uid,

            "firstName"=>$data["firstName"],

            "middleName"=>$data["middleName"] ?? "",

            "lastName"=>$data["lastName"],

            "suffix"=>$data["suffix"] ?? "",

            "fullName"=>$fullName,

            "email"=>$data["email"],

            "userType"=>$data["userType"],

            "accountStatus"=>"Pending",

            "mustChangePassword"=>true,

            "createdAt"=>date("c")

        ]);

    $resetLink=$auth->getPasswordResetLink(

        $data["email"]

    );

    $resetLink = $auth->getPasswordResetLink($data["email"]);

    $subject = "Welcome to MediTrack";

    $body = "
    <h2>Welcome to MediTrack</h2>

    <p>Hello {$fullName},</p>

    <p>Your staff account has been created.</p>

    <p>Please click the link below to complete your account.</p>

    <p>
    <a href='{$resetLink}'>Complete Your Account</a>
    </p>
    ";

    sendMail(
        $data["email"],
        $fullName,
        $subject,
        $body
    );

    echo json_encode([
        "success" => true,
        "uid" => $uid,
        "message" => "Account created successfully. Email sent."
    ]);

}
catch(Throwable $e){

    http_response_code(500);

    echo json_encode([

        "success"=>false,

        "message"=>$e->getMessage()

    ]);

}


$resetLink = $auth->getPasswordResetLink($email);
$subject = "Welcome to MediTrack";

$body = "
<h2>Welcome to MediTrack</h2>

<p>Hello {$fullName},</p>

<p>Your staff account has been created.</p>

<p>Please click the link below to set your password.</p>

<p>
<a href='{$resetLink}'>
Complete Your Account
</a>
</p>

<p>If you did not expect this email, please ignore it.</p>

<p>MediTrack System</p>
";

sendMail(
    $email,
    $fullName,
    $subject,
    $body
);

echo json_encode([

    "success" => true,

    "message" => "Account created successfully."

]);