#!/usr/bin/expect -f
# Tiempo máximo de espera para cada prompt
set timeout 20

# Inicia la conexión SSH con el usuario 'pi' a la IP indicada
spawn ssh pi@192.168.123.15

# Espera el prompt de la contraseña del usuario pi
expect {
    "pi@192.168.123.15's password:" {
        send "pi\r"
    }
    timeout {
        puts "Error: Tiempo de espera excedido al esperar la contraseña de SSH."
        exit 1
    }
}

# Espera el prompt del shell. Ajusta el prompt según lo que muestre el sistema remoto.
expect {
    "$ " {}
    "# " {}
    timeout {
        puts "Error: No se detectó el prompt después de iniciar sesión."
        exit 1
    }
}

# Ejecuta 'sudo su' para obtener privilegios de superusuario
send "sudo su\r"

# Espera el prompt para la contraseña del sudo
expect {
    "password for pi:" {
        send "pi\r"
    }
    timeout {
        puts "Error: Tiempo de espera excedido al esperar la contraseña de sudo."
        exit 1
    }
}

# Espera el prompt de root (#) para asegurarse que ya somos superusuario
expect {
    "# " {}
    timeout {
        puts "Error: No se obtuvo el prompt de root."
        exit 1
    }
}

# Ejecuta el comando para reiniciar el servicio sensor.service
send "systemctl restart sensor.service\r"

# Espera la finalización del comando y el prompt
expect {
    "# " {}
    timeout {
        puts "Error: Tiempo de espera excedido al reiniciar el servicio."
        exit 1
    }
}

# Salimos de la sesión de root y luego de la sesión SSH
send "exit\r"
expect {
    "$ " {}
    timeout {}
}
send "exit\r"
expect eof
