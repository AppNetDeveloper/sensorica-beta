#!/bin/bash
echo "Intentando apagar el sistema: $(date)" >> /var/log/poweroff-script.log
/sbin/shutdown -h now >> /var/log/poweroff-script.log 2>&1
