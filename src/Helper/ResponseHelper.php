<?php

namespace App\Helper;

class ResponseHelper
{
    public function validationError($requestId, $message)
    {
        return json_encode([
            'request_id' => $requestId,
            'status' => false,
            'error' => 'VALIDATION_ERROR',
            'message' => $message,
        ]);
    }

    public function authError($requestId)
    {
        return json_encode([
            'request_id' => $requestId,
            'status' => false,
            'error' => 'AUTH_ERROR_INVALID_TOKEN',
            'message' => 'Error. Invalid or expired token was received!',
        ]);
    }

    public function notApprovedError($requestId)
    {
        return json_encode([
            'request_id' => $requestId,
            'status' => false,
            'error' => 'APPROVE_ERROR',
            'message' => "Error. You can't send messages to not approved contacts!",
        ]);
    }

    public function locationResponse($userPhone, $data)
    {
        return json_encode([
            'user_phone' => $userPhone,
            'lat' => $data['lat'],
            'lon' => $data['lon'],
        ]);
    }

    public function messageResponse($userPhone, $data)
    {
        return json_encode([
            'sender_number' => $userPhone,
            'type' => $data['type'],
            'content' => $data['content'],
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
