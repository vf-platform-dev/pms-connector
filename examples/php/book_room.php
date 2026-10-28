<?php
/**
 * Example: quote, then book, then handle the common outcomes.
 *   ROOMZ_API_KEY=rz_xxx php book_room.php
 */
require __DIR__ . '/RoomzClient.php';

$roomz = new RoomzClient(getenv('ROOMZ_BASE') ?: 'https://www.macsoftlabs.co.zw/mukusu/api/v1', (string)getenv('ROOMZ_API_KEY'));

$stay = ['property_id' => 1, 'check_in' => date('Y-m-d', strtotime('+30 days')), 'check_out' => date('Y-m-d', strtotime('+32 days'))];
$orderNo = 'WEB-' . time();   // your website's own order number

try {
    $quote = $roomz->get('/rates/quote', $stay + ['room_type_id' => 1, 'adults' => 2, 'meal_plan' => 'BB']);
    if (!$quote['available']) {
        exit("Sorry, sold out on: " . implode(', ', $quote['shortfalls'][0]['short_nights']) . "\n");
    }
    echo "Price: {$quote['currency']} {$quote['total']}\n";

    $booking = $roomz->send('POST', '/bookings', $stay + [
        'meal_plan'    => 'BB',
        'external_ref' => $orderNo,
        'guest'        => ['firstname' => 'Tendai', 'lastname' => 'Moyo', 'email' => 'tendai@example.com', 'phone' => '+263771000000'],
        'rooms'        => [['room_type_id' => 1, 'adults' => 2]],
    ], $orderNo);   // order number doubles as the Idempotency-Key

    echo "Booked: {$booking['order_no']} ({$booking['status']}), balance {$booking['currency']} {$booking['totals']['balance']}\n";
    foreach ($booking['warnings'] as $w) {
        echo "Note: {$w['message']}\n";
    }
} catch (RoomzApiException $e) {
    switch ($e->errorCode) {
        case 'no_availability':
            echo "Those rooms were just taken - please pick other dates.\n";
            break;
        case 'duplicate_external_ref':
            echo "Already booked as booking #{$e->details['booking_id']}.\n";
            break;
        case 'validation_failed':
            echo "Please check: {$e->getMessage()}\n";
            break;
        default:
            echo "Booking failed ({$e->errorCode}). Reference {$e->requestId}\n";
    }
}
