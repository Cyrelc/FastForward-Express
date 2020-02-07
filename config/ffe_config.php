<?php
/* 
 * gst => a positive float value representing the government sales taxes to be applied
 * business_hours_open => a time, between 0:00 and 23:59 that represents the earliest pickup time allowed to customers without further cost penalty
 * business_hours_closed => a time, between 0:00 and 23:59 that represents the latest delivery time allowed to customers without further cost penalty. Should be larger than business_hours_open
*/
return [
    'gst' => 5,
    'business_hours_open' => '8:00',
    'business_hours_close' => '17:00',
]

?>
