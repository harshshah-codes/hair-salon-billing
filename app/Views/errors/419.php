<?php
/** @var string $message */
$message = $message ?? 'Session expired. Please refresh the page and try again.';
echo view('errors/http', ['status' => 419, 'message' => $message], 'plain');
