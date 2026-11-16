#!/bin/bash
set -e

mysql_root=(mariadb --protocol=socket -uroot -p"${MARIADB_ROOT_PASSWORD}")

echo "SIGEL: importando schema.sql"
"${mysql_root[@]}" < /sigel-sql/schema.sql

if [ -f /sigel-sql/seed.sql ]; then
    echo "SIGEL: importando seed.sql"
    "${mysql_root[@]}" < /sigel-sql/seed.sql
fi

echo "SIGEL: creando usuario de la aplicación"
"${mysql_root[@]}" <<SQL
CREATE USER IF NOT EXISTS '${SIGEL_DB_USER}'@'%' IDENTIFIED BY '${SIGEL_DB_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON sigel.* TO '${SIGEL_DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL
