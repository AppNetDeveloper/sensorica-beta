#!/bin/bash

while true; do
    php /var/www/html/artisan modbus:read
    sleep 0.1
done
