# coding: utf-8
import sqlite3
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

DJANGO_DB = r'C:\Users\Laurentiu\Desktop\Proiect RE1\db.sqlite3'

def escape_sql(value):
    if value is None:
        return 'NULL'
    if isinstance(value, (int, float)):
        return str(value)
    return "'" + str(value).replace("'", "''").replace("\\", "\\\\") + "'"

conn = sqlite3.connect(DJANGO_DB)
conn.row_factory = sqlite3.Row
cursor = conn.cursor()

output_file = open('migrate_with_ships.sql', 'w', encoding='utf-8')

output_file.write("-- Migrare COMPLETA cu informatii nave\n\n")
output_file.write("SET NAMES utf8mb4;\n")
output_file.write("SET CHARACTER SET utf8mb4;\n\n")

print("Exportare containere cu informatii nave...")
cursor.execute("SELECT * FROM manifests_manifestentry LIMIT 3195")

output_file.write("-- Containere (3195) - DELETE OLD DATA\n")
output_file.write("DELETE FROM manifest_entries;\n")
output_file.write("DELETE FROM manifests;\n\n")

count = 0
for row in cursor.fetchall():
    manifest_num = escape_sql(row['numar_manifest'])
    date = escape_sql(row['data_inregistrare'])

    # Insert manifest
    output_file.write(f"INSERT IGNORE INTO manifests (manifest_number, arrival_date) VALUES ({manifest_num}, {date});\n")

    # Insert container entry cu TOATE campurile + informatii nave
    container = escape_sql(row['container'])
    tip = escape_sql(row['tip_container'])
    colete = escape_sql(row['numar_colete'])
    greutate = escape_sql(row['greutate_bruta'])
    marfa = escape_sql(row['descriere_marfa'])
    sumara = escape_sql(row['numar_sumara'])
    tip_op = escape_sql(row['tip_operatiune'])
    numar_permis = escape_sql(row['numar_permis'])
    numar_pozitie = escape_sql(row['numar_pozitie'])
    cerere_op = escape_sql(row['cerere_operatiune'])

    # INFORMATII NAVE
    ship_name = escape_sql(row['nume_nava'])
    ship_flag = escape_sql(row['pavilion_nava'])

    output_file.write(f"INSERT INTO manifest_entries (manifest_id, container_number, container_type, packages, weight, goods_description, summary_number, operation_type, permit_number, position_number, operation_request, ship_name, ship_flag) ")
    output_file.write(f"SELECT id, {container}, {tip}, {colete}, {greutate}, {marfa}, {sumara}, {tip_op}, {numar_permis}, {numar_pozitie}, {cerere_op}, {ship_name}, {ship_flag} FROM manifests WHERE manifest_number = {manifest_num} LIMIT 1;\n")

    count += 1
    if count % 500 == 0:
        print(f"  {count}/3195 containere...")

print(f"  {count} containere exportate")

output_file.write("\n-- DONE\n")
output_file.close()
conn.close()

print(f"\nGATA! Fisierul 'migrate_with_ships.sql' a fost creat!")
print("Include: toate campurile + ship_name, ship_flag")
