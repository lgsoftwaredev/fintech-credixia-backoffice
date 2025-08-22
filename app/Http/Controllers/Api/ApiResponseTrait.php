<?php
namespace App\Http\Controllers\Api;

trait ApiResponseTrait
{
    protected function success( $data, $message = null,$code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $code);
    }

    protected function error($message,$code = 400,$data = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => $data
        ], $code ?? 400);
    }
}
