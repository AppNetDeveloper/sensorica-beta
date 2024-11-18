#!/bin/bash
echo "Intentando reiniciar el sistema: $(date)" >> /var/log/reboot-script.log
/sbin/shutdown -r now >> /var/log/reboot-script.log 2>&1