<?php
/** @var string $message */
echo view('errors/http', ['status' => 500, 'message' => $message ?? 'Something went wrong on our side.'], 'plain');
